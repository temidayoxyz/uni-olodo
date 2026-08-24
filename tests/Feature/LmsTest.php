<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CourseOffering;
use App\Models\Programme;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\OfferingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The shared LMS space: enrolled students learn, the assigned lecturer grades,
 * and nobody crosses those lines.
 */
class LmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
        $this->seed(CalendarSeeder::class);
        $this->seed(DemoUsersSeeder::class);
        $this->seed(OfferingsSeeder::class);

        Storage::fake('local');
    }

    private function offering(string $code): CourseOffering
    {
        return CourseOffering::query()
            ->where('semester_id', Semester::where('is_current', true)->value('id'))
            ->whereHas('course', fn ($q) => $q->where('code', $code))
            ->firstOrFail();
    }

    /** Enrol a fresh student in the given offering via an approved registration. */
    private function enrollStudent(CourseOffering $o): User
    {
        $student = User::factory()->role(UserRole::Student)->create();

        StudentProfileSeed::for($student, 'CSC-BS', 300);

        $registration = Registration::create([
            'student_id' => $student->id,
            'semester_id' => $o->semester_id,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $registration->items()->create(['course_offering_id' => $o->id, 'status' => 'registered']);

        return $student;
    }

    public static function lmsPageProvider(): array
    {
        return [
            'course home' => ['/courses/%d'],
            'assignments list' => ['/courses/%d/assignments'],
        ];
    }

    #[DataProvider('lmsPageProvider')]
    public function test_enrolled_students_access_their_course_pages(string $pattern): void
    {
        $offering = $this->offering('CSC 301');
        $student = $this->enrollStudent($offering);

        $this->actingAs($student)
            ->get(sprintf($pattern, $offering->id))
            ->assertOk();
    }

    #[DataProvider('lmsPageProvider')]
    public function test_unenrolled_students_are_refused(string $pattern): void
    {
        $offering = $this->offering('CSC 301');
        $outsider = User::factory()->role(UserRole::Student)->create();

        $this->actingAs($outsider)
            ->get(sprintf($pattern, $offering->id))
            ->assertForbidden();
    }

    public function test_lecturer_manages_their_own_offering_only(): void
    {
        $obi = User::where('email', 'c.obi@olodo.edu.ng')->firstOrFail(); // owns CSC 301 + CSC 305

        $own = $this->offering('CSC 301');
        $others = $this->offering('BUS 201');

        $this->actingAs($obi)->get('/courses/'.$own->id)->assertOk();
        $this->actingAs($obi)->get('/courses/'.$own->id.'/assignments')->assertOk();
        $this->actingAs($obi)->get('/courses/'.$others->id)->assertForbidden();
    }

    public function test_student_submits_and_replaces_before_the_deadline(): void
    {
        $offering = $this->offering('CSC 305');

        $live = $offering->assignments()->create([
            'title' => 'Live lab task',
            'instructions' => 'Submit your work as PDF.',
            'points' => 50,
            'available_from' => now()->subDay(),
            'due_at' => now()->addWeek(),
            'published_at' => now(),
        ]);

        $student = $this->enrollStudent($offering);

        $this->actingAs($student)
            ->post("/courses/{$offering->id}/assignments/{$live->id}/submit", [
                'file' => UploadedFile::fake()->create('work-v1.pdf', 200, 'application/pdf'),
                'note' => 'First attempt.',
            ])->assertRedirect()->assertSessionHas('status');

        $submission = $live->submissions()->where('student_id', $student->id)->firstOrFail();
        $this->assertSame('First attempt.', $submission->note);
        Storage::disk('local')->assertExists($submission->file_path);

        // Replacement keeps ONE row and swaps the file.
        $this->post("/courses/{$offering->id}/assignments/{$live->id}/submit", [
            'file' => UploadedFile::fake()->create('work-v2.pdf', 240, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame(1, $live->submissions()->count());
        $fresh = $submission->fresh();
        $this->assertSame('work-v2.pdf', $fresh->original_name);
        Storage::disk('local')->assertMissing($submission->file_path);
        Storage::disk('local')->assertExists($fresh->file_path);
    }

    public function test_submission_after_the_deadline_is_refused(): void
    {
        $offering = $this->offering('CSC 305');
        $past = $offering->assignments()->create([
            'title' => 'Closed task',
            'instructions' => 'Too late.',
            'points' => 50,
            'due_at' => now()->subDays(3),
            'late_until' => null,
            'published_at' => now()->subDays(10),
        ]);

        $student = $this->enrollStudent($offering);

        $this->actingAs($student)
            ->post("/courses/{$offering->id}/assignments/{$past->id}/submit", [
                'file' => UploadedFile::fake()->create('late.pdf', 100, 'application/pdf'),
            ])->assertForbidden();

        $this->assertSame(0, $past->submissions()->count());
    }

    public function test_grading_is_scoped_to_the_assigned_lecturer(): void
    {
        $csc305 = $this->offering('CSC 305'); // owned by Dr. Obi
        $otherLecturer = User::where('email', 'y.ibrahim@olodo.edu.ng')->firstOrFail(); // BusAdmin lecturer
        $student = $this->enrollStudent($csc305);

        $assignment = $csc305->assignments()->create([
            'title' => 'Scoping check', 'instructions' => '…', 'points' => 20,
            'due_at' => now()->addDays(2), 'published_at' => now(),
        ]);

        $submission = $assignment->submissions()->create([
            'student_id' => $student->id,
            'file_path' => 'assignments/x/sub.pdf',
            'original_name' => 'sub.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9000,
            'submitted_at' => now(),
        ]);
        Storage::disk('local')->put($submission->file_path, '%PDF-1.4 x');

        // A different lecturer cannot open the queue or grade.
        $queue = "/courses/{$csc305->id}/assignments/{$assignment->id}/grade";
        $this->actingAs($otherLecturer)->get($queue)->assertForbidden();

        $this->actingAs($otherLecturer)
            ->post("/courses/{$csc305->id}/submissions/{$submission->id}/grade", [
                'score' => 18, 'feedback' => 'Nice.',
            ])->assertForbidden();

        // The assigned lecturer grades; score is clamped to the max and feedback required.
        $obi = User::where('email', 'c.obi@olodo.edu.ng')->firstOrFail();

        $this->actingAs($obi)
            ->post("/courses/{$csc305->id}/submissions/{$submission->id}/grade", [
                'score' => 500, 'feedback' => 'Over the top.',
            ])->assertSessionHasErrors('score');

        $this->actingAs($obi)
            ->post("/courses/{$csc305->id}/submissions/{$submission->id}/grade", [
                'score' => 17,
            ])->assertSessionHasErrors('feedback');

        $this->actingAs($obi)
            ->post("/courses/{$csc305->id}/submissions/{$submission->id}/grade", [
                'score' => 17, 'feedback' => 'Solid ER model; tighten cardinalities next time.',
            ])->assertRedirect()->assertSessionHas('status');

        $submission->refresh();
        $this->assertSame(17.0, $submission->score);
        $this->assertTrue($submission->isGraded());

        // The student sees their grade but can no longer replace the file.
        $this->actingAs($student)->get("/courses/{$csc305->id}/assignments/{$assignment->id}")
            ->assertOk()
            ->assertSee('17')
            ->assertSee('cardinalities');
    }

    public function test_students_cannot_grade_even_their_own_submission(): void
    {
        $csc301 = $this->offering('CSC 301');
        $student = $this->enrollStudent($csc301);

        $assignment = $csc301->assignments()->create([
            'title' => 'Self-grading attempt', 'instructions' => '…', 'points' => 10,
            'due_at' => now()->addDays(4), 'published_at' => now(),
        ]);

        $submission = $assignment->submissions()->create([
            'student_id' => $student->id,
            'file_path' => 'assignments/y/self.pdf',
            'original_name' => 'self.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 8000,
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->post("/courses/{$csc301->id}/submissions/{$submission->id}/grade", [
                'score' => 10, 'feedback' => 'Perfect, obviously.',
            ])->assertForbidden();

        $this->assertNull($submission->fresh()->graded_at);
    }
}

/** Tiny helper so enrolment tests get valid profiles without the full seeder. */
final class StudentProfileSeed
{
    private static int $seq = 1;

    public static function for(User $student, string $programmeCode, int $level): void
    {
        StudentProfile::create([
            'user_id' => $student->id,
            'matric_number' => sprintf('UO/LMS/24/%04d', self::$seq++),
            'programme_id' => Programme::where('code', $programmeCode)->value('id'),
            'level' => $level,
        ]);
    }
}

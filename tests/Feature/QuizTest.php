<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CourseOffering;
use App\Models\Programme;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\OfferingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Timed quiz attempts: windows, attempt limits, server-side timing,
 * and honest auto-scoring across all four question types.
 */
class QuizTest extends TestCase
{
    use RefreshDatabase;

    private CourseOffering $offering;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
        $this->seed(CalendarSeeder::class);
        $this->seed(DemoUsersSeeder::class);
        $this->seed(OfferingsSeeder::class);

        $this->offering = CourseOffering::query()
            ->where('semester_id', Semester::where('is_current', true)->value('id'))
            ->whereHas('course', fn ($q) => $q->where('code', 'CSC 301'))
            ->firstOrFail();
    }

    /** A quiz whose window is open right now, with one question of each type. */
    private function liveQuiz(array $overrides = []): Quiz
    {
        $quiz = $this->offering->quizzes()->create([
            'title' => 'Unit check',
            'instructions' => 'Answer everything.',
            'duration_minutes' => 30,
            'available_from' => now()->subHour(),
            'available_until' => now()->addDay(),
            'max_attempts' => 1,
            'reveal_answers' => true,
            'published_at' => now(),
            ...$overrides,
        ]);

        $quiz->questions()->createMany([
            ['type' => 'single_choice', 'prompt' => '2 + 2?', 'options' => [
                ['key' => 'a', 'text' => '3'], ['key' => 'b', 'text' => '4'], ['key' => 'c', 'text' => '5'],
            ], 'answers' => ['b'], 'points' => 2, 'position' => 1],
            ['type' => 'true_false', 'prompt' => 'A queue is LIFO.', 'options' => null, 'answers' => ['false'], 'points' => 1, 'position' => 2],
            ['type' => 'multi_choice', 'prompt' => 'Select the even numbers.', 'options' => [
                ['key' => 'a', 'text' => '2'], ['key' => 'b', 'text' => '3'], ['key' => 'c', 'text' => '4'],
            ], 'answers' => ['a', 'c'], 'points' => 3, 'position' => 3],
            ['type' => 'short_answer', 'prompt' => 'Name the traversal that visits level by level.', 'options' => null,
                'answers' => ['breadth-first', 'BFS'], 'points' => 2, 'position' => 4],
        ]);

        return $quiz->fresh();
    }

    private function enrolledStudent(): User
    {
        $student = User::factory()->role(UserRole::Student)->create();
        StudentProfile::create([
            'user_id' => $student->id,
            'matric_number' => sprintf('UO/QZ/24/%04d', random_int(1000, 9999)),
            'programme_id' => Programme::where('code', 'CSC-BS')->value('id'),
            'level' => 300,
        ]);
        $registration = Registration::create([
            'student_id' => $student->id,
            'semester_id' => $this->offering->semester_id,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $registration->items()->create(['course_offering_id' => $this->offering->id, 'status' => 'registered']);

        return $student;
    }

    public function test_student_sees_start_page_and_opens_a_timed_attempt(): void
    {
        $quiz = $this->liveQuiz();
        $student = $this->enrolledStudent();

        // Before the window opens: hidden entirely.
        $closed = $this->liveQuiz(['available_from' => now()->addHours(2)]);
        $this->actingAs($student)->get("/courses/{$this->offering->id}/quizzes/{$closed->id}")
            ->assertNotFound();

        $this->actingAs($student)->get("/courses/{$this->offering->id}/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertSee('Start attempt')
            ->assertSee('The timer starts');

        $attemptId = $this->actingAs($student)
            ->post("/courses/{$this->offering->id}/quizzes/{$quiz->id}/start")
            ->assertRedirect();

        $attempt = QuizAttempt::where('student_id', $student->id)->firstOrFail();
        $this->assertFalse($attempt->isSubmitted());
    }

    public function test_answers_are_auto_scored_across_all_question_types(): void
    {
        $quiz = $this->liveQuiz();
        $student = $this->enrolledStudent();

        $this->actingAs($student)->post("/courses/{$this->offering->id}/quizzes/{$quiz->id}/start");
        $attempt = QuizAttempt::where('student_id', $student->id)->firstOrFail();
        $questions = $quiz->questions->keyBy('prompt')->values();

        $single = $quiz->questions->firstWhere('type', 'single_choice');
        $tf = $quiz->questions->firstWhere('type', 'true_false');
        $multi = $quiz->questions->firstWhere('type', 'multi_choice');
        $short = $quiz->questions->firstWhere('type', 'short_answer');

        // One wrong single-choice, correct TF, partial multi, trimmed/cased short answer.
        $this->post("/courses/{$this->offering->id}/quizzes/{$quiz->id}/attempts/{$attempt->id}", [
            'answers' => [
                $single->id => 'a',        // wrong
                $tf->id => 'false',         // exact form key
                $multi->id => ['a'],        // partial — incomplete set scores zero
                $short->id => '  bfs ',     // whitespace + casing tolerated for short answers
            ],
        ])->assertRedirect();

        $attempt->refresh();
        $this->assertNotNull($attempt->submitted_at);

        // TF (1) + short answer (2) only.
        $this->assertSame(3.0, (float) $attempt->score);

        $answers = $attempt->answers->keyBy('quiz_question_id');
        $this->assertFalse($answers[$single->id]->is_correct);
        $this->assertTrue($answers[$tf->id]->is_correct);
        $this->assertFalse($answers[$multi->id]->is_correct);
        $this->assertTrue($answers[$short->id]->is_correct);

        // Results page shows score and review when reveal_answers is on.
        $this->get("/courses/{$this->offering->id}/quizzes/{$quiz->id}/result")
            ->assertOk()
            ->assertSee('3.0');
    }

    public function test_attempt_limits_are_enforced(): void
    {
        $quiz = $this->liveQuiz(['max_attempts' => 1]);
        $student = $this->enrolledStudent();

        $this->actingAs($student)->post("/courses/{$this->offering->id}/quizzes/{$quiz->id}/start");
        $attempt = QuizAttempt::where('student_id', $student->id)->firstOrFail();
        $attempt->forceFill(['submitted_at' => now(), 'score' => 4])->save();

        $this->actingAs($student)
            ->post("/courses/{$this->offering->id}/quizzes/{$quiz->id}/start")
            ->assertForbidden();
    }

    public function test_late_submissions_past_duration_and_grace_are_refused_without_a_fake_score(): void
    {
        $quiz = $this->liveQuiz(['duration_minutes' => 10]);
        $student = $this->enrolledStudent();

        $this->actingAs($student)->post("/courses/{$this->offering->id}/quizzes/{$quiz->id}/start");

        $attempt = QuizAttempt::where('student_id', $student->id)->firstOrFail();
        // Simulate a student who lost connectivity: clock moved past duration + grace.
        $attempt->forceFill(['started_at' => now()->subMinutes(20)])->save();

        $response = $this->post("/courses/{$this->offering->id}/quizzes/{$quiz->id}/attempts/{$attempt->id}", [
            'answers' => [],
        ]);

        $response->assertOk(); // renders the expiry explanation page
        $attempt->refresh();
        $this->assertNotNull($attempt->submitted_at);
        $this->assertNull($attempt->score); // closed without inventing a result
    }

    public function test_unenrolled_students_cannot_touch_quizzes(): void
    {
        $quiz = $this->liveQuiz();
        $outsider = User::factory()->role(UserRole::Student)->create();

        $this->actingAs($outsider)->get("/courses/{$this->offering->id}/quizzes/{$quiz->id}")->assertForbidden();
        $this->actingAs($outsider)->post("/courses/{$this->offering->id}/quizzes/{$quiz->id}/start")->assertForbidden();
    }

    public function test_the_lecturer_sees_the_quiz_overview_not_the_paper(): void
    {
        $quiz = $this->liveQuiz();
        $obi = User::where('email', 'c.obi@olodo.edu.ng')->firstOrFail();

        $this->actingAs($obi)->get("/courses/{$this->offering->id}/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertSee('Questions')
            ->assertSee('Accepted:')
            ->assertDontSee('Start attempt');
    }
}

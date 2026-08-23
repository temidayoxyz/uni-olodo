<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseScore;
use App\Models\PublishedResult;
use App\Models\Registration;
use App\Models\RegistrationItem;
use App\Models\ResultSubmission;
use App\Models\Semester;
use App\Models\User;
use App\Support\GradeScale;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Two completed sessions of history for the named students — registrations,
 * component scores, result submissions and immutable published results.
 *
 * Zainab's scores are chosen so her CGPA lands honestly at ≈3.62:
 *   24/25 FS: CSC101 76 · CSC102 63 · MTH101 71 · GST101 68
 *   24/25 SS: MTH102 58 · GST102 51 · STA105 60
 *   25/26 FS: CSC201 67 · CSC202 56 · MTH205 64 · GST201 70
 *   25/26 SS: SWE201 59 · DSC201 44 · STA205 53
 */
class AcademicHistorySeeder extends Seeder
{
    /** student email => session name => [course code => [ca, exam]] (CA /40, exam /60) */
    private const HISTORY = [
        'z.adeyemi@student.olodo.edu.ng' => [
            'FS-2' => ['CSC 101' => [31, 45], 'CSC 102' => [24, 39], 'MTH 101' => [28, 43], 'GST 101' => [26, 42]],
            'SS-2' => ['MTH 102' => [22, 36], 'GST 102' => [20, 31], 'STA 105' => [23, 37]],
            'FS-1' => ['CSC 201' => [26, 41], 'CSC 202' => [22, 34], 'MTH 205' => [25, 39], 'GST 201' => [28, 42]],
            'SS-1' => ['SWE 201' => [23, 36], 'DSC 201' => [17, 27], 'STA 205' => [21, 32]],
        ],
        'f.alade@student.olodo.edu.ng' => [
            'FS-1' => ['CSC 301' => [30, 48], 'CSC 303' => [27, 44], 'CSC 305' => [33, 47], 'GST 201' => [29, 41]],
            'SS-1' => ['CSC 304' => [31, 46], 'CSC 308' => [29, 45], 'STA 205' => [24, 35], 'SWE 301' => [30, 49]],
        ],
        'd.okon@student.olodo.edu.ng' => [
            'FS-1' => ['BUS 101' => [28, 40], 'ACC 101' => [25, 38], 'GST 101' => [30, 44]],
            'SS-1' => ['GST 201' => [27, 41], 'STA 105' => [22, 33]],
        ],
    ];

    public function run(): void
    {
        $currentSessionName = AcademicSession::where('is_current', true)->value('name');
        [$startYear] = sscanf($currentSessionName, '%d/%d');

        // Past semesters, oldest first: SS of session−2 is not needed; FS/SS of −2 and FS/SS of −1.
        $past = [
            'FS-2' => Semester::whereHas('session', fn ($q) => $q->where('name', ($startYear - 2).'/'.($startYear - 1)))->where('number', 1)->first(),
            'SS-2' => Semester::whereHas('session', fn ($q) => $q->where('name', ($startYear - 2).'/'.($startYear - 1)))->where('number', 2)->first(),
            'FS-1' => Semester::whereHas('session', fn ($q) => $q->where('name', ($startYear - 1).'/'.($startYear)))->where('number', 1)->first(),
            'SS-1' => Semester::whereHas('session', fn ($q) => $q->where('name', ($startYear - 1).'/'.($startYear)))->where('number', 2)->first(),
        ];

        // Lecturers who "taught" past offerings — rotate plausibly by department prefix.
        $lecturersByPrefix = [
            'CSC' => User::where('email', 'c.obi@olodo.edu.ng')->value('id'),
            'SWE' => User::where('email', 'a.umeh@olodo.edu.ng')->value('id'),
            'DSC' => User::where('email', 'c.obi@olodo.edu.ng')->value('id'),
            'MTH' => User::where('email', 'e.uche@olodo.edu.ng')->value('id'),
            'STA' => User::where('email', 'e.uche@olodo.edu.ng')->value('id'),
            'BUS' => User::where('email', 'y.ibrahim@olodo.edu.ng')->value('id'),
            'ACC' => User::where('email', 'b.adeoye@olodo.edu.ng')->value('id'),
            'GST' => User::where('email', 'y.ibrahim@olodo.edu.ng')->value('id'),
            'EEE' => User::where('email', 'k.lawal@olodo.edu.ng')->value('id'),
        ];

        DB::transaction(function () use ($past, $lecturersByPrefix) {
            foreach ($past as $key => $semester) {
                if ($semester === null) {
                    continue;
                }

                foreach (self::HISTORY as $email => $perSemester) {
                    $scores = $perSemester[$key] ?? null;
                    if ($scores === null) {
                        continue;
                    }

                    $student = User::where('email', $email)->firstOrFail();

                    $registration = Registration::create([
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                        'status' => 'approved',
                        'submitted_at' => $semester->registration_opens_at?->copy()->addDays(3),
                        'approved_at' => $semester->registration_opens_at?->copy()->addDays(5),
                    ]);

                    foreach ($scores as $code => [$ca, $exam]) {
                        $course = Course::where('code', $code)->firstOrFail();
                        $offering = CourseOffering::firstOrCreate([
                            'course_id' => $course->id,
                            'semester_id' => $semester->id,
                        ], [
                            'lecturer_id' => $lecturersByPrefix[strtok($code, ' ')],
                            'capacity' => 60,
                            'status' => 'closed',
                        ]);

                        RegistrationItem::create([
                            'registration_id' => $registration->id,
                            'course_offering_id' => $offering->id,
                            'status' => 'registered',
                        ]);

                        $total = $ca + $exam;

                        CourseScore::create([
                            'course_offering_id' => $offering->id,
                            'student_id' => $student->id,
                            'ca_score' => $ca,
                            'exam_score' => $exam,
                            'last_edited_by' => $offering->lecturer_id,
                        ]);

                        // Completed history flows through the approval chain into official results.
                        $submission = ResultSubmission::firstOrCreate([
                            'course_offering_id' => $offering->id,
                        ], [
                            'submitted_by' => $offering->lecturer_id,
                            'status' => 'published',
                            'submitted_at' => $semester->ends_on->copy()->addWeeks(2),
                            'reviewed_by' => User::where('role', 'registrar')->value('id'),
                            'reviewed_at' => $semester->ends_on->copy()->addWeeks(4),
                            'published_at' => $semester->ends_on->copy()->addWeeks(5),
                        ]);

                        PublishedResult::create([
                            'result_submission_id' => $submission->id,
                            'course_offering_id' => $offering->id,
                            'semester_id' => $semester->id,
                            'student_id' => $student->id,
                            'ca_score' => $ca,
                            'exam_score' => $exam,
                            'total' => $total,
                            'grade_letter' => GradeScale::letterFor($total),
                            'grade_point' => GradeScale::pointFor($total),
                            'is_passed' => GradeScale::isPassed($total),
                            'published_at' => $submission->published_at,
                        ]);
                    }
                }
            }

            // Registrar's live queue: one completed past offering submitted but NOT yet
            // approved (Dr. Obi's CSC 201), and one with scores entered but not submitted.
            $this->seedPendingApprovals($past['SS-1'], $lecturersByPrefix);
        });
    }

    private function seedPendingApprovals(?Semester $lastSemester, array $lecturersByPrefix): void
    {
        if ($lastSemester === null) {
            return;
        }

        $fillerStudents = User::query()
            ->where('role', 'student')
            ->whereNotIn('email', [
                'z.adeyemi@student.olodo.edu.ng',
                'f.alade@student.olodo.edu.ng',
                'd.okon@student.olodo.edu.ng',
            ])
            ->whereHas('studentProfile', fn ($q) => $q->whereIn('level', [200, 300]))
            ->take(6)
            ->get();

        // CSC 201 last semester — submitted, awaiting registrar approval.
        $csc201 = CourseOffering::firstOrCreate([
            'course_id' => Course::where('code', 'CSC 201')->value('id'),
            'semester_id' => $lastSemester->id,
        ], [
            'lecturer_id' => $lecturersByPrefix['CSC'],
            'capacity' => 60,
            'status' => 'closed',
        ]);

        foreach ($fillerStudents as $i => $student) {
            [$ca, $exam] = [[26, 43], [21, 35], [28, 47], [19, 30], [24, 39], [22, 34]][$i];
            $total = $ca + $exam;

            CourseScore::updateOrCreate([
                'course_offering_id' => $csc201->id,
                'student_id' => $student->id,
            ], [
                'ca_score' => $ca,
                'exam_score' => $exam,
                'last_edited_by' => $csc201->lecturer_id,
            ]);
        }

        ResultSubmission::firstOrCreate([
            'course_offering_id' => $csc201->id,
        ], [
            'submitted_by' => $csc201->lecturer_id,
            'status' => 'submitted',
            'note' => 'All scripts moderated. CA average 24.2, exam average 38.0.',
            'submitted_at' => now()->subDays(6),
        ]);

        // STA 105 last semester — scores entered by Dr. Uche but NOT yet submitted.
        $sta105 = CourseOffering::firstOrCreate([
            'course_id' => Course::where('code', 'STA 105')->value('id'),
            'semester_id' => $lastSemester->id,
        ], [
            'lecturer_id' => $lecturersByPrefix['STA'],
            'capacity' => 80,
            'status' => 'closed',
        ]);

        foreach ($fillerStudents as $i => $student) {
            [$ca, $exam] = [[23, 36], [27, 41], [18, 29], [25, 40], [20, 31], [26, 42]][$i];

            CourseScore::updateOrCreate([
                'course_offering_id' => $sta105->id,
                'student_id' => $student->id,
            ], [
                'ca_score' => $ca,
                'exam_score' => $exam,
                'last_edited_by' => $sta105->lecturer_id,
            ]);
        }

        // Keep the registration records coherent so these students were genuinely enrolled.
        foreach ([$csc201, $sta105] as $offering) {
            foreach ($fillerStudents as $student) {
                $registration = Registration::firstOrCreate([
                    'student_id' => $student->id,
                    'semester_id' => $lastSemester->id,
                ], [
                    'status' => 'approved',
                    'submitted_at' => $lastSemester->registration_opens_at?->copy()->addDays(2),
                    'approved_at' => $lastSemester->registration_opens_at?->copy()->addDays(4),
                ]);

                RegistrationItem::firstOrCreate([
                    'registration_id' => $registration->id,
                    'course_offering_id' => $offering->id,
                ], ['status' => 'registered']);
            }
        }
    }
}

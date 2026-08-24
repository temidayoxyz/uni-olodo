<?php

namespace App\Services;

use App\Enums\ResultSubmissionStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CourseOffering;
use App\Models\CourseScore;
use App\Models\PublishedResult;
use App\Models\ResultSubmission;
use App\Models\User;
use App\Notifications\PortalNotice;
use App\Policies\CourseOfferingPolicy;
use App\Support\GradeScale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The results lifecycle:
 *   component scores (lecturer) → submission for approval → registrar
 *   approve / return → publish as immutable snapshots students can see.
 */
class ResultService
{
    public const CA_MAX = 40.0;

    public const EXAM_MAX = 60.0;

    /**
     * Save component scores for an offering. Only the assigned lecturer may write,
     * and only while the results are not yet approved/published.
     *
     * @param  array<int, array{ca: ?numeric, exam: ?numeric}>  $scores  keyed by student id
     */
    public function saveScores(User $editor, CourseOffering $offering, array $scores): int
    {
        // Explicit-user authorization: services may run outside a request session.
        if (! $editor->can('grade', $offering)) {
            throw new AuthorizationException;
        }

        if ($this->submissionFor($offering)?->statusIs(ResultSubmissionStatus::Approved, ResultSubmissionStatus::Published)) {
            throw ValidationException::withMessages([
                'results' => 'Results for this offering are locked (already approved or published).',
            ]);
        }

        $written = 0;

        DB::transaction(function () use ($editor, $offering, $scores, &$written): void {
            foreach ($scores as $studentId => $pair) {
                // Only enrolled students may carry scores.
                if (! CourseOfferingPolicy::isEnrolledById((int) $studentId, $offering)) {
                    continue;
                }

                $ca = $pair['ca'] === null || $pair['ca'] === '' ? null : min((float) $pair['ca'], self::CA_MAX);
                $exam = $pair['exam'] === null || $pair['exam'] === '' ? null : min((float) $pair['exam'], self::EXAM_MAX);

                if ($ca !== null && $ca < 0) {
                    $ca = 0;
                }
                if ($exam !== null && $exam < 0) {
                    $exam = 0;
                }

                CourseScore::updateOrCreate([
                    'course_offering_id' => $offering->id,
                    'student_id' => $studentId,
                ], [
                    'ca_score' => $ca,
                    'exam_score' => $exam,
                    'last_edited_by' => $editor->id,
                ]);

                $written++;
            }
        });

        AuditLog::record('results.scores_saved', $offering, ['rows' => $written]);

        return $written;
    }

    /**
     * Submit the offering's provisional results for registry approval.
     * The gradebook must be complete — every enrolled student fully scored.
     */
    public function submit(User $lecturer, CourseOffering $offering): ResultSubmission
    {
        if (! $lecturer->can('grade', $offering)) {
            throw new AuthorizationException;
        }

        $existing = $this->submissionFor($offering);

        if ($existing?->statusIs(ResultSubmissionStatus::Submitted, ResultSubmissionStatus::Approved, ResultSubmissionStatus::Published)) {
            throw ValidationException::withMessages([
                'results' => 'These results are already '.$existing->status->label().'.',
            ]);
        }

        $enrolled = $offering->enrolledStudents()->pluck('users.id');
        $scored = CourseScore::query()
            ->where('course_offering_id', $offering->id)
            ->whereNotNull('ca_score')
            ->whereNotNull('exam_score')
            ->pluck('student_id');

        $missing = $enrolled->diff($scored);
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'results' => 'The gradebook is incomplete: '.$missing->count().' student(s) have no complete CA + examination scores.',
            ]);
        }

        if ($enrolled->isEmpty()) {
            throw ValidationException::withMessages(['results' => 'No students are enrolled in this offering.']);
        }

        $submission = ResultSubmission::updateOrCreate(
            ['course_offering_id' => $offering->id],
            [
                'submitted_by' => $lecturer->id,
                'status' => ResultSubmissionStatus::Submitted,
                'note' => null,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'published_at' => null,
            ],
        );

        AuditLog::record('results.submitted', $offering, ['students' => $enrolled->count()]);

        return $submission;
    }

    /** Registrar approves the provisional results; publication is a separate, deliberate step. */
    public function approve(ResultSubmission $submission, User $registrar): ResultSubmission
    {
        $this->assertRegistrar($registrar);
        $this->assertStatus($submission, ResultSubmissionStatus::Submitted);

        $submission->forceFill([
            'status' => ResultSubmissionStatus::Approved,
            'reviewed_by' => $registrar->id,
            'reviewed_at' => now(),
        ])->save();

        AuditLog::record('results.approved', $submission->offering, ['by' => $registrar->email]);

        return $submission;
    }

    /** Send results back to the lecturer with the registrar's reason. */
    public function returnForCorrections(ResultSubmission $submission, User $registrar, string $note): ResultSubmission
    {
        $this->assertRegistrar($registrar);
        $this->assertStatus($submission, ResultSubmissionStatus::Submitted);

        if (trim($note) === '') {
            throw ValidationException::withMessages(['note' => 'State what must be corrected.']);
        }

        $submission->forceFill([
            'status' => ResultSubmissionStatus::Returned,
            'reviewed_by' => $registrar->id,
            'reviewed_at' => now(),
            'note' => $note,
        ])->save();

        AuditLog::record('results.returned', $submission->offering, ['by' => $registrar->email, 'note' => $note]);

        return $submission;
    }

    /**
     * Publish approved results: immutable per-student snapshots students see from here on.
     * This is the ONLY path that writes published_results.
     */
    public function publish(ResultSubmission $submission, User $registrar): ResultSubmission
    {
        $this->assertRegistrar($registrar);
        $this->assertStatus($submission, ResultSubmissionStatus::Approved);

        DB::transaction(function () use ($submission, $registrar): void {
            $offering = $submission->offering()->with('course')->firstOrFail();
            $publishedAt = now();

            $rows = CourseScore::query()
                ->where('course_offering_id', $offering->id)
                ->whereNotNull('ca_score')
                ->whereNotNull('exam_score')
                ->with('student')
                ->get();

            foreach ($rows as $row) {
                $total = round(($row->ca_score ?? 0) + ($row->exam_score ?? 0), 2);

                PublishedResult::create([
                    'result_submission_id' => $submission->id,
                    'course_offering_id' => $offering->id,
                    'semester_id' => $offering->semester_id,
                    'student_id' => $row->student_id,
                    'ca_score' => $row->ca_score,
                    'exam_score' => $row->exam_score,
                    'total' => $total,
                    'grade_letter' => GradeScale::letterFor($total),
                    'grade_point' => GradeScale::pointFor($total),
                    'is_passed' => GradeScale::isPassed($total),
                    'published_at' => $publishedAt,
                ]);

                $row->student->notify(new PortalNotice(
                    title: 'Official result published — '.$offering->course->code,
                    body: 'Your official result for '.$offering->course->title.' ('.$offering->course->code.') has been published: grade '.GradeScale::letterFor($total).'. View it under Results.',
                    url: '/student/results',
                ));
            }

            $submission->forceFill([
                'status' => ResultSubmissionStatus::Published,
                'reviewed_by' => $registrar->id,
                'reviewed_at' => $submission->reviewed_at ?? $publishedAt,
                'published_at' => $publishedAt,
            ])->save();

            AuditLog::record('results.published', $offering, [
                'by' => $registrar->email,
                'students' => $rows->count(),
            ]);
        });

        return $submission;
    }

    public function submissionFor(CourseOffering $offering): ?ResultSubmission
    {
        return ResultSubmission::where('course_offering_id', $offering->id)->first();
    }

    private function assertRegistrar(User $user): void
    {
        if (! $user->hasRole(UserRole::Registrar) && ! $user->isSuperAdmin()) {
            abort(403);
        }
    }

    private function assertStatus(ResultSubmission $submission, ResultSubmissionStatus $expected): void
    {
        if (! $submission->statusIs($expected)) {
            throw ValidationException::withMessages([
                'results' => "Expected results in state [{$expected->label()}] but found [{$submission->status->label()}].",
            ]);
        }
    }
}

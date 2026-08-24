<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Timed quiz attempts. The clock is the server's: started_at is recorded when
 * an attempt opens, and submissions past duration + grace are refused.
 */
class QuizController extends Controller
{
    /** Grace beyond the nominal duration, absorbing network latency. */
    private const GRACE_SECONDS = 60;

    public function show(CourseOffering $offering, Quiz $quiz): View|RedirectResponse
    {
        Gate::authorize('view', $offering);
        abort_unless($quiz->course_offering_id === $offering->id, 404);

        $user = auth()->user();
        $managing = $user->can('manage', $offering);

        // Lecturers see a read-only summary of the instrument.
        if ($managing) {
            return view('lms.quiz-manage', [
                'offering' => $offering->load('course'),
                'quiz' => $quiz->load('questions', 'attempts.student'),
                'totalPoints' => $quiz->totalPoints(),
            ]);
        }

        abort_unless($quiz->published_at !== null && $quiz->isAvailable(), 404, 'This quiz is not available.');

        $attempt = $quiz->attempts()->where('student_id', $user->id)->latest('id')->first();

        // An in-progress attempt resumes; a submitted one shows results.
        if ($attempt !== null) {
            return $attempt->isSubmitted()
                ? redirect()->route('courses.quiz.result', [$offering, $quiz])
                : redirect()->route('courses.quiz.take', [$offering, $quiz]);
        }

        return view('lms.quiz-start', [
            'offering' => $offering->load('course'),
            'quiz' => $quiz->load('questions'),
            'attemptsUsed' => $quiz->attempts()->where('student_id', $user->id)->count(),
        ]);
    }

    /** Open (or resume) an attempt and hand the student the question paper. */
    public function start(Request $request, CourseOffering $offering, Quiz $quiz): RedirectResponse
    {
        abort_if($request->user()->can('manage', $offering), 403);
        Gate::authorize('view', $offering);
        abort_unless($quiz->course_offering_id === $offering->id, 404);
        abort_unless($quiz->isAvailable(), 403, 'This quiz is not open.');

        $existing = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->latest('id')->first();

        // Resume rather than burn another attempt.
        if ($existing !== null && ! $existing->isSubmitted()) {
            return redirect()->route('courses.quiz.take', [$offering, $quiz]);
        }

        if ($existing !== null || $quiz->max_attempts > 0) {
            $used = $quiz->attempts()->where('student_id', $request->user()->id)->count();

            if ($used >= max(1, $quiz->max_attempts)) {
                abort(403, 'You have already used your attempt'.(max(1, $quiz->max_attempts) > 1 ? 's' : '').' for this quiz.');
            }
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $request->user()->id,
            'started_at' => now(),
        ]);

        return redirect()->route('courses.quiz.take', [$offering, $quiz, $attempt]);
    }

    public function take(Request $request, CourseOffering $offering, Quiz $quiz): View
    {
        Gate::authorize('view', $offering);

        $attempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->latest('id')->firstOrFail();

        abort_if($attempt->isSubmitted(), 403, 'That attempt has already been submitted.');

        $deadline = $attempt->started_at->copy()->addMinutes($quiz->duration_minutes);

        return view('lms.quiz-take', [
            'offering' => $offering->load('course'),
            'quiz' => $quiz->load('questions'),
            'attempt' => $attempt,
            'deadline' => $deadline,
            'secondsLeft' => max(0, now()->diffInSeconds($deadline, false)),
            'expired' => now()->isAfter($deadline),
        ]);
    }

    /** Auto-score on submission; nothing is written past the deadline + grace. */
    public function submitAttempt(Request $request, CourseOffering $offering, Quiz $quiz, QuizAttempt $attempt): RedirectResponse|View
    {
        $user = $request->user();
        abort_unless($attempt->student_id === $user->id && $attempt->quiz_id === $quiz->id, 403);
        abort_if($attempt->isSubmitted(), 403, 'Already submitted.');

        $answers = $request->input('answers', []);
        $questions = $quiz->questions()->get()->keyBy('id');

        $deadline = $attempt->started_at->copy()->addMinutes($quiz->duration_minutes)->addSeconds(self::GRACE_SECONDS);

        if (now()->isAfter($deadline)) {
            // Close the attempt without a score — honest refusal beats a fake one.
            $attempt->forceFill(['submitted_at' => now()])->save();

            return view('lms.quiz-expired', [
                'offering' => $offering->load('course'),
                'quiz' => $quiz,
            ])->with('status', 'The time limit passed before your answers arrived.');
        }

        $score = 0.0;

        DB::transaction(function () use ($attempt, $questions, $answers, &$score): void {
            foreach ($questions as $question) {
                $response = $answers[$question->id] ?? null;

                // Normalise checkbox groups: unchecked boxes arrive absent → empty.
                if ($question->type === 'multi_choice' && $response === null) {
                    $response = [];
                }

                [$correct, $points] = $question->grade($response);

                $attempt->answers()->updateOrCreate(
                    ['quiz_question_id' => $question->id],
                    [
                        'response' => is_array($response) ? array_values($response) : $response,
                        'is_correct' => $correct,
                        'awarded_points' => $points,
                    ],
                );

                $score += $points;
            }

            $attempt->forceFill([
                'submitted_at' => now(),
                'score' => round($score, 2),
            ])->save();
        });

        return redirect()->route('courses.quiz.result', [$offering, $quiz]);
    }

    public function result(Request $request, CourseOffering $offering, Quiz $quiz): View
    {
        Gate::authorize('view', $offering);

        $attempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->whereNotNull('submitted_at')
            ->with(['answers.question'])
            ->latest('id')
            ->firstOrFail();

        return view('lms.quiz-result', [
            'offering' => $offering->load('course'),
            'quiz' => $quiz->load('questions'),
            'attempt' => $attempt,
            'totalPoints' => $quiz->totalPoints(),
        ]);
    }
}

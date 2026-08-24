<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\ResultSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\CourseScore;
use App\Models\Semester;
use App\Services\ResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResultsController extends Controller
{
    public function __construct(private readonly ResultService $results) {}

    /** Lecturer's offerings with their result-submission status. */
    public function index(): View
    {
        $user = auth()->user();

        $offerings = $user->taughtOfferings()
            ->with(['course', 'semester.session'])
            ->orderByDesc('semester_id')
            ->get()
            ->each(fn (CourseOffering $offering) => $offering->resultStatus = $this->results->submissionFor($offering)?->status);

        return view('lecturer.results-index', [
            'offerings' => $offerings,
            'currentSemesterId' => Semester::where('is_current', true)->value('id'),
        ]);
    }

    /** Gradebook editor for one offering's component scores. */
    public function gradebook(CourseOffering $offering): View
    {
        Gate::authorize('grade', $offering);

        $students = $offering->enrolledStudents()
            ->with(['studentProfile'])
            ->orderBy('name')
            ->get()
            ->map(function ($student) use ($offering) {
                $score = CourseScore::query()
                    ->where('course_offering_id', $offering->id)
                    ->where('student_id', $student->id)
                    ->first();

                return (object) [
                    'student' => $student,
                    'ca' => $score?->ca_score,
                    'exam' => $score?->exam_score,
                ];
            });

        return view('lecturer.gradebook', [
            'offering' => $offering->load('course'),
            'rows' => $students,
            'submission' => $this->results->submissionFor($offering),
            'locked' => $this->results->submissionFor($offering)?->statusIs(
                ResultSubmissionStatus::Approved,
                ResultSubmissionStatus::Published,
            ),
            'caMax' => ResultService::CA_MAX,
            'examMax' => ResultService::EXAM_MAX,
        ]);
    }

    public function saveScores(Request $request, CourseOffering $offering): RedirectResponse
    {
        Gate::authorize('grade', $offering);

        try {
            $written = $this->results->saveScores(
                $request->user(),
                $offering,
                array_slice((array) $request->input('scores', []), 0, 500),
            );
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return back()->with('status', "Score sheet saved ({$written} students).");
    }

    public function submit(Request $request, CourseOffering $offering): RedirectResponse
    {
        Gate::authorize('grade', $offering);

        try {
            $this->results->submit($request->user(), $offering);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return redirect()->route('lecturer.results')
            ->with('status', 'Provisional results submitted to the registry for approval.');
    }
}

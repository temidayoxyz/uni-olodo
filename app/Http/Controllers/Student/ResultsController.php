<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PublishedResult;
use App\Models\Semester;
use App\Support\GradeScale;
use Illuminate\View\View;

class ResultsController extends Controller
{
    /**
     * Official results only, grouped by session → semester, with honest GPA math.
     * Provisional (unpublished) figures never appear here.
     */
    public function index(): View
    {
        $results = PublishedResult::query()
            ->where('student_id', auth()->id())
            ->with(['offering.course', 'semester.session'])
            ->get()
            ->sortBy(fn ($r) => [$r->semester->session->starts_on, $r->semester->number]);

        // Group: session name → semester → rows + computed GPA.
        $grouped = $results->groupBy(fn ($r) => $r->semester->session->name)
            ->map(function ($sessionResults) {
                return $sessionResults->groupBy(fn ($r) => $r->semester->name)
                    ->map(function ($rows) {
                        return [
                            'semester' => $rows->first()->semester,
                            'rows' => $rows->values(),
                            'gpa' => GradeScale::gpa($rows->map(fn ($r) => [
                                'total' => (float) $r->total,
                                'credit_units' => $r->offering->course->credit_units,
                            ])),
                        ];
                    });
            });

        $cgpa = GradeScale::gpa($results->map(fn ($r) => [
            'total' => (float) $r->total,
            'credit_units' => $r->offering->course->credit_units,
        ]));

        $currentSemester = Semester::where('is_current', true)->first();

        return view('student.results', [
            'grouped' => $grouped,
            'cgpa' => $cgpa,
            'creditsEarned' => $results->filter(fn ($r) => $r->is_passed)->sum(fn ($r) => $r->offering->course->credit_units),
            'currentSemester' => $currentSemester,
            'hasProvisionalNote' => $currentSemester !== null,
        ]);
    }

    /** Print-oriented unofficial transcript; same official data, different dress. */
    public function transcript(): View
    {
        $results = PublishedResult::query()
            ->where('student_id', auth()->id())
            ->with(['offering.course.department', 'semester.session'])
            ->orderBy('published_results.published_at')
            ->get();

        $user = auth()->user()->load('studentProfile.programme.department.faculty');

        return view('student.transcript', [
            'user' => $user,
            'profile' => $user->studentProfile,
            'results' => $results,
            'cgpa' => GradeScale::gpa($results->map(fn ($r) => [
                'total' => (float) $r->total,
                'credit_units' => $r->offering->course->credit_units,
            ])),
            'generatedAt' => now(),
        ]);
    }
}

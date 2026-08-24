<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PublishedResult;
use App\Models\Semester;
use App\Support\GradeScale;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AcademicsController extends Controller
{
    /** Programme record, current standing, and quick paths into results/registration. */
    public function index(): View
    {
        $user = auth()->user()->load('studentProfile.programme.department.faculty', 'studentProfile.adviser');
        $profile = $user->studentProfile;
        $semester = Semester::where('is_current', true)->with('session')->first();

        $registration = $user->registrations()
            ->where('semester_id', $semester?->id)
            ->with('items.offering.course')
            ->first();

        $registeredCredits = (int) ($registration?->items
            ->where('status', 'registered')
            ->sum(fn ($item) => $item->offering->course->credit_units) ?? 0);

        $graded = PublishedResult::query()
            ->where('student_id', $user->id)
            ->join('course_offerings', 'course_offerings.id', '=', 'published_results.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->get(['published_results.total', 'courses.credit_units']);

        // Programme progress: semesters completed of the programme's duration, by official history.
        $semestersWithResults = PublishedResult::query()
            ->where('student_id', $user->id)
            ->distinct()
            ->count('semester_id');

        return view('student.academics', [
            'profile' => $profile,
            'semester' => $semester,
            'registration' => $registration,
            'registeredCredits' => $registeredCredits,
            'cgpa' => GradeScale::gpa($graded->map(fn ($r) => [
                'total' => (float) $r->total,
                'credit_units' => $r->credit_units,
            ])),
            'creditsEarned' => DB::table('published_results')
                ->where('student_id', $user->id)
                ->where('is_passed', true)
                ->join('course_offerings', 'course_offerings.id', '=', 'published_results.course_offering_id')
                ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
                ->sum('courses.credit_units'),
            'semestersCompleted' => $semestersWithResults,
        ]);
    }
}

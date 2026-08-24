<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\User;
use Illuminate\View\View;

class MyCoursesController extends Controller
{
    /** One index for both roles: students see enrolments, lecturers see assignments. */
    public function index(): View
    {
        $user = auth()->user();
        $semester = Semester::where('is_current', true)->with('session')->first();

        $offerings = $user->role->isStaff()
            ? $user->taughtOfferings()->where('semester_id', $semester?->id)->with(['course', 'schedules'])->get()
            : self::enrolledOfferings($user, $semester?->id);

        return view('lms.my-courses', [
            'semester' => $semester,
            'offerings' => $offerings,
            'teaching' => $user->role->isStaff(),
        ]);
    }

    public static function enrolledOfferings(User $user, ?int $semesterId): mixed
    {
        return CourseOffering::query()
            ->select('course_offerings.*')
            ->join('registration_items', 'registration_items.course_offering_id', '=', 'course_offerings.id')
            ->join('registrations', 'registrations.id', '=', 'registration_items.registration_id')
            ->where('registrations.student_id', $user->id)
            ->where('registrations.semester_id', $semesterId)
            ->where('registrations.status', 'approved')
            ->where('registration_items.status', 'registered')
            ->with(['course', 'lecturer'])
            ->orderBy('course_offerings.id')
            ->get();
    }
}

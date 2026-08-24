<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Registration;
use App\Models\RegistrationItem;
use App\Models\Semester;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $registrations) {}

    public function index(Request $request): View
    {
        $student = $request->user()->load('studentProfile.programme.department');
        $semester = Semester::where('is_current', true)->with('session')->first();

        $registration = Registration::query()
            ->where('student_id', $student->id)
            ->where('semester_id', $semester?->id)
            ->latest()->first();

        $basket = $this->registrations->basket($student, $semester);
        $credits = (int) $basket->sum(fn ($o) => $o->course->credit_units);

        // Offerings relevant to this student: their level ±100, open, not in basket.
        $level = $student->studentProfile?->level ?? 100;
        $available = CourseOffering::query()
            ->select('course_offerings.*')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->where('semester_id', $semester?->id)
            ->where('status', 'open')
            ->where('is_active', true)
            ->whereBetween('courses.level', [max(100, $level - 100), $level])
            ->whereNotIn('course_offerings.id', $basket->pluck('id'))
            ->with(['course.prerequisites', 'course.department', 'lecturer', 'schedules'])
            ->orderBy('courses.code')
            ->get();

        // Pre-compute rule outcomes per available offering so the UI can explain itself.
        $checks = $available->mapWithKeys(function (CourseOffering $offering) use ($student, $semester) {
            return [$offering->id => $this->registrations->checkAdd($student, $semester, $offering)];
        });

        return view('student.registration', [
            'semester' => $semester,
            'registration' => $registration,
            'basket' => $basket,
            'basketItems' => $registration?->activeItems()->get()->keyBy('course_offering_id'),
            'credits' => $credits,
            'maxCredits' => RegistrationService::MAX_CREDITS,
            'available' => $available,
            'checks' => $checks,
            'windowOpen' => $semester?->registrationIsOpen() ?? false,
            'profile' => $student->studentProfile,
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'offering' => ['required', 'integer', 'exists:course_offerings,id'],
        ]);

        $semester = Semester::where('is_current', true)->firstOrFail();
        $offering = CourseOffering::with('course.prerequisites', 'schedules')->findOrFail($validated['offering']);

        try {
            $this->registrations->addToBasket($request->user(), $semester, $offering);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors()['registration'] ?? [])->all()));
        }

        return back()->with('status', $offering->course->code.' added to your registration.');
    }

    public function remove(Request $request, RegistrationItem $item): RedirectResponse
    {
        try {
            $this->registrations->removeFromBasket($request->user(), Semester::where('is_current', true)->firstOrFail(), $item);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors()['registration'] ?? [])->all()));
        }

        return back()->with('status', 'Course removed from your registration.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $semester = Semester::where('is_current', true)->firstOrFail();

        try {
            $this->registrations->submit($request->user(), $semester);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors()['registration'] ?? ['Submission failed.'])->all()));
        }

        return redirect()->route('student.registration')
            ->with('status', 'Registration submitted. You will be notified when the registry approves it.');
    }
}

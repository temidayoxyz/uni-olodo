<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Academic structure management: registry-owned CRUD over the
 * faculty → department → programme → course tree and the calendar.
 */
class AcademicsController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-structure');

        return view('admin.academics.index', [
            'faculties' => Faculty::query()
                ->with(['departments.programmes', 'departments.courses'])
                ->withCount(['departments'])
                ->orderBy('name')->get(),
            'courseCount' => Course::count(),
            'programmeCount' => Programme::count(),
        ]);
    }

    // --- Programmes ----------------------------------------------------------

    public function createProgramme(): View
    {
        Gate::authorize('manage-structure');

        return view('admin.academics.programme-form', [
            'programme' => new Programme,
            'departments' => Department::with('faculty')->orderBy('name')->get(),
        ]);
    }

    public function storeProgramme(Request $request): RedirectResponse
    {
        Gate::authorize('manage-structure');

        $validated = $this->validateProgramme($request);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['tuition_per_session'] = (int) round($validated['tuition_per_session_naira'] * 100);
        unset($validated['tuition_per_session_naira']);

        Programme::create($validated);

        return redirect()->route('admin.academics')
            ->with('status', "Programme {$validated['name']} created.");
    }

    public function editProgramme(Programme $programme): View
    {
        Gate::authorize('manage-structure');

        return view('admin.academics.programme-form', [
            'programme' => $programme,
            'departments' => Department::with('faculty')->orderBy('name')->get(),
        ]);
    }

    public function updateProgramme(Request $request, Programme $programme): RedirectResponse
    {
        Gate::authorize('manage-structure');

        $validated = $this->validateProgramme($request, $programme);
        $validated['tuition_per_session'] = (int) round($validated['tuition_per_session_naira'] * 100);
        unset($validated['tuition_per_session_naira']);

        $programme->update($validated);

        return redirect()->route('admin.academics')
            ->with('status', 'Programme updated.');
    }

    // --- Courses -------------------------------------------------------------

    public function courses(Request $request): View
    {
        Gate::authorize('manage-structure');

        $departmentId = $request->query('department');

        return view('admin.academics.courses', [
            'courses' => Course::query()
                ->with('department.faculty')
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->orderBy('code')
                ->paginate(20)
                ->withQueryString(),
            'departments' => Department::orderBy('name')->get(),
            'activeDepartment' => $departmentId ? Department::find($departmentId) : null,
        ]);
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        Gate::authorize('manage-structure');

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:12', 'unique:courses,code'],
            'title' => ['required', 'string', 'max:191'],
            'credit_units' => ['required', 'integer', 'min:1', 'max:6'],
            'level' => ['required', 'integer', 'in:100,200,300,400,500'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        Course::create($validated + ['is_active' => true]);

        return back()->with('status', "Course {$validated['code']} added to the catalogue.");
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage-structure');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'credit_units' => ['required', 'integer', 'min:1', 'max:6'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $course->update($validated);

        return back()->with('status', "{$course->code} updated.");
    }

    /** Toggle catalogue activation without a full edit round-trip. */
    public function toggleCourse(Course $course): RedirectResponse
    {
        Gate::authorize('manage-structure');

        $course->update(['is_active' => ! $course->is_active]);

        return back()->with('status', $course->is_active ? "{$course->code} reactivated." : "{$course->code} deactivated — it will no longer be offered or registerable.");
    }

    // --- Calendar --------------------------------------------------------------

    public function calendar(): View
    {
        Gate::authorize('manage-structure');

        return view('admin.academics.calendar', [
            'sessions' => AcademicSession::query()
                ->with('semesters')
                ->orderByDesc('starts_on')
                ->get(),
        ]);
    }

    /** Update a semester's registration window (the registrar's term control). */
    public function updateSemesterWindow(Request $request, Semester $semester): RedirectResponse
    {
        Gate::authorize('manage-structure');

        $validated = $request->validate([
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at'],
        ], [
            'registration_closes_at.after' => 'The closing date must fall after the opening date.',
        ]);

        $semester->update([
            'registration_opens_at' => $validated['registration_opens_at'] ?? null,
            'registration_closes_at' => $validated['registration_closes_at'] ?? null,
        ]);

        AuditLog::record('calendar.window_updated', $semester, $validated);

        return back()->with('status', "Registration window for {$semester->name} updated.");
    }

    // --- helpers -----------------------------------------------------------------

    private function validateProgramme(Request $request, ?Programme $existing = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:16', 'unique:programmes,code'.($existing ? ','.$existing->id : '')],
            'award' => ['required', 'in:bsc,beng'],
            'duration_semesters' => ['required', 'integer', 'min:2', 'max:12'],
            'description' => ['nullable', 'string', 'max:3000'],
            'entry_requirements' => ['nullable', 'string', 'max:2000'],
            'tuition_per_session_naira' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}

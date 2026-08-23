<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use Illuminate\View\View;

class PublicAcademicsController extends Controller
{
    public function index(): View
    {
        return view('public.academics', [
            'faculties' => Faculty::query()
                ->with(['departments' => fn ($q) => $q->withCount('programmes')->with(['programmes' => fn ($p) => $p->where('is_active', true)->orderBy('name')])])
                ->orderBy('name')
                ->get(),
            'currentSemester' => Semester::where('is_current', true)->with('session')->first(),
            'programmeCount' => Programme::where('is_active', true)->count(),
        ]);
    }

    public function show(Programme $programme): View
    {
        $programme->load(['department.faculty', 'department.courses' => fn ($q) => $q->where('is_active', true)->where('level', '<=', 300)->orderBy('level')->orderBy('code')]);

        return view('public.programme', [
            'programme' => $programme,
            'relatedProgrammes' => Programme::query()
                ->where('department_id', $programme->department_id)
                ->whereKeyNot($programme->id)
                ->where('is_active', true)
                ->get(),
        ]);
    }
}

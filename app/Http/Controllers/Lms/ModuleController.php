<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\CourseOffering;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function show(CourseOffering $offering, CourseModule $module): View
    {
        Gate::authorize('view', $offering);

        abort_unless($module->course_offering_id === $offering->id, 404);
        abort_if($module->published_at === null && ! auth()->user()->can('manage', $offering), 404);

        $contents = $module->contents()
            ->when(auth()->user()->cannot('manage', $offering), fn ($q) => $q->whereNotNull('published_at'))
            ->orderBy('position')
            ->get();

        return view('lms.module', [
            'offering' => $offering->load('course'),
            'module' => $module,
            'contents' => $contents,
            'managing' => auth()->user()->can('manage', $offering),
        ]);
    }
}

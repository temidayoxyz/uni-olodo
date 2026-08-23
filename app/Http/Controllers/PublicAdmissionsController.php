<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Semester;
use Illuminate\View\View;

class PublicAdmissionsController extends Controller
{
    public function __invoke(): View
    {
        return view('public.admissions-live', [
            'intakeSession' => AcademicSession::query()->orderByDesc('starts_on')->first(),
            'registrationSemester' => Semester::where('is_current', true)->first(),
        ]);
    }
}

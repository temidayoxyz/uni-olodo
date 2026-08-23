<?php

namespace App\Http\Controllers;

use App\Models\CampusEvent;
use App\Models\Faculty;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Semester;
use Illuminate\View\View;

class PublicHomeController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home', [
            'faculties' => Faculty::query()
                ->with(['departments.programmes' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('name')
                ->get(),
            'programmes' => Programme::query()->where('is_active', true),
            'facultyCount' => Faculty::count(),
            'news' => NewsArticle::query()->published()->latest('published_at')->take(3)->get(),
            'events' => CampusEvent::query()->public()->upcoming()->orderBy('starts_at')->take(3)->get(),
            'currentSemester' => Semester::where('is_current', true)->with('session')->first(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\CourseContent;
use App\Models\CourseOffering;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Course materials live in private storage; downloads are policy-gated.
 */
class MaterialController extends Controller
{
    public function download(CourseOffering $offering, CourseContent $content): StreamedResponse
    {
        Gate::authorize('view', $offering);

        abort_unless($content->module->course_offering_id === $offering->id, 404);
        abort_unless($content->type === 'file' && filled($content->file_path), 404);
        abort_unless(Storage::disk('local')->exists($content->file_path), 404);

        return Storage::disk('local')->download(
            $content->file_path,
            $content->file_name ?? basename($content->file_path),
        );
    }
}

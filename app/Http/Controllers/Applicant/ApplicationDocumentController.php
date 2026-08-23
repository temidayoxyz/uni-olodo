<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationDocumentController extends Controller
{
    private const MAX_BYTES = 4 * 1024 * 1024; // 4 MB

    public function store(Request $request): RedirectResponse
    {
        /** @var Application|null $application */
        $application = Application::query()->where('user_id', $request->user()->id)->latest()->first();
        abort_unless($application !== null && $application->statusIs(ApplicationStatus::Draft, ApplicationStatus::MoreInfoRequired), 403, 'Documents can only be changed before submission.');

        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(ApplicationDocument::TYPES))],
            'document' => ['required', 'file', 'max:'.(self::MAX_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png'],
        ], [
            'document.mimes' => 'Upload a PDF or image (jpg/png).',
            'document.max' => 'Files must be 4 MB or smaller.',
        ]);

        // One file per type: replacing removes the previous version.
        $application->documents()->where('type', $validated['type'])->get()->each(function (ApplicationDocument $document): void {
            Storage::disk('local')->delete($document->stored_path);
            $document->delete();
        });

        $file = $request->file('document');

        $application->documents()->create([
            'type' => $validated['type'],
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $file->store("applications/{$application->id}", 'local'),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'verification' => 'pending',
        ]);

        return back()->with('status', ucfirst($application->documents()->where('type', $validated['type'])->first()?->typeLabel() ?? 'Document').' uploaded. It will be verified by the admissions office.');
    }

    /** Applicants download their OWN uploads only; files live outside the public root. */
    public function download(Request $request, ApplicationDocument $document): StreamedResponse
    {
        abort_unless($document->application->user_id === $request->user()->id, 403);

        abort_unless(Storage::disk('local')->exists($document->stored_path), 404);

        return Storage::disk('local')->download($document->stored_path, $document->original_name);
    }
}

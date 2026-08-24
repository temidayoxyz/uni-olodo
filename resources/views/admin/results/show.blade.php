<x-layout.portal title="Review results">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('admin.results.index') }}" class="hover:text-pine">Results</a>
        <span aria-hidden="true"> / </span>
        <span class="tabular-nums">{{ $offering->course->code }}</span>
    </nav>

    <x-ui.page-header
        :title="'Provisional results — '.$offering->course->code.' '.$offering->course->title"
        :subtitle="$offering->semester?->session?->name.' '.$offering->semester?->name.' · submitted by '.$submission->submitter?->name.' '.$submission->submitted_at?->diffForHumans()" />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    <div class="table-wrap">
        <table class="table min-w-[36rem]">
            <thead>
                <tr>
                    <th scope="col">Student</th>
                    <th scope="col" class="text-center">CA (40)</th>
                    <th scope="col" class="text-center">Exam (60)</th>
                    <th scope="col" class="text-center">Total</th>
                    <th scope="col" class="text-center">Proposed grade</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $entry)
                    <tr>
                        <td>
                            <p class="font-medium">{{ $entry->row->student->name }}</p>
                            <p class="text-xs tabular-nums text-ink-faint">{{ $entry->row->student->studentProfile?->matric_number }}</p>
                        </td>
                        <td class="num">{{ number_format($entry->row->ca_score, 1) }}</td>
                        <td class="num">{{ number_format($entry->row->exam_score, 1) }}</td>
                        <td class="num font-semibold">{{ number_format($entry->total ?? 0, 1) }}</td>
                        <td class="text-center"><span class="{{ \App\Support\GradeScale::isPassed($entry->total) ? 'badge-success' : 'badge-danger' }}">{{ $entry->letter }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel mt-6 px-5 py-5">
        @if ($submission->statusIs(\App\Enums\ResultSubmissionStatus::Submitted))
            <h2 class="text-sm font-semibold">Registry decision</h2>
            <div class="mt-4 flex flex-wrap items-start gap-3">
                <form method="POST" action="{{ route('admin.results.approve', $submission) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Approve results</button>
                </form>

                <details class="relative">
                    <summary class="btn-secondary cursor-pointer list-none [&::-webkit-details-marker]:hidden">Return for corrections…</summary>
                    <form method="POST" action="{{ route('admin.results.return', $submission) }}"
                          class="panel absolute z-20 mt-2 w-80 space-y-3 p-4 shadow-lg">
                        @csrf
                        <x-ui.textarea label="What must the lecturer correct?" name="note" rows="3" required />
                        <button type="submit" class="btn-danger btn-sm w-full">Return to lecturer</button>
                    </form>
                </details>

                <p class="ms-auto max-w-sm text-xs leading-relaxed text-ink-faint">
                    Approval confirms the figures. Publication is a separate step that writes permanent
                    official records and notifies every student.
                </p>
            </div>
        @elseif ($submission->statusIs(\App\Enums\ResultSubmissionStatus::Approved))
            <h2 class="text-sm font-semibold">Approved — ready to publish</h2>
            <p class="mt-1 text-xs text-ink-faint">Approved {{ $submission->reviewed_at?->diffForHumans() }}.</p>
            <form method="POST" action="{{ route('admin.results.publish', $submission) }}" class="mt-4">
                @csrf
                <button type="submit" class="btn-primary"
                        onclick="return confirm('Publish official results for all students on this sheet? This cannot be undone.')">
                    Publish official results
                </button>
            </form>
        @else
            <x-ui.alert type="success" title="Published {{ $submission->published_at?->diffForHumans() }}">
                Official records were written and students notified. This snapshot is immutable.
            </x-ui.alert>
        @endif
    </div>
</x-layout.portal>

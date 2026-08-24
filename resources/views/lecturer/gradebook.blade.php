<x-layout.portal :title="'Gradebook — '.$offering->course->code">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('lecturer.results') }}" class="hover:text-pine">Results</a>
        <span aria-hidden="true"> / </span>
        <span class="tabular-nums">{{ $offering->course->code }}</span>
    </nav>

    <x-ui.page-header
        :title="'Gradebook — '.$offering->course->code.' '.$offering->course->title"
        :subtitle="$offering->semester->session->name.' '.$offering->semester->name.' · CA out of '.number_format($caMax, 0).' · Examination out of '.number_format($examMax, 0)" />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    @if ($locked)
        <div class="mb-6">
            <x-ui.alert type="warning" title="Results are locked">
                These results have been {{ strtolower($submission->status->label()) }} by the registry and can no longer be edited.
            </x-ui.alert>
        </div>
    @endif

    @if ($submission)
        <div class="mb-6">
            <x-ui.alert type="{{ $submission->statusIs(\App\Enums\ResultSubmissionStatus::Returned) ? 'warning' : 'info' }}"
                        title="Submission status: {{ $submission->status->label() }}">
                @if ($submission->statusIs(\App\Enums\ResultSubmissionStatus::Returned))
                    {{ $submission->note }}
                    <span class="block mt-1 text-xs text-ink-faint">Correct the scores below and resubmit.</span>
                @else
                    Submitted {{ $submission->submitted_at?->diffForHumans() }} · awaiting registry action.
                @endif
            </x-ui.alert>
        </div>
    @endif

    <form method="POST" action="{{ route('courses.gradebook.save', $offering) }}">
        @csrf
        <div class="table-wrap">
            <table class="table min-w-[36rem]">
                <thead>
                    <tr>
                        <th scope="col">Student</th>
                        <th scope="col" class="text-center">CA ({{ number_format($caMax, 0) }})</th>
                        <th scope="col" class="text-center">Exam ({{ number_format($examMax, 0) }})</th>
                        <th scope="col" class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $row->student->name }}</p>
                                <p class="text-xs tabular-nums text-ink-faint">{{ $row->student->studentProfile?->matric_number }}</p>
                            </td>
                            <td class="num">
                                <input type="number" name="scores[{{ $row->student->id }}][ca]" value="{{ $row->ca }}"
                                       min="0" max="{{ $caMax }}" step="0.5" @disabled($locked)
                                       class="input mx-auto w-20 !py-1 text-center tabular-nums"
                                       aria-label="CA score for {{ $row->student->name }}">
                            </td>
                            <td class="num">
                                <input type="number" name="scores[{{ $row->student->id }}][exam]" value="{{ $row->exam }}"
                                       min="0" max="{{ $examMax }}" step="0.5" @disabled($locked)
                                       class="input mx-auto w-20 !py-1 text-center tabular-nums"
                                       aria-label="Examination score for {{ $row->student->name }}">
                            </td>
                            <td class="num font-semibold">
                                @if ($row->ca !== null && $row->exam !== null)
                                    {{ number_format($row->ca + $row->exam, 1) }}
                                @else
                                    <span class="text-ink-faint">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-sm text-ink-soft">No students are enrolled in this offering.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @unless ($locked || $rows->isEmpty())
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <button type="submit" class="btn-secondary"><x-lucide-save class="size-4" /> Save score sheet</button>

                <form method="POST" action="{{ route('courses.results.submit', $offering) }}">
                    @csrf
                    <button type="submit" class="btn-primary"
                            title="Requires every enrolled student to have complete CA + examination scores">
                        Submit provisional results for approval
                    </button>
                </form>
            </div>
            <p class="mt-2 text-xs text-ink-faint">
                Submission sends the complete sheet to the registry. After approval you cannot edit; publication makes grades official and visible to students.
            </p>
        @endunless
    </form>
</x-layout.portal>

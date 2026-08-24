<x-layout.portal :title="'Grading — '.$assignment->title">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.index') }}" class="hover:text-pine">My courses</a>
        <span aria-hidden="true"> / </span>
        <a href="{{ route('courses.home', $offering) }}" class="tabular-nums hover:text-pine">{{ $offering->course->code }}</a>
        <span aria-hidden="true"> / </span>
        <a href="{{ route('courses.assignments', $offering) }}" class="hover:text-pine">Assignments</a>
    </nav>

    <x-ui.page-header
        title="Grading queue"
        :subtitle="$offering->course->code.' · '.$assignment->title.' · '.number_format($enrolledCount).' enrolled, '.$submissions->count().' submitted ('.$submissions->whereNull('graded_at')->count().' awaiting grades)'" />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif

    @if ($submissions->isEmpty())
        <x-ui.empty-state icon="inbox" title="No submissions yet">
            Submissions will appear here as students upload their work.
        </x-ui.empty-state>
    @else
        <div class="space-y-4">
            @foreach ($submissions as $submission)
                @php $late = $submission->submitted_at->isAfter($assignment->due_at); @endphp
                <article class="panel px-5 py-4 {{ $submission->graded_at ? 'opacity-90' : '' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1 basis-64">
                            <p class="text-sm font-semibold">
                                {{ $submission->student->name }}
                                <span class="ms-1 text-xs font-normal text-ink-faint tabular-nums">{{ $submission->student->studentProfile?->matric_number }}</span>
                            </p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-faint">
                                <span>Submitted {{ $submission->submitted_at->format('D j M, g:i a') }}</span>
                                @if ($late)<span class="badge-danger badge">Late</span>@endif
                                <a href="{{ route('courses.submission.download', [$offering, $submission]) }}" class="font-medium text-pine hover:underline">Open file ({{ number_format($submission->size_bytes / 1024) }} KB)</a>
                            </p>
                            @if ($submission->note)
                                <p class="mt-1.5 rounded-[var(--radius-control)] bg-surface-dim px-3 py-1.5 text-xs italic text-ink-soft">Student note: “{{ $submission->note }}”</p>
                            @endif
                        </div>

                        <div class="w-full max-w-sm shrink-0">
                            @if ($submission->graded_at)
                                <p class="flex items-baseline justify-end gap-2 text-sm">
                                    <span class="badge-success tabular-nums">{{ number_format($submission->score, 0) }}/{{ number_format($assignment->points, 0) }}</span>
                                    <span class="text-ink-faint">graded {{ $submission->graded_at->diffForHumans() }}</span>
                                </p>
                                <p class="mt-1.5 line-clamp-3 text-end text-xs leading-relaxed text-ink-soft">{{ $submission->feedback }}</p>
                            @else
                                <form method="POST" action="{{ route('courses.grading.store', [$offering, $submission]) }}"
                                      class="flex flex-wrap items-start justify-end gap-2">
                                    @csrf
                                    <label class="sr-only" for="score-{{ $submission->id }}">Score for {{ $submission->student->name }}</label>
                                    <input type="number" name="score" id="score-{{ $submission->id }}" required min="0" max="{{ $assignment->points }}" step="0.5"
                                           placeholder="/{{ number_format($assignment->points, 0) }}"
                                           class="input w-24 !py-1.5 text-sm tabular-nums">
                                    <label class="sr-only" for="feedback-{{ $submission->id }}">Feedback for {{ $submission->student->name }}</label>
                                    <textarea name="feedback" id="feedback-{{ $submission->id }}" required rows="2"
                                              placeholder="Feedback to the student…"
                                              class="input w-full !py-1.5 text-sm sm:w-auto sm:min-w-56"></textarea>
                                    <button type="submit" class="btn-primary btn-sm mt-0.5">Release grade</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layout.portal>

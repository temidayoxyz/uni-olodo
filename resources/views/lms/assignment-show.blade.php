<x-layout.portal :title="$assignment->title.' — '.$offering->course->code">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.home', $offering) }}" class="tabular-nums hover:text-pine">{{ $offering->course->code }}</a>
        <span aria-hidden="true"> / </span>
        <a href="{{ route('courses.assignments', $offering) }}" class="hover:text-pine">Assignments</a>
        <span aria-hidden="true"> / </span>
        <span>{{ str($assignment->title)->limit(40) }}</span>
    </nav>

    <div class="mx-auto max-w-3xl">
        <x-ui.page-header :title="$assignment->title" />

        <article class="panel px-6 py-6">
            <dl class="grid grid-cols-3 gap-4 border-b border-line-soft pb-4 text-sm">
                <div><dt class="text-xs text-ink-faint">Due</dt>
                    <dd class="font-semibold tabular-nums">{{ $assignment->due_at->format('D j M, g:i a') }}</dd></div>
                <div><dt class="text-xs text-ink-faint">Points</dt>
                    <dd class="font-semibold tabular-nums">{{ number_format($assignment->points, 0) }}</dd></div>
                <div><dt class="text-xs text-ink-faint">Late window</dt>
                    <dd class="font-semibold">{{ $assignment->late_until ? 'until '.$assignment->late_until->format('j M' ) : 'None' }}</dd></div>
            </dl>

            <div class="rich-text mt-5">{!! str($assignment->instructions)->markdown() !!}</div>
        </article>

        @if ($managing)
            <a href="{{ route('courses.grading', [$offering, $assignment]) }}" class="btn-primary mt-6">
                Open grading queue
            </a>
        @else
            {{-- Student submission panel --}}
            <section class="panel mt-6 px-6 py-6" aria-labelledby="submit-heading">
                <h2 id="submit-heading" class="text-sm font-semibold">Your submission</h2>

                @if ($submission)
                    <ul class="mt-4 divide-y divide-line-soft rounded-[var(--radius-control)] border border-line bg-surface-dim px-4">
                        <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                            <span class="min-w-0 truncate font-medium">{{ $submission->original_name }}</span>
                            <span class="shrink-0 text-xs text-ink-faint tabular-nums">{{ number_format($submission->size_bytes / 1024) }} KB · {{ $submission->submitted_at->format('j M, g:i a') }}</span>
                            <a href="{{ route('courses.submission.download', [$offering, $submission]) }}" class="btn-secondary btn-sm">Download</a>
                        </li>
                    </ul>
                    @if ($submission->note)
                        <p class="mt-2 text-xs italic text-ink-faint">Your note: “{{ $submission->note }}”</p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-ink-soft">Nothing submitted yet.</p>
                @endif

                @if ($submission?->graded_at)
                    <div class="mt-5 border-t border-line-soft pt-5">
                        <p class="flex items-baseline gap-2 font-display text-2xl font-semibold tracking-tight">
                            {{ number_format($submission->score, 0) }}<span class="text-base font-normal text-ink-faint">/ {{ number_format($assignment->points, 0) }} points</span>
                        </p>
                        <p class="mt-1 text-xs text-ink-faint">Graded by {{ $submission->grader?->name }} · {{ $submission->graded_at->diffForHumans() }}</p>
                        <blockquote class="mt-3 rounded-[var(--radius-control)] border-s-2 border-pine-line bg-pine-tint/60 px-4 py-3 text-sm leading-relaxed text-ink">
                            {{ $submission->feedback }}
                        </blockquote>
                    </div>
                @elseif ($canSubmit)
                    <form method="POST" action="{{ route('courses.assignment.submit', [$offering, $assignment]) }}"
                          enctype="multipart/form-data" class="mt-5 space-y-4 border-t border-line-soft pt-5">
                        @csrf

                        <label class="block">
                            <span class="label">{{ $submission ? 'Replace file' : 'Upload your work' }} (PDF, DOC(X), ZIP or TXT · max 10 MB)</span>
                            <input type="file" name="file" required accept=".pdf,.zip,.doc,.docx,.txt"
                                   class="input file:mr-3 file:cursor-pointer file:rounded-[var(--radius-control)] file:border-0 file:bg-paper-deep file:px-3 file:py-1.5 file:text-xs file:font-semibold hover:file:bg-line">
                        </label>

                        <x-ui.textarea label="Note to your lecturer (optional)" name="note"
                                      :value="$submission?->note" rows="2" placeholder="Anything they should know about this submission…" />

                        <button type="submit" class="btn-primary">
                            {{ $submission ? 'Replace submission' : 'Submit assignment' }}
                        </button>
                        @unless ($assignment->isPastDue())
                            <p class="ms-3 inline-block align-middle text-xs text-ink-faint">You can replace it until the deadline.</p>
                        @endunless
                    </form>
                @elseif ($submission === null && $assignment->isPastDue())
                    <div class="mt-5"><x-ui.alert type="danger" title="The deadline has passed">Submissions closed {{ $assignment->due_at->diffForHumans() }}. Contact your lecturer immediately if you believe you have grounds for an extension.</x-ui.alert></div>
                @endif
            </section>
        @endif
    </div>
</x-layout.portal>

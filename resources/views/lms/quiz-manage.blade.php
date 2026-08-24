<x-layout.portal :title="$quiz->title.' — quiz overview'">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.home', $offering) }}" class="tabular-nums hover:text-pine">{{ $offering->course->code }}</a>
        <span aria-hidden="true"> / </span>
        <span>{{ $quiz->title }}</span>
    </nav>

    <x-ui.page-header :title="$quiz->title"
        :subtitle="$quiz->questions->count().' questions · '.$totalPoints.' points · '.$quiz->duration_minutes.' minutes · '.number_format($quiz->attempts->count()).' attempts submitted'" />

    <div class="mx-auto max-w-3xl space-y-6">
        <section class="panel px-6 py-5">
            <h2 class="text-sm font-semibold">Settings</h2>
            <dl class="mt-3 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div><dt class="text-xs text-ink-faint">Opens</dt><dd class="tabular-nums">{{ $quiz->available_from?->format('j M, g:i a') }}</dd></div>
                <div><dt class="text-xs text-ink-faint">Closes</dt><dd class="tabular-nums">{{ $quiz->available_until?->format('j M, g:i a') }}</dd></div>
                <div><dt class="text-xs text-ink-faint">Max attempts</dt><dd class="tabular-nums">{{ $quiz->max_attempts }}</dd></div>
                <div><dt class="text-xs text-ink-faint">Answer review</dt><dd>{{ $quiz->reveal_answers ? 'Shown after submit' : 'Hidden' }}</dd></div>
            </dl>
            @if ($quiz->instructions)
                <p class="mt-3 border-t border-line-soft pt-3 text-sm leading-relaxed text-ink-soft">{{ $quiz->instructions }}</p>
            @endif
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="text-sm font-semibold">Questions</h2></div>
            <ol class="divide-y divide-line-soft">
                @foreach ($quiz->questions as $question)
                    <li class="px-6 py-4">
                        <p class="text-sm"><span class="me-1.5 tabular-nums text-ink-faint">{{ $loop->iteration }}.</span>{{ $question->prompt }}
                            <span class="badge-neutral ms-1 tabular-nums">{{ $question->points }}pt</span>
                        </p>
                        @if ($question->options)
                            <ul class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-soft">
                                @foreach ($question->options as $option)
                                    <li class="{{ in_array($option['key'], $question->answers ?? []) ? 'font-semibold text-success' : '' }}">
                                        {{ $option['key'] }}) {{ $option['text'] }}{{ in_array($option['key'], $question->answers ?? []) ? ' ✓' : '' }}
                                    </li>
                                @endforeach
                            </ul>
                        @elseif ($question->type === 'short_answer')
                            <p class="mt-1.5 text-xs text-success">Accepted: {{ collect($question->answers ?? [])->implode(' / ') }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="panel px-6 py-5">
            <h2 class="text-sm font-semibold">Submitted attempts</h2>
            @forelse ($quiz->attempts->whereNotNull('submitted_at')->sortByDesc('score') as $attempt)
                <p class="mt-2 flex items-center justify-between border-b border-line-soft pb-2 text-sm last:border-b-0">
                    <span>{{ $attempt->student?->name }} <span class="text-xs text-ink-faint tabular-nums">{{ $attempt->student?->studentProfile?->matric_number }}</span></span>
                    <span class="font-semibold tabular-nums">{{ number_format($attempt->score, 1) }} / {{ number_format($totalPoints, 0) }}</span>
                </p>
            @empty
                <p class="mt-3 text-sm text-ink-soft">No submissions yet.</p>
            @endforelse
        </section>
    </div>
</x-layout.portal>

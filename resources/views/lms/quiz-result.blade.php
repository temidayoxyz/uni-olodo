<x-layout.portal :title="$quiz->title.' — result'">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.home', $offering) }}" class="tabular-nums hover:text-pine">{{ $offering->course->code }}</a>
        <span aria-hidden="true"> / </span>
        <span>{{ $quiz->title }}</span>
    </nav>

    @if (session('status'))<div class="mx-auto max-w-3xl mb-5"><x-ui.alert type="danger" title="Not accepted">{{ session('status') }}</x-ui.alert></div>@endif

    <div class="mx-auto max-w-3xl">
        <section class="panel px-6 py-6 text-center" aria-labelledby="score-heading">
            <h1 id="score-heading" class="text-sm font-semibold text-ink-faint">Your result — {{ $quiz->title }}</h1>
            <p class="mt-2 font-display text-5xl font-semibold tracking-tight tabular-nums">
                {{ number_format($attempt->score, 1) }}<span class="text-xl font-normal text-ink-faint"> / {{ number_format($totalPoints, 0) }}</span>
            </p>
            <p class="mt-1 text-xs text-ink-faint">Submitted {{ $attempt->submitted_at?->diffForHumans() }} · auto-scored</p>
        </section>

        @if ($quiz->reveal_answers)
            <ol class="mt-8 space-y-5">
                @foreach ($quiz->questions as $question)
                    @php
                        $answer = $attempt->answers->firstWhere('quiz_question_id', $question->id);
                        $correct = $answer?->is_correct;

                        // Readable rendering of the stored response for this question type.
                        $rawResponse = $answer?->response;
                        if ($rawResponse === null) {
                            $yourAnswer = '— (no answer)';
                        } elseif ($question->type === 'short_answer') {
                            $yourAnswer = is_array($rawResponse) ? implode(', ', $rawResponse) : (string) $rawResponse;
                        } else {
                            $options = collect($question->options ?? [])->keyBy('key');
                            $yourAnswer = collect(is_array($rawResponse) ? $rawResponse : [$rawResponse])
                                ->map(fn ($key) => $options[$key]['text'] ?? (string) $key)
                                ->implode('; ');
                        }
                    @endphp
                    <li class="panel px-6 py-5">
                        <p class="flex items-start justify-between gap-4 text-sm font-semibold">
                            <span>{{ $loop->iteration }}. {{ $question->prompt }}</span>
                            <span class="{{ $correct ? 'text-success' : 'text-danger' }} shrink-0 font-bold uppercase">{{ $correct ? '✓ correct' : '✗ incorrect' }}</span>
                        </p>

                        <dl class="mt-3 grid gap-x-8 gap-y-1.5 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-ink-faint">Your answer</dt>
                                <dd>{{ $yourAnswer }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-faint">Accepted answer</dt>
                                <dd class="font-medium">{{ collect($question->answers ?? [])->implode(', ') }}</dd>
                            </div>
                        </dl>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="mt-8 text-center text-sm text-ink-soft">Answer review is not enabled for this quiz.</p>
        @endif

        <div class="mt-8 text-center">
            <a href="{{ route('courses.home', $offering) }}" class="btn-secondary">Back to {{ $offering->course->code }}</a>
        </div>
    </div>
</x-layout.portal>

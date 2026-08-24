<x-layout.portal :title="$quiz->title.' — attempt'">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.home', $offering) }}" class="tabular-nums hover:text-pine">{{ $offering->course->code }}</a>
        <span aria-hidden="true"> / </span>
        <span>{{ $quiz->title }}</span>
    </nav>

    <div class="mx-auto max-w-3xl" x-data>
        <x-ui.page-header :title="$quiz->title"
            :subtitle="'Attempt started '.$attempt->started_at->format('g:i a').' · answers are graded on submission'" />

        <div class="panel sticky top-14 z-20 mb-5 flex items-center justify-between px-5 py-3"
             x-data="{ left: {{ $secondsLeft }} }"
             x-init="if (left > 0) { const t = setInterval(() => { left--; if (left <= 0) clearInterval(t) }, 1000) }">
            <p class="text-sm font-medium">Time remaining</p>
            <p class="font-display text-xl font-semibold tabular-nums"
               :class="left < 60 ? 'text-danger' : ''"
               aria-live="off">
                <span x-text="String(Math.floor(left / 60)).padStart(2, '0')"></span>:<span x-text="String(left % 60).padStart(2, '0')"></span>
                <span class="sr-only">minutes remaining</span>
            </p>
        </div>

        @if ($expired)
            <x-ui.alert type="danger" title="Time is up on the server">
                The deadline for this attempt has already passed. Submitting now will not be accepted.
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('courses.quiz.submit', [$offering, $quiz, $attempt]) }}" @submit="return !expired && confirm('Submit your answers?')">
            @csrf

            <ol class="space-y-6">
                @foreach ($quiz->questions as $question)
                    <li class="panel px-6 py-5">
                        <fieldset>
                            <legend class="mb-3 text-sm font-semibold">
                                <span class="me-1.5 text-ink-faint tabular-nums">{{ $loop->iteration }}.</span>{{ $question->prompt }}
                            </legend>

                            @if (in_array($question->type, ['single_choice', 'true_false']))
                                @foreach ($question->options ?? [['key' => 'true', 'text' => 'True'], ['key' => 'false', 'text' => 'False']] as $option)
                                    <label class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option['key'] }}"
                                               class="size-4 accent-[var(--color-pine)]" required>
                                        {{ $option['text'] }}
                                    </label>
                                @endforeach
                            @elseif ($question->type === 'multi_choice')
                                <p class="mb-1.5 text-xs text-ink-faint">Select all that apply.</p>
                                @foreach ($question->options ?? [] as $option)
                                    <label class="flex cursor-pointer items-center gap-2.5 py-1.5 text-sm">
                                        <input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option['key'] }}"
                                               class="size-4 accent-[var(--color-pine)]">
                                        {{ $option['text'] }}
                                    </label>
                                @endforeach
                            @else
                                <input type="text" name="answers[{{ $question->id }}]"
                                       class="input max-w-md" autocomplete="off"
                                       placeholder="Type your answer…">
                            @endif
                        </fieldset>
                    </li>
                @endforeach
            </ol>

            <div class="mt-6 flex items-center justify-between gap-4 border-t border-line pt-4">
                <p class="text-xs leading-relaxed text-ink-faint">
                    Unanswered questions score zero. Submission closes at
                    <span class="font-semibold tabular-nums">{{ $deadline->format('g:i a') }}</span> server time.
                </p>
                <button type="submit" class="btn-primary btn-lg" :disabled="expired">Submit answers</button>
            </div>
        </form>
    </div>
</x-layout.portal>

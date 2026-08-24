<x-layout.portal :title="$quiz->title.' — '.$offering->course->code">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.home', $offering) }}" class="tabular-nums hover:text-pine">{{ $offering->course->code }}</a>
        <span aria-hidden="true"> / </span>
        <span>{{ $quiz->title }}</span>
    </nav>

    <section class="panel mx-auto max-w-2xl px-6 py-8">
        <h1 class="font-display text-2xl font-semibold tracking-tight">{{ $quiz->title }}</h1>

        @if ($quiz->instructions)
            <p class="mt-3 text-sm leading-relaxed text-ink-soft">{{ $quiz->instructions }}</p>
        @endif

        <dl class="mt-6 grid grid-cols-3 gap-4 border-y border-line-soft py-4 text-sm">
            <div><dt class="text-xs text-ink-faint">Questions</dt><dd class="font-semibold tabular-nums">{{ $quiz->questions->count() }}</dd></div>
            <div><dt class="text-xs text-ink-faint">Time limit</dt><dd class="font-semibold tabular-nums">{{ $quiz->duration_minutes }} minutes</dd></div>
            <div><dt class="text-xs text-ink-faint">Attempts</dt><dd class="font-semibold tabular-nums">{{ $attemptsUsed }} / {{ $quiz->max_attempts }}</dd></div>
        </dl>

        <div class="mt-5">
            <x-ui.alert type="info" title="Before you begin">
                The timer starts the moment you click below and runs on the university's
                servers, not your device. Closing the page does not stop it. Make sure
                you have a stable connection before starting.
            </x-ui.alert>
        </div>

        <form method="POST" action="{{ route('courses.quiz.start', [$offering, $quiz]) }}" class="mt-5">
            @csrf
            <button type="submit" class="btn-primary btn-lg">Start attempt</button>
            <a href="{{ route('courses.home', $offering) }}" class="btn-ghost ms-2">Not yet</a>
        </form>
    </section>
</x-layout.portal>

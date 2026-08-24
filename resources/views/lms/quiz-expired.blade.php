<x-layout.portal :title="$quiz->title.' — time expired'">
    <section class="panel mx-auto max-w-2xl px-6 py-10 text-center">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-danger-tint text-danger">
            <x-lucide-timer-off class="size-6" />
        </div>
        <h1 class="mt-4 font-display text-xl font-semibold tracking-tight">The time limit passed</h1>
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-ink-soft">
            Your answers for “{{ $quiz->title }}” arrived after the server-side deadline
            ({{ $quiz->duration_minutes }} minutes plus a short grace period), so the attempt
            was closed without a score.
        </p>
        <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-ink-soft">
            If this was caused by a connectivity problem, contact your lecturer
            <em>immediately</em> — requests to reopen an attempt go through them and are logged.
        </p>
        <a href="{{ route('courses.home', $offering) }}" class="btn-secondary mt-6">Back to {{ $offering->course->code }}</a>
    </section>
</x-layout.portal>

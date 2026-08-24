<x-layout.portal :title="$teaching ? 'My courses — teaching' : 'My courses'">
    <x-ui.page-header
        :title="($teaching ? 'Teaching ' : '').($semester?->session->name.' '.$semester?->name ?? '')"
        :subtitle="$teaching ? 'Your assigned offerings for the current semester.' : 'Courses you are enrolled in this semester. Materials, assignments and feedback live inside each course.'" />

    @if ($offerings->isEmpty())
        <x-ui.empty-state icon="book-open" title="{{ $teaching ? 'No offerings assigned this semester' : 'No enrolled courses yet' }}">
            @if ($teaching)
                If you are expecting an assignment, contact the examinations unit.
            @else
                Your courses appear here once your registration is approved.
                <a href="{{ route('student.registration') }}" class="btn-primary mt-5">Go to registration</a>
            @endif
        </x-ui.empty-state>
    @else
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($offerings as $offering)
                <li>
                    <a href="{{ route('courses.home', $offering) }}"
                       class="panel group flex h-full flex-col px-5 py-5 transition-colors hover:border-pine-line hover:bg-surface-dim">
                        <div class="flex items-baseline justify-between gap-3">
                            <h2 class="font-display text-lg font-semibold tracking-tight group-hover:text-pine">{{ $offering->course->code }}</h2>
                            <span class="badge-neutral tabular-nums">{{ $offering->course->credit_units }}u</span>
                        </div>
                        <p class="mt-1 font-medium leading-snug">{{ $offering->course->title }}</p>
                        <p class="mt-auto pt-3 text-xs text-ink-faint">
                            {{ $teaching ? $offering->enrolmentCount().' students enrolled' : ($offering->lecturer?->name ?? 'Staff TBA') }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layout.portal>

<x-layout.portal title="Results workflow">
    <x-ui.page-header title="Results"
        subtitle="Component scores per offering, and where each sits in the approval chain: draft → submitted → approved → published." />

    <div class="space-y-3">
        @forelse ($offerings as $offering)
            @php
                $status = $offering->resultStatus;
                $isCurrent = $offering->semester_id === $currentSemesterId;
                $badge = match ($status?->value) {
                    'submitted' => 'badge-info',
                    'approved' => 'badge-pine',
                    'published' => 'badge-success',
                    'returned' => 'badge-warning',
                    default => 'badge-neutral',
                };
            @endphp
            <article class="panel flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold">{{ $offering->course->code }} — {{ $offering->course->title }}</p>
                    <p class="mt-0.5 text-xs text-ink-faint">
                        {{ $offering->semester->session->name }} {{ $offering->semester->name }}@if($isCurrent) · current semester@endif
                        · {{ $offering->enrolmentCount() }} students
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="{{ $badge }}">{{ $status?->label() ?? ($isCurrent ? 'In progress — not yet submittable' : 'Scores pending') }}</span>
                    <a href="{{ route('courses.gradebook', $offering) }}" class="btn-secondary btn-sm">
                        {{ $locked = in_array($status?->value, ['approved', 'published']) ? 'View sheet' : 'Open gradebook' }}
                    </a>
                </div>
            </article>
        @empty
            <x-ui.empty-state icon="award" title="No offerings yet">Your offerings will appear here as they are assigned.</x-ui.empty-state>
        @endforelse
    </div>
</x-layout.portal>

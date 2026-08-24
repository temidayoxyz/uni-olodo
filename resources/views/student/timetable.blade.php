<x-layout.portal title="Timetable">
    <x-ui.page-header
        :title="'My timetable — '.($semester?->session->name.' '.$semester?->name ?? '')"
        subtitle="Built from your approved course registration. Venue changes are announced on your dashboard." />

    @if ($slots->isEmpty())
        <x-ui.empty-state icon="calendar-off" title="No timetable yet">
            @if (! $semester)
                There is no active semester right now.
            @else
                Your timetable appears once your course registration for {{ $semester->name }} is approved.
                <a href="{{ route('student.registration') }}" class="btn-primary mt-5">Go to registration</a>
            @endif
        </x-ui.empty-state>
    @else
        {{-- Mobile: day-grouped list --}}
        <div class="space-y-6 md:hidden">
            @foreach ($slots->groupBy('schedule.weekday') as $weekday => $daySlots)
                <section class="panel" aria-label="{{ $weekdays[$weekday] }}">
                    <h2 class="panel-header text-sm font-semibold">{{ $weekdays[$weekday] }}</h2>
                    <ul class="divide-y divide-line-soft">
                        @foreach ($daySlots as $slot)
                            <li class="flex items-center gap-4 px-5 py-3">
                                <p class="w-14 shrink-0 text-xs font-semibold tabular-nums">
                                    {{ \Carbon\Carbon::parse($slot->schedule->starts_at)->format('g:i') }}
                                </p>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $slot->offering->course->code }}</p>
                                    <p class="text-xs text-ink-faint">{{ $slot->schedule->venue ?? 'Venue TBA' }} · until {{ \Carbon\Carbon::parse($slot->schedule->ends_at)->format('g:i a') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        {{-- Desktop: weekly grid --}}
        <div class="table-wrap hidden md:block">
            <table class="table min-w-[52rem]">
                <thead>
                    <tr>
                        @foreach ([1, 2, 3, 4, 5] as $day)
                            <th scope="col">{{ $weekdays[$day] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Distinct time rows across the week (morning / midday / afternoon bands).
                        $bands = [
                            ['8:00', '10:00'], ['10:00', '12:00'], ['12:00', '14:00'], ['14:00', '16:00'], ['16:00', '18:00'],
                        ];
                    @endphp
                    @foreach ($bands as [$from, $to])
                        <tr>
                            @foreach ([1, 2, 3, 4, 5] as $day)
                                @php
                                    $cell = $slots->first(fn ($s) => $s->schedule->weekday === $day
                                        && \Carbon\Carbon::parse($s->schedule->starts_at)->format('G:i') >= \Carbon\Carbon::parse($from)->format('G:i')
                                        && \Carbon\Carbon::parse($s->schedule->starts_at)->format('G:i') < \Carbon\Carbon::parse($to)->format('G:i'));
                                @endphp
                                <td class="!px-2 align-top">
                                    @if ($cell)
                                        <div class="rounded-[var(--radius-control)] border border-pine-line bg-pine-tint px-3 py-2.5">
                                            <p class="text-xs font-bold tabular-nums text-pine">
                                                {{ \Carbon\Carbon::parse($cell->schedule->starts_at)->format('g:i') }}–{{ \Carbon\Carbon::parse($cell->schedule->ends_at)->format('g:i a') }}
                                            </p>
                                            <p class="mt-0.5 truncate text-sm font-semibold">{{ $cell->offering->course->code }}</p>
                                            <p class="truncate text-xs text-ink-soft">{{ $cell->schedule->venue ?? 'Venue TBA' }}</p>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-xs text-ink-faint">Weekend sessions, if scheduled by your faculty, are listed in the mobile view above.</p>
    @endif
</x-layout.portal>

<x-layout.portal title="Dashboard">
    @php
        $user = auth()->user();
        $daypart = now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening');
    @endphp
    <x-ui.page-header
        :title="'Good '.$daypart.', '.collect(explode(' ', $user->name))->last()"
        :subtitle="$semester ? $semester->session->name.' · '.$semester->name.' · Week '.max(1, now()->diffInWeeks($semester->starts_on) + 1) : 'No active semester'" />

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Primary column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Today / Next class --}}
            <section aria-labelledby="today-heading">
                <div class="panel">
                    <div class="panel-header">
                        <h2 id="today-heading" class="text-sm font-semibold">{{ now()->isSameDay(today()) ? 'Today' : 'Next up' }} · {{ today()->translatedFormat('l j F') }}</h2>
                        <a href="/student/timetable" class="text-xs font-medium text-pine hover:underline">Full timetable →</a>
                    </div>

                    @if ($offerings->isEmpty())
                        <div class="px-5 py-10 text-center">
                            <p class="font-medium">You have no approved registrations yet this semester.</p>
                            <p class="mt-1 text-sm text-ink-soft">Registration is open — choose your courses to see your schedule here.</p>
                            <a href="/student/registration" class="btn-primary mt-4">Register courses</a>
                        </div>
                    @elseif ($todaySlots->isEmpty())
                        <div class="px-5 py-8">
                            <p class="text-sm text-ink-soft">No classes scheduled for {{ today()->translatedFormat('l') }}.</p>
                            @if ($nextSlot)
                                <div class="mt-4 flex items-start gap-3 rounded-[var(--radius-control)] bg-surface-dim px-4 py-3">
                                    <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-pine-tint text-pine">
                                        <x-lucide-calendar-clock class="size-4.5" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold">
                                            {{ $nextSlot->schedule->weekdayName() }} {{ \Carbon\Carbon::parse($nextSlot->schedule->starts_at)->format('g:i a') }}
                                            — {{ $nextSlot->offering->course->code }}
                                        </p>
                                        <p class="text-sm text-ink-soft">{{ $nextSlot->offering->course->title }} @if($nextSlot->schedule->venue)· {{ $nextSlot->schedule->venue }}@endif</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <ul class="divide-y divide-line-soft">
                            @foreach ($todaySlots as $slot)
                                <li class="flex items-start gap-4 px-5 py-3.5">
                                    <p class="w-16 shrink-0 pt-0.5 text-sm font-semibold tabular-nums">
                                        {{ \Carbon\Carbon::parse($slot->schedule->starts_at)->format('g:i') }}
                                        <span class="block text-xs font-normal text-ink-faint">to {{ \Carbon\Carbon::parse($slot->schedule->ends_at)->format('g:i a') }}</span>
                                    </p>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold">
                                            {{ $slot->offering->course->code }} — {{ $slot->offering->course->title }}
                                        </p>
                                        <p class="mt-0.5 text-sm text-ink-soft">
                                            {{ $slot->schedule->venue ?? 'Venue TBA' }} · {{ $slot->offering->lecturer?->name ?? 'Lecturer TBA' }}
                                        </p>
                                    </div>
                                    <a href="/student/courses/{{ $slot->offering->id }}" class="btn-secondary btn-sm shrink-0 self-center">Open</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            {{-- Deadlines --}}
            <section aria-labelledby="deadlines-heading">
                <div class="panel">
                    <div class="panel-header">
                        <h2 id="deadlines-heading" class="text-sm font-semibold">Coming due</h2>
                        <a href="/student/academics#assessments" class="text-xs font-medium text-pine hover:underline">All assessments →</a>
                    </div>

                    @if ($deadlines->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-ink-soft">
                            Nothing is due in the next few days. Use the breathing room well.
                        </p>
                    @else
                        <ul class="divide-y divide-line-soft">
                            @foreach ($deadlines as $item)
                                <li class="flex items-center gap-4 px-5 py-3.5">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $item->kind === 'quiz' ? 'bg-ochre-tint text-ochre-strong' : 'bg-info-tint text-info' }}">
                                        <x-lucide-{{ $item->kind === 'quiz' ? 'timer' : 'upload' }} class="size-4.5" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold">{{ $item->title }}</p>
                                        <p class="text-sm text-ink-soft">{{ $item->course }}</p>
                                    </div>
                                    <p class="shrink-0 text-end text-xs leading-tight {{ $item->at->diffInDays(now()) <= 3 ? 'font-semibold text-danger' : 'text-ink-soft' }}">
                                        {{ $item->at->diffInDays(now()) === 0 ? 'Due today' : $item->at->diffInDays(now()).' days' }}
                                        <span class="block font-normal text-ink-faint">{{ $item->at->format('D j M, g:i a') }}</span>
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            {{-- Continue learning: active courses --}}
            <section aria-labelledby="courses-heading">
                <div class="panel">
                    <div class="panel-header">
                        <h2 id="courses-heading" class="text-sm font-semibold">My courses this semester</h2>
                        <a href="/student/courses" class="text-xs font-medium text-pine hover:underline">All courses →</a>
                    </div>
                    <ul class="divide-y divide-line-soft">
                        @foreach ($offerings as $offering)
                            <li>
                                <a href="/student/courses/{{ $offering->id }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-surface-dim">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-pine-tint text-[0.6875rem] font-bold text-pine">
                                        {{ $offering->course->credit_units }}u
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold">{{ $offering->course->code }} — {{ $offering->course->title }}</p>
                                        <p class="text-sm text-ink-soft">{{ $offering->lecturer?->name ?? 'Staff TBA' }}</p>
                                    </div>
                                    <x-lucide-chevron-right class="size-4 shrink-0 text-ink-faint" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        </div>

        {{-- Context column --}}
        <div class="space-y-6">
            {{-- Academic standing --}}
            <section aria-labelledby="standing-heading" class="panel px-5 py-5">
                <h2 id="standing-heading" class="text-sm font-semibold">Academic standing</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-ink-soft">CGPA (official)</dt>
                        <dd class="text-lg font-semibold tabular-nums">{{ $cgpa !== null ? number_format($cgpa, 2) : '—' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-ink-soft">Registered this semester</dt>
                        <dd class="font-semibold tabular-nums">{{ $registeredCredits }} credits</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-ink-soft">Programme</dt>
                        <dd class="max-w-[55%] truncate text-end font-medium">{{ $user->studentProfile?->programme?->name }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-ink-soft">Level</dt>
                        <dd class="font-medium tabular-nums">{{ $user->studentProfile?->level }}</dd>
                    </div>
                </dl>
                <a href="/student/academics" class="btn-secondary btn-sm mt-5 w-full">My academics & results</a>
            </section>

            {{-- Fees --}}
            @if ($unpaidInvoices->isNotEmpty())
                <section aria-labelledby="fees-heading">
                    @foreach ($unpaidInvoices as $invoice)
                        <x-ui.alert type="warning" title="{{ $invoice->title }}">
                            {{ $invoice->formattedAmount() }} is due by {{ $invoice->due_at?->format('j M Y') }}.
                            <a href="/payments" class="ms-1 font-semibold underline underline-offset-2">Pay now</a>
                        </x-ui.alert>
                    @endforeach
                </section>
            @endif

            {{-- Feedback returned --}}
            @if ($recentFeedback->isNotEmpty())
                <section aria-labelledby="feedback-heading" class="panel">
                    <div class="panel-header"><h2 id="feedback-heading" class="text-sm font-semibold">Feedback returned</h2></div>
                    <ul class="divide-y divide-line-soft">
                        @foreach ($recentFeedback as $fb)
                            <li class="px-5 py-3.5">
                                <p class="flex items-baseline justify-between gap-3 text-sm font-semibold">
                                    <span class="truncate">{{ $fb->assignment_title }}</span>
                                    <span class="shrink-0 font-semibold tabular-nums text-success">{{ number_format($fb->score, 0) }}/100</span>
                                </p>
                                <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-ink-soft">{{ $fb->feedback }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Announcements --}}
            <section aria-labelledby="notices-heading" class="panel">
                <div class="panel-header">
                    <h2 id="notices-heading" class="text-sm font-semibold">Notices</h2>
                </div>
                @if ($announcements->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-ink-soft">No notices at the moment.</p>
                @else
                    <ul class="divide-y divide-line-soft">
                        @foreach ($announcements as $announcement)
                            <li class="px-5 py-3.5">
                                <p class="flex items-start gap-2 text-sm font-semibold leading-snug">
                                    @if ($announcement->priority === 'high')
                                        <span class="badge-danger badge mt-0.5 !px-1.5 !py-0">High</span>
                                    @endif
                                    {{ $announcement->title }}
                                </p>
                                <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-ink-soft">{{ str($announcement->body)->stripTags() }}</p>
                                <p class="mt-1.5 text-[0.6875rem] text-ink-faint">{{ $announcement->published_at?->diffForHumans() }} · {{ $announcement->author?->name }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-layout.portal>

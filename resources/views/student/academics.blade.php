<x-layout.portal title="My academics">
    <x-ui.page-header title="My academics"
        subtitle="Your programme record as the registry holds it. Official grades live under Results." />

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-6">
            {{-- Programme record --}}
            <section class="panel px-6 py-6" aria-labelledby="programme-heading">
                <h2 id="programme-heading" class="text-sm font-semibold text-ink-faint uppercase tracking-wide">Programme</h2>
                <p class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ $profile?->programme?->name }}</p>
                <dl class="mt-5 grid grid-cols-2 gap-x-8 gap-y-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-ink-faint">Matric number</dt><dd class="font-semibold tabular-nums">{{ $profile?->matric_number }}</dd></div>
                    <div><dt class="text-xs text-ink-faint">Level</dt><dd class="font-semibold tabular-nums">{{ $profile?->level }} ({{ $profile?->programme?->award === 'beng' ? 'B.Eng.' : 'B.Sc.' }})</dd></div>
                    <div><dt class="text-xs text-ink-faint">Status</dt><dd class="font-semibold capitalize">{{ $profile?->status->value }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-ink-faint">Department</dt><dd>{{ $profile?->programme?->department?->name }}, {{ $profile?->programme?->department?->faculty?->name }}</dd></div>
                    <div><dt class="text-xs text-ink-faint">Academic adviser</dt><dd class="font-medium">{{ $profile?->adviser?->name ?? 'To be assigned' }}</dd></div>
                </dl>
            </section>

            {{-- Current registration snapshot --}}
            <section class="panel px-6 py-6" aria-labelledby="reg-heading">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="reg-heading" class="text-sm font-semibold text-ink-faint uppercase tracking-wide">This semester</h2>
                    @if ($semester)
                        <span class="text-xs text-ink-faint">{{ $semester->session->name }} · {{ $semester->name }}</span>
                    @endif
                </div>

                @if ($registration?->statusIs(\App\Enums\RegistrationStatus::Approved))
                    <ul class="mt-3 divide-y divide-line-soft border-y border-line-soft">
                        @foreach ($registration->items->where('status', 'registered') as $item)
                            <li class="flex items-center justify-between py-2 text-sm">
                                <a href="/courses/{{ $item->offering_id }}" class="hover:text-pine">
                                    <span class="font-medium tabular-nums">{{ $item->offering->course->code }}</span> — {{ $item->offering->course->title }}
                                </a>
                                <span class="tabular-nums text-ink-faint">{{ $item->offering->course->credit_units }}u</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-sm"><strong class="tabular-nums">{{ $registeredCredits }}</strong> registered credits · approved</p>
                @else
                    <p class="mt-3 text-sm leading-relaxed text-ink-soft">
                        No approved registration for this semester yet.
                    </p>
                    <a href="{{ route('student.registration') }}" class="btn-primary btn-sm mt-4">Go to registration</a>
                @endif
            </section>
        </div>

        {{-- Progress + links --}}
        <aside class="space-y-6">
            <section class="panel px-5 py-5" aria-labelledby="progress-heading">
                <h2 id="progress-heading" class="text-sm font-semibold">Progress</h2>
                @php
                    $duration = $profile?->programme?->duration_semesters ?? 8;
                    $percent = min(100, (int) round(($semestersCompleted / max(1, $duration)) * 100));
                @endphp
                <div class="mt-4 space-y-4 text-sm">
                    <div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-ink-soft">Programme duration completed</dt>
                            <dd class="font-semibold tabular-nums">{{ $semestersCompleted }} / {{ $duration }} semesters</dd>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-paper-deep" role="progressbar"
                             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
                             aria-label="Programme progress: {{ $percent }} percent">
                            <div class="h-full rounded-full bg-pine" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <dt class="text-ink-soft">Credits earned (passed, official)</dt>
                        <dd class="font-semibold tabular-nums">{{ $creditsEarned }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <dt class="text-ink-soft">Cumulative GPA</dt>
                        <dd class="font-semibold tabular-nums">{{ $cgpa !== null ? number_format($cgpa, 2) : '—' }}</dd>
                    </div>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-ink-faint">
                    Computed from registry-approved results only.
                </p>
            </section>

            <section class="panel px-5 py-5">
                <h2 class="text-sm font-semibold">Where next</h2>
                <div class="mt-4 grid gap-2">
                    <a href="{{ route('student.results') }}" class="btn-secondary justify-start"><x-lucide-award class="size-4" /> Official results & GPA</a>
                    <a href="{{ route('student.transcript') }}" class="btn-secondary justify-start"><x-lucide-file-text class="size-4" /> Unofficial transcript</a>
                    <a href="{{ route('student.registration') }}" class="btn-secondary justify-start"><x-lucide-clipboard-check class="size-4" /> Course registration</a>
                </div>
            </section>
        </aside>
    </div>
</x-layout.portal>

<x-layout.public title="Academics">
    <section class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold tracking-widest text-pine uppercase">Academics</p>
            <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                Four faculties. {{ $programmeCount }} undergraduate programmes. One standard of teaching.
            </h1>
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-ink-soft">
                Every programme runs on a semester system with continuous assessment
                worth 40% of each course grade. Browse by faculty below, or go straight
                to the programme that fits you.
            </p>
        </div>
    </section>

    <section class="border-b border-line bg-paper-deep/50" aria-label="Programme directory">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-x-12 gap-y-12 lg:grid-cols-2">
                @foreach ($faculties as $faculty)
                    <section aria-labelledby="faculty-{{ $faculty->id }}">
                        <header class="flex items-baseline justify-between gap-4 border-b border-line pb-3">
                            <h2 id="faculty-{{ $faculty->id }}" class="font-display text-xl font-semibold tracking-tight">{{ $faculty->name }}</h2>
                            <span class="shrink-0 text-xs tabular-nums text-ink-faint">{{ $faculty->departments->sum('programmes_count') }} programmes</span>
                        </header>

                        @foreach ($faculty->departments as $department)
                            <div class="mt-5">
                                <h3 class="text-xs font-bold tracking-wide text-ink-faint uppercase">{{ $department->name }}</h3>
                                <ul class="mt-2 divide-y divide-line-soft border-b border-line-soft">
                                    @foreach ($department->programmes as $programme)
                                        <li>
                                            <a href="{{ route('programmes.show', $programme) }}" class="group flex items-center justify-between gap-4 py-3">
                                                <span>
                                                    <span class="block font-medium group-hover:text-pine">{{ $programme->name }}</span>
                                                    <span class="text-xs text-ink-faint">{{ $programme->code }} · {{ intdiv($programme->duration_semesters, 2) }} years · ₦{{ number_format($programme->tuition_per_session / 100) }}/session</span>
                                                </span>
                                                <x-lucide-arrow-right class="size-4 shrink-0 text-ink-faint transition-transform group-hover:translate-x-0.5 group-hover:text-pine" />
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach

                        @if ($faculty->summary)
                            <p class="mt-4 text-sm leading-relaxed text-ink-soft">{{ $faculty->summary }}</p>
                        @endif
                    </section>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Academic calendar snapshot --}}
    @if ($currentSemester)
        <section aria-labelledby="calendar-heading">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_1.2fr] lg:px-8">
                <div>
                    <h2 id="calendar-heading" class="font-display text-3xl font-semibold tracking-tight">The academic year</h2>
                    <p class="mt-4 leading-relaxed text-ink-soft">
                        Two semesters per session: First Semester from September into late January,
                        Second Semester from March through July. Registration opens before lectures
                        begin and closes in the third week — late registration needs your dean's approval.
                    </p>
                </div>
                <dl class="space-y-5">
                    <div class="flex items-start justify-between gap-6 border-b border-line pb-4">
                        <dt class="text-sm text-ink-soft">Current term</dt>
                        <dd class="text-end font-semibold">{{ $currentSemester->session->name }} {{ $currentSemester->name }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-6 border-b border-line pb-4">
                        <dt class="text-sm text-ink-soft">Lectures run</dt>
                        <dd class="text-end font-semibold tabular-nums">{{ $currentSemester->starts_on?->format('j M') }} – {{ $currentSemester->ends_on?->format('j M Y') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-6 border-b border-line pb-4">
                        <dt class="text-sm text-ink-soft">Course registration</dt>
                        <dd class="text-end font-semibold tabular-nums">closes {{ $currentSemester->registration_closes_at?->format('j F Y') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-6">
                        <dt class="text-sm text-ink-soft">Assessment pattern</dt>
                        <dd class="text-end font-semibold">CA 40% · Examination 60%</dd>
                    </div>
                </dl>
            </div>
        </section>
    @endif
</x-layout.public>

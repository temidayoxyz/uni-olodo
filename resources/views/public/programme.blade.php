<x-layout.public :title="$programme->name">
    {{-- Breadcrumb + masthead --}}
    <div class="border-b border-line bg-paper-deep/50">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs text-ink-faint">
                <a href="{{ route('academics') }}" class="hover:text-pine">Academics</a>
                <span aria-hidden="true"> / </span>
                <span>{{ $programme->department->faculty->name }}</span>
                <span aria-hidden="true"> / </span>
                <span class="text-ink-soft">{{ $programme->name }}</span>
            </nav>
        </div>
    </div>

    <section class="border-b border-line bg-paper-deep/50">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 pb-14 sm:px-6 lg:grid-cols-[1.5fr_1fr] lg:gap-16 lg:px-8">
            <div>
                <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                    {{ $programme->name }}
                </h1>
                <p class="mt-3 text-sm font-medium text-pine">
                    {{ $programme->department->name }} · {{ $programme->department->faculty->name }}
                </p>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-soft">{{ $programme->description }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary btn-lg">Apply for this programme</a>
                    <a href="{{ route('admissions') }}" class="btn-secondary btn-lg">Admissions overview</a>
                </div>
            </div>

            {{-- Facts panel --}}
            <aside class="panel h-fit px-6 py-6" aria-label="Programme facts">
                <h2 class="sr-only">Key facts</h2>
                <dl class="space-y-4 text-sm">
                    <div class="flex items-baseline justify-between gap-4 border-b border-line-soft pb-3.5">
                        <dt class="text-ink-soft">Award</dt>
                        <dd class="font-semibold uppercase">{{ $programme->award === 'beng' ? 'B.Eng.' : 'B.Sc.' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 border-b border-line-soft pb-3.5">
                        <dt class="text-ink-soft">Duration</dt>
                        <dd class="font-semibold">{{ intdiv($programme->duration_semesters, 2) }} years ({{ $programme->duration_semesters }} semesters)</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 border-b border-line-soft pb-3.5">
                        <dt class="text-ink-soft">Tuition</dt>
                        <dd class="font-semibold tabular-nums">₦{{ number_format($programme->tuition_per_session / 100) }} <span class="text-xs font-normal text-ink-faint">per session</span></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-ink-soft">Study mode</dt>
                        <dd class="font-semibold">Full-time, on campus</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>

    <section class="border-b border-line">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_1fr] lg:gap-16 lg:px-8">
            {{-- Entry requirements --}}
            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">Entry requirements</h2>
                <p class="mt-4 leading-relaxed text-ink-soft">{!! str($programme->entry_requirements)->markdown()->toHtmlString() !!}</p>
                <p class="mt-3 leading-relaxed text-ink-soft">
                    Applications are made online through the applicant portal. Shortlisted
                    candidates are notified of their entrance examination date by email.
                </p>
            </div>

            {{-- Programme structure --}}
            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">How the programme is structured</h2>
                <p class="mt-4 leading-relaxed text-ink-soft">
                    The first two years build foundations — university-wide general studies,
                    mathematics for your field, and introductory courses in the discipline.
                    From {{ $programme->department->code }} 300 upward, teaching concentrates
                    on advanced and applied coursework.
                </p>

                @if ($programme->department->courses->isNotEmpty())
                    <h3 class="mt-7 text-xs font-bold tracking-widest text-ink-faint uppercase">Sample early courses</h3>
                    <ul class="mt-3 divide-y divide-line-soft border-y border-line-soft">
                        @foreach ($programme->department->courses->take(8) as $course)
                            <li class="flex items-baseline justify-between gap-4 py-2.5 text-sm">
                                <span class="font-medium tabular-nums">{{ $course->code }}</span>
                                <span class="flex-1 text-ink-soft">{{ $course->title }}</span>
                                <span class="shrink-0 tabular-nums text-ink-faint">{{ $course->credit_units }}u</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <p class="mt-4 text-sm leading-relaxed text-ink-soft">
                    Full curriculum handbooks are issued at registration each session.
                </p>
            </div>
        </div>
    </section>

    {{-- Where it leads + related --}}
    <section>
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1.4fr_1fr] lg:gap-16 lg:px-8">
            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">Where it leads</h2>
                <p class="mt-4 max-w-xl leading-relaxed text-ink-soft">
                    Graduates of this programme go into roles across technology, finance,
                    industry, and the public sector — or on to postgraduate study. The career
                    services desk runs CV clinics and an annual career fair attended by employers
                    who hire from {{ $programme->department->code }} specifically.
                </p>
            </div>

            @if ($relatedProgrammes->isNotEmpty())
                <aside aria-label="Related programmes">
                    <h2 class="text-xs font-bold tracking-widest text-ink-faint uppercase">Also in this department</h2>
                    <ul class="mt-3 divide-y divide-line-soft border-y border-line-soft">
                        @foreach ($relatedProgrammes as $related)
                            <li>
                                <a href="{{ route('programmes.show', $related) }}" class="group flex items-center justify-between gap-4 py-3 text-sm font-medium hover:text-pine">
                                    {{ $related->name }}
                                    <x-lucide-arrow-right class="size-4 shrink-0 text-ink-faint group-hover:text-pine" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </aside>
            @endif
        </div>
    </section>
</x-layout.public>

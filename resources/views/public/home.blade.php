<x-layout.public title="Home">
    {{-- Masthead: editorial statement, not a marketing hero --}}
    <section class="border-b border-line">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.5fr_1fr] lg:gap-16 lg:py-24">
            <div>
                <p class="text-sm font-semibold tracking-widest text-pine uppercase">Olodo · Ibadan · Est. 2011</p>
                <h1 class="mt-4 font-display text-4xl leading-[1.08] font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                    A university that knows your name — and takes your future seriously.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-ink-soft">
                    University of Olodo is a young comprehensive institution built around
                    close teaching, honest standards, and a digital campus that puts
                    students first. Knowledge. Character. Impact.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="/academics" class="btn-primary btn-lg">Explore programmes</a>
                    <a href="/admissions" class="btn-secondary btn-lg">How to apply</a>
                </div>
            </div>

            {{-- Live institutional context --}}
            <aside class="flex flex-col justify-end gap-4 lg:border-s lg:border-line lg:ps-12">
                @if ($currentSemester?->registrationIsOpen())
                    <div class="rounded-[var(--radius-surface)] border border-pine-line bg-pine-tint px-5 py-4">
                        <p class="flex items-center gap-2 text-xs font-bold tracking-wide text-pine uppercase">
                            <span class="size-1.5 animate-pulse rounded-full bg-pine"></span> Registration open
                        </p>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink">
                            Course registration for {{ $currentSemester->session->name }} {{ $currentSemester->name }}
                            closes {{ $currentSemester->registration_closes_at?->format('j F') }}.
                        </p>
                    </div>
                @endif

                <dl class="grid grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <dt class="text-xs tracking-wide text-ink-faint uppercase">Faculties</dt>
                        <dd class="mt-1 font-display text-2xl font-semibold tabular-nums">{{ $facultyCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs tracking-wide text-ink-faint uppercase">Programmes</dt>
                        <dd class="mt-1 font-display text-2xl font-semibold tabular-nums">{{ $programmes->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs tracking-wide text-ink-faint uppercase">Founded</dt>
                        <dd class="mt-1 font-display text-2xl font-semibold tabular-nums">2011</dd>
                    </div>
                    <div>
                        <dt class="text-xs tracking-wide text-ink-faint uppercase">Teaching style</dt>
                        <dd class="mt-1 font-display text-xl font-semibold">Small classes</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>

    {{-- Programmes as an index, grouped by faculty --}}
    <section aria-labelledby="programmes-heading" class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-baseline justify-between gap-4">
                <h2 id="programmes-heading" class="font-display text-3xl font-semibold tracking-tight">Programmes of study</h2>
                <a href="/academics" class="text-sm font-medium text-pine hover:underline">Full academic catalogue →</a>
            </div>

            <div class="mt-10 grid gap-x-12 gap-y-10 md:grid-cols-2">
                @foreach ($faculties as $faculty)
                    <section aria-label="{{ $faculty->name }}">
                        <h3 class="border-b border-line pb-2.5 text-sm font-bold tracking-wide text-ink uppercase">{{ $faculty->name }}</h3>
                        <ul class="divide-y divide-line-soft">
                            @foreach ($faculty->departments->sortBy('name') as $department)
                                @foreach ($department->programmes->where('is_active')->sortBy('name') as $programme)
                                    <li>
                                        <a href="/academics/{{ $programme->slug }}" class="group flex items-baseline justify-between gap-4 py-3">
                                            <span>
                                                <span class="block text-[0.9375rem] font-medium group-hover:text-pine">{{ $programme->name }}</span>
                                                <span class="text-xs text-ink-faint">{{ $department->code }} · {{ intdiv($programme->duration_semesters, 2) }} years</span>
                                            </span>
                                            <x-lucide-arrow-right class="size-4 shrink-0 self-center text-ink-faint transition-transform group-hover:translate-x-0.5 group-hover:text-pine" />
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Learning at Olodo: prose + concrete practice --}}
    <section aria-labelledby="learning-heading" class="border-b border-line bg-paper-deep/60">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_1.1fr] lg:gap-16 lg:px-8">
            <div>
                <h2 id="learning-heading" class="font-display text-3xl font-semibold tracking-tight">What learning here looks like</h2>
                <p class="mt-5 leading-relaxed text-ink-soft">
                    We are a teaching university, and we say so plainly. Lecturers know
                    their students by name; continuous assessment carries real weight;
                    and every course has a home on the digital campus — materials,
                    submissions, feedback, and results you can actually find.
                </p>
                <p class="mt-4 leading-relaxed text-ink-soft">
                    It is a deliberately modern operating culture inside a traditional
                    academic frame: formal examinations, moderated results approved by
                    the registry, and transcripts that mean what they say.
                </p>
            </div>

            <ul class="space-y-5">
                @foreach ([
                    ['Continuous assessment done properly', '40% of your course grade is earned across the semester through labs, assignments, and quizzes — not one high-stakes scramble at the end.', 'clipboard-check'],
                    ['A portal that works', 'Register courses, submit work, receive graded feedback, track fees, and get official notices targeted to you — from any device.', 'monitor-smartphone'],
                    ['Results you can trust', 'Lecturers submit provisional scores; the registry approves and publishes them. What you see on your transcript is what senate approved.', 'shield-check'],
                ] as [$title, $body, $icon])
                    <li class="flex gap-4 rounded-[var(--radius-surface)] border border-line bg-surface p-5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-pine-tint text-pine">
                            <x-lucide-{{ $icon }} class="size-5" />
                        </span>
                        <div>
                            <h3 class="font-semibold">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $body }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- News & events --}}
    <section aria-labelledby="news-heading" class="border-b border-line">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.4fr_1fr] lg:gap-16 lg:px-8">
            <div>
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 id="news-heading" class="font-display text-3xl font-semibold tracking-tight">From the university</h2>
                    <a href="/news" class="text-sm font-medium text-pine hover:underline">All news →</a>
                </div>
                <ul class="mt-8 divide-y divide-line">
                    @forelse ($news as $article)
                        <li class="flex gap-6 py-5">
                            <time datetime="{{ $article->published_at->toDateString() }}" class="w-14 shrink-0 pt-0.5 text-sm tabular-nums text-ink-faint">
                                {{ $article->published_at->format('M') }}
                                <span class="block font-display text-2xl font-semibold text-ink">{{ $article->published_at->format('j') }}</span>
                            </time>
                            <div>
                                <h3 class="font-display text-xl leading-snug font-semibold tracking-tight hover:text-pine">
                                    <a href="/news/{{ $article->slug }}">{{ $article->title }}</a>
                                </h3>
                                <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-ink-soft">{{ $article->excerpt }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="py-5 text-sm text-ink-soft">News will appear here once published.</li>
                    @endforelse
                </ul>
            </div>

            <aside>
                <h2 class="text-sm font-bold tracking-wide text-ink uppercase">Upcoming events</h2>
                <ul class="mt-5 space-y-4">
                    @forelse ($events as $event)
                        <li class="rounded-[var(--radius-surface)] border border-line bg-surface p-4">
                            <p class="text-xs font-semibold tracking-wide text-pine uppercase">{{ $event->starts_at->format('D j F · g:i a') }}</p>
                            <h3 class="mt-1 font-semibold leading-snug">{{ $event->title }}</h3>
                            @if ($event->location)<p class="mt-1 text-sm text-ink-faint">{{ $event->location }}</p>@endif
                        </li>
                    @empty
                        <li class="text-sm text-ink-soft">No upcoming events.</li>
                    @endforelse
                </ul>
            </aside>
        </div>
    </section>

    {{-- Admissions path --}}
    <section class="bg-pine text-white" aria-labelledby="apply-heading">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <h2 id="apply-heading" class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Ready to apply?</h2>
                <p class="mt-4 text-white/80 leading-relaxed">
                    Applications for the new session are open. The whole process — forms,
                    documents, fee payment, and status tracking — happens online.
                </p>
            </div>

            <ol class="mt-10 grid gap-8 sm:grid-cols-4">
                @foreach ([
                    'Check requirements', 'Create your applicant account', 'Complete & submit', 'Track your decision',
                ] as $i => $step)
                    <li class="border-t-2 border-white/25 pt-4">
                        <span class="font-display text-3xl font-semibold text-ochre-tint tabular-nums">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <p class="mt-2 font-medium">{{ $step }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="mt-10 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn-lg btn bg-white font-semibold text-pine hover:bg-white/90 active:bg-white">Start application</a>
                <a href="/admissions" class="btn-lg btn border-white/30 font-semibold text-white hover:bg-white/10">Admissions overview</a>
            </div>
        </div>
    </section>
</x-layout.public>

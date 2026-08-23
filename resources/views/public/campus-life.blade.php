<x-layout.public title="Campus Life">
    <section class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold tracking-widest text-pine uppercase">Campus life</p>
            <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                A campus sized for participation, not spectators.
            </h1>
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-ink-soft">
                Olodo is compact by design. Between lectures, labs, and the library,
                there is a deliberate place for everything else: societies, sport,
                worship, work, and rest.
            </p>
        </div>
    </section>

    {{-- Student services --}}
    <section aria-labelledby="services-heading" class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 id="services-heading" class="font-display text-3xl font-semibold tracking-tight">Student support services</h2>
            <div class="mt-10 grid gap-x-12 gap-y-10 md:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Academic advising', 'Every student is assigned an academic adviser in their department — a named lecturer you can actually book time with, not a helpdesk ticket.', 'user-check'],
                    ['Counselling & wellbeing', 'Confidential counselling through the student affairs unit, with referral pathways for care beyond campus.', 'heart-handshake'],
                    ['Career services', 'CV clinics, interview practice, and the annual career fair. The desk also coordinates work-study placements on campus.', 'briefcase'],
                    ['Accommodation', 'On-campus halls are allocated by session with priority for new students; the affairs office maintains a vetted off-campus listing.', 'bed-double'],
                    ['Health centre', 'A staffed clinic on campus during teaching hours, with an ambulance arrangement to UCH for emergencies.', 'stethoscope'],
                    ['Disability support', 'Reasonable adjustments coordinated with faculties — timetabling, accessible venues, and examination arrangements.', 'accessibility'],
                ] as [$title, $body, $icon])
                    <article>
                        <span class="flex size-10 items-center justify-center rounded-full bg-pine-tint text-pine">
                            <x-lucide-{{ $icon }} class="size-5" />
                        </span>
                        <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ $body }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Community --}}
    <section class="border-b border-line bg-paper-deep/50" aria-labelledby="community-heading">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_1.2fr] lg:gap-16 lg:px-8">
            <div>
                <h2 id="community-heading" class="font-display text-3xl font-semibold tracking-tight">Societies & community</h2>
                <p class="mt-4 leading-relaxed text-ink-soft">
                    Societies register each session through student affairs; any twelve
                    students with a faculty patron can found one. The current roster runs
                    from departmental associations to faith fellowships, debate, robotics,
                    and one very competitive chess club.
                </p>
                <ul class="mt-6 space-y-3 text-sm leading-relaxed">
                    <li><strong class="text-ink">Computing Students' Association (COMPSA)</strong> — hackathons, a weekly project clinic, and the departmental seminar series.</li>
                    <li><strong class="text-ink">Management & Accounting Society</strong> — case competitions and firm visits across Ibadan and Lagos.</li>
                    <li><strong class="text-ink">Olodo Debate Union</strong> — inter-university circuit; open trials at the start of every session.</li>
                    <li><strong class="text-ink">Sports council</strong> — football, basketball, athletics, table tennis, with inter-faculty games each second semester.</li>
                </ul>
            </div>

            <aside aria-labelledby="facilities-heading">
                <h2 id="facilities-heading" class="text-sm font-bold tracking-wide uppercase">Facilities</h2>
                <ul class="mt-6 divide-y divide-line border-y border-line">
                    @foreach ([
                        ['The Library', 'Reading seats for six hundred, course reserves for every taught module, and extended hours during examinations.'],
                        ['Digital innovation studio', 'Workstations and a small server rack reserved for capstone and society projects — booked, never queued.'],
                        ['Laboratories', 'Dedicated computing labs, engineering workshops, and a statistics computing room.'],
                        ['Sports complex', 'Football pitch, basketball court, and indoor hall beside the hostels.'],
                        ['Multipurpose auditorium', 'Orientation, public lectures, and the things students actually remember university by.'],
                    ] as $name => $description)
                        <li class="py-3.5">
                            <p class="font-medium">{{ $name }}</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-ink-soft">{{ $description }}</p>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </section>

    {{-- CTA into events --}}
    <section class="bg-pine text-white" aria-labelledby="visit-heading">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-6 px-4 py-12 sm:px-6 lg:px-8">
            <div>
                <h2 id="visit-heading" class="font-display text-2xl font-semibold tracking-tight">See it for yourself</h2>
                <p class="mt-2 max-w-xl text-white/80">Public lectures and the career fair are open to visitors and prospective students. Orientation happens every session before first semester begins.</p>
            </div>
            <a href="{{ route('news.index') }}" class="btn-lg btn bg-white font-semibold text-pine hover:bg-white/90">Upcoming events →</a>
        </div>
    </section>
</x-layout.public>

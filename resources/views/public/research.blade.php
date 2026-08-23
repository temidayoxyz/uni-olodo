<x-layout.public title="Research">
    <section class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold tracking-widest text-pine uppercase">Research</p>
            <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                Growing research ambitions, honestly stated.
            </h1>
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-ink-soft">
                Olodo is a teaching university with a deliberate, growing research
                culture — concentrated where we have genuine depth, not scattered
                everywhere for prestige.
            </p>
        </div>
    </section>

    {{-- Research areas --}}
    <section aria-labelledby="areas-heading" class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 id="areas-heading" class="font-display text-3xl font-semibold tracking-tight">Where our work concentrates</h2>
            <div class="mt-10 grid gap-x-12 gap-y-10 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Applied computing for Nigerian constraints', 'Systems that work under real bandwidth, power, and cost conditions — from low-bandwidth application design to offline-first record systems.'],
                    ['Statistics for public decisions', 'Survey methodology and inference applied to health, agriculture, and education data in partnership with state agencies.'],
                    ['Organisational behaviour in emerging firms', 'How small and medium Nigerian firms actually manage people, incentives, and growth.'],
                    ['Power systems resilience', 'Distribution networks, renewable integration, and the engineering of reliable supply in imperfect grids.'],
                ] as [$title, $body])
                    <article class="border-t-2 border-pine/20 pt-4">
                        <h3 class="font-display text-xl leading-snug font-semibold tracking-tight">{{ $title }}</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-ink-soft">{{ $body }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Centres + opportunities --}}
    <section>
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_1fr] lg:gap-16 lg:px-8">
            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">Centres & groups</h2>
                <ul class="mt-6 divide-y divide-line border-y border-line">
                    @foreach ([
                        ['Centre for Digital Innovation', 'Hosts the capstone studio, industry-sponsored projects, and the applied computing seminar series.'],
                        ['Data & Policy Unit', 'Statistical consulting for public-sector partners; staffed jointly by Mathematical Sciences and Management faculties.'],
                        ['Undergraduate research scheme', 'Final-year projects with genuine research questions are selected annually for presentation at the campus research day.'],
                    ] as [$name, $description])
                        <li class="py-3.5">
                            <p class="font-medium">{{ $name }}</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-ink-soft">{{ $description }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">For students & partners</h2>
                <p class="mt-4 leading-relaxed text-ink-soft">
                    Undergraduate researchers join faculty projects through the final-year
                    selection process each session; postgraduate supervision capacity is
                    published openly by department so applicants know before they apply.
                    Organisations interested in sponsored student projects or data
                    partnerships should write to the
                    <a href="mailto:research@olodo.edu.ng" class="font-medium text-pine hover:underline">research office</a>.
                </p>
                <p class="mt-4 leading-relaxed text-ink-soft">
                    Publications by our faculty appear in the annual research report,
                    available on request from the registry.
                </p>
                <a href="{{ route('contact') }}" class="btn-secondary mt-6">Contact the research office</a>
            </div>
        </div>
    </section>
</x-layout.public>

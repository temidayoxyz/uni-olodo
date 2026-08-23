<x-layout.public title="About">
    <section class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold tracking-widest text-pine uppercase">About</p>
            <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                A young university, built deliberately.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-soft">
                University of Olodo was founded in 2011 in Olodo, Ibadan — a young
                institution that chose a harder path: grow slowly enough that teaching
                quality never becomes a slogan.
            </p>
        </div>
    </section>

    <section aria-labelledby="mission-heading">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_1.3fr] lg:gap-16 lg:px-8">
            <aside class="lg:border-e lg:border-line lg:pe-16">
                <h2 id="mission-heading" class="sr-only">Mission and values</h2>
                <blockquote class="border-s-2 border-pine ps-5">
                    <p class="font-display text-2xl leading-snug font-medium tracking-tight">Knowledge. Character. Impact.</p>
                    <footer class="mt-2 text-sm text-ink-faint">The motto, in order of priority.</footer>
                </blockquote>
            </aside>

            <div class="space-y-8 leading-relaxed text-ink-soft">
                <p><strong class="text-ink">Our mission</strong> is to educate Nigerian students to the standard their ambitions deserve — through close teaching, honest assessment, and a campus culture where character is taken as seriously as grades.</p>
                <p><strong class="text-ink">Our vision</strong> is a university that earns its reputation the slow way: graduates who are competent, employable, and known for how they work with others.</p>
                <div>
                    <h3 class="text-xs font-bold tracking-widest uppercase">What we hold to</h3>
                    <ul class="mt-3 space-y-2.5">
                        <li class="flex gap-3"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-pine" /> Teaching-first staffing: lecturers are hired and promoted for classroom excellence as much as research output.</li>
                        <li class="flex gap-3"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-pine" /> Assessment integrity: moderated results, published only after registry approval, protected from grade inflation.</li>
                        <li class="flex gap-3"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-pine" /> Digital fluency: every academic process lives on the portal — not because technology is fashionable, but because students deserve working systems.</li>
                        <li class="flex gap-3"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-pine" /> Honest growth: intake expands only when staff and facilities can absorb it.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- History --}}
    <section class="border-y border-line bg-paper-deep/50" aria-labelledby="history-heading">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 id="history-heading" class="font-display text-3xl font-semibold tracking-tight">A short history</h2>
            <dl class="mt-10 grid gap-x-12 gap-y-8 md:grid-cols-3">
                @foreach ([
                    ['2011', 'Founding', 'The university opens with two faculties — Sciences and Management — and 214 pioneer students in refurbished buildings along Oladipo Alayande Road.'],
                    ['2015', 'Computing arrives', 'The Faculty of Computing & Information Sciences is established; the first graduating cohort includes 38 computer science majors.'],
                    ['2019', 'Engineering joins', 'Accreditation of the Faculty of Engineering adds Electrical & Electronic Engineering, with laboratory space purpose-built for it.'],
                    ['2022', 'Digital campus', 'The integrated student portal replaces scattered systems: registration, learning, results, and fees move onto one platform.'],
                    ['2024', 'Largest cohort', 'Admissions pass one thousand new students for the first time — matched, by policy, with a proportionate expansion of teaching staff.'],
                    ['Today', 'Still teaching first', 'Four faculties, eight undergraduate programmes, and a rule the founders set that has not been traded away: no lecture hall we cannot teach well in.'],
                ] as [$year, $title, $body])
                    <div class="border-t-2 border-pine/20 pt-4">
                        <dt class="font-display text-2xl font-semibold tabular-nums">{{ $year }}</dt>
                        <dd class="mt-1"><span class="font-semibold">{{ $title }}.</span> <span class="text-sm leading-relaxed text-ink-soft">{{ $body }}</span></dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- Leadership + governance --}}
    <section aria-labelledby="leadership-heading">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_1fr] lg:gap-16 lg:px-8">
            <div>
                <h2 id="leadership-heading" class="font-display text-2xl font-semibold tracking-tight">Leadership</h2>
                <ul class="mt-6 divide-y divide-line border-y border-line">
                    @foreach ([
                        'Vice-Chancellor' => 'Prof. Adaeze Nnamdi-Agu',
                        'Deputy Vice-Chancellor (Academic)' => 'Prof. Wale Adekunle',
                        'Registrar' => 'Tunde Bakare',
                        'Bursar' => 'Sani Garba',
                        'University Librarian' => 'Dr. Ijeoma Ekwueme',
                    ] as $role => $name)
                        <li class="flex items-baseline justify-between gap-4 py-3 text-sm">
                            <span class="text-ink-soft">{{ $role }}</span>
                            <span class="font-medium">{{ $name }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 text-sm leading-relaxed text-ink-soft">
                    Senate — chaired by the Vice-Chancellor — sets and reviews academic
                    standards each session, including results moderation policy.
                </p>
            </div>

            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">Strategic priorities</h2>
                <p class="mt-4 leading-relaxed text-ink-soft">
                    For the current planning period, management has committed to four
                    measurable priorities:
                </p>
                <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-ink-soft">
                    <li>Deepen the digital campus: every academic workflow fully self-service where appropriate.</li>
                    <li>Grow postgraduate supervision capacity in Computing and Management.</li>
                    <li>Expand laboratory and studio space ahead of enrolment growth, never behind it.</li>
                    <li>Publish programme-level outcome data annually — including what we have not yet achieved.</li>
                </ol>
            </div>
        </div>
    </section>
</x-layout.public>

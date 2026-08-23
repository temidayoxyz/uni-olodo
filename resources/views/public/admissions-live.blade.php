<x-layout.public title="Admissions">
    <section class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold tracking-widest text-pine uppercase">Admissions</p>
            <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                Applying to Olodo is online, step by step — and honest about what we look for.
            </h1>
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-ink-soft">
                One application, up to two programme choices, and a clear status page that
                tells you exactly where you stand. No agents required.
            </p>

            @if ($intakeSession)
                <div class="mt-8 inline-flex flex-wrap items-center gap-x-6 gap-y-2 rounded-[var(--radius-surface)] border border-pine-line bg-pine-tint px-5 py-3.5 text-sm">
                    <span class="font-semibold text-pine">Now admitting for {{ $intakeSession->name }}</span>
                    @if ($registrationSemester?->registrationIsOpen())
                        <span class="text-ink-soft">Current course registration closes {{ $registrationSemester->registration_closes_at?->format('j F Y') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- The process --}}
    <section aria-labelledby="process-heading" class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 id="process-heading" class="font-display text-3xl font-semibold tracking-tight">The application process</h2>

            <ol class="mt-10 grid gap-x-10 gap-y-8 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Create your account', 'Register with an email you check regularly. Verification takes one click.'],
                    ['Complete the form', 'Personal details, educational background, and up to two programme choices. Save and return any time before submitting.'],
                    ['Upload documents', 'Passport photograph, O-level result (WAEC/NECO/NABTEB), birth certificate or declaration of age, and your entrance examination slip.'],
                    ['Pay & submit', 'The ₦10,000 application fee is paid online or by bank transfer with verification. Submit and track everything from your dashboard.'],
                ] as $i => [$title, $body])
                    <li class="border-t-2 border-pine/25 pt-4">
                        <span class="font-display text-3xl font-semibold text-ochre tabular-nums">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="mt-2 font-semibold">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ $body }}</p>
                    </li>
                @endforeach
            </ol>

            <a href="{{ route('register') }}" class="btn-primary btn-lg mt-10">Start application</a>
        </div>
    </section>

    <section id="fees" class="border-b border-line bg-paper-deep/50">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">Fees</h2>
                <ul class="mt-5 divide-y divide-line border-y border-line">
                    <li class="flex items-baseline justify-between gap-4 py-3.5 text-sm">
                        <span class="font-medium">Application fee <span class="text-ink-faint">(once, non-refundable)</span></span>
                        <span class="tabular-nums">₦10,000</span>
                    </li>
                    <li class="flex items-baseline justify-between gap-4 py-3.5 text-sm">
                        <span class="font-medium">Tuition — Computing & Engineering programmes</span>
                        <span class="tabular-nums">₦420,000–₦450,000 / session</span>
                    </li>
                    <li class="flex items-baseline justify-between gap-4 py-3.5 text-sm">
                        <span class="font-medium">Tuition — Management & Sciences programmes</span>
                        <span class="tabular-nums">₦350,000 / session</span>
                    </li>
                    <li class="flex items-baseline justify-between gap-4 py-3.5 text-sm">
                        <span class="font-medium">Payment structure</span>
                        <span class="text-end">Two instalments per session<br/><span class="text-xs text-ink-faint">(60% first semester · 40% second)</span></span>
                    </li>
                </ul>
                <p class="mt-4 text-sm leading-relaxed text-ink-soft">
                    Tuition covers lectures, laboratory access, examinations, and results processing.
                    Accommodation is billed separately by the student affairs office.
                </p>
            </div>

            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">Financial support</h2>
                <p class="mt-4 leading-relaxed text-ink-soft">
                    Olodo does not pretend to offer scholarships it does not have. What exists:
                </p>
                <ul class="mt-4 space-y-3 text-sm leading-relaxed">
                    <li class="flex gap-3"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-success" /> An instalment plan that spreads tuition across both semesters at no extra cost.</li>
                    <li class="flex gap-3"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-success" /> A work-study scheme placing a small number of students in library, laboratory, and administrative roles each session.</li>
                    <li class="flex gap-3"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-success" /> Guidance on external scholarships and state bursaries through the student affairs office.</li>
                </ul>
                <p class="mt-4 text-sm leading-relaxed text-ink-soft">
                    Questions about fees? Contact the bursary at
                    <a href="mailto:finance@olodo.edu.ng" class="font-medium text-pine hover:underline">finance@olodo.edu.ng</a>.
                </p>
            </div>
        </div>
    </section>

    {{-- FAQs --}}
    <section aria-labelledby="faq-heading">
        <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 id="faq-heading" class="font-display text-3xl font-semibold tracking-tight">Common questions</h2>

            <div class="mt-8 divide-y divide-line border-y border-line">
                @foreach ([
                    ['Can I apply for more than one programme?', 'Yes — every application carries a first and second programme choice. If your first choice is not offered admission, the second is considered automatically; there is no separate fee.'],
                    ['What if my O-level result is in one sitting?', 'Five credits in not more than two sittings are accepted. Both sittings must be declared during the application, and both certificates uploaded where applicable.'],
                    ['How will I know what is happening with my application?', 'Your applicant dashboard shows live status: received, under review, additional information requested, or decided. We also email you at every transition.'],
                    ['Is there an entrance examination?', 'Yes. After document review, shortlisted applicants sit the university entrance examination on campus. Your slip is part of the required documents.'],
                    ['I made a mistake after submitting. What do I do?', 'You cannot edit a submitted application yourself — that protects everyone\'s records. Use the support channel from your dashboard; corrections go through the admissions office and are audited.'],
                ] as [$question, $answer])
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            <x-lucide-chevron-down class="size-4 shrink-0 text-ink-faint transition-transform group-open:rotate-180" />
                        </summary>
                        <p class="mt-3 max-w-3xl text-sm leading-relaxed text-ink-soft">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>

            <p class="mt-8 text-sm text-ink-soft">
                Something else? <a href="{{ route('contact') }}" class="font-medium text-pine hover:underline">Contact the registry →</a>
            </p>
        </div>
    </section>
</x-layout.public>

<footer class="mt-20 border-t border-line bg-surface">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div>
                <div class="flex items-center gap-2.5 text-pine">
                    <x-brand-mark class="size-9" />
                    <span class="font-display text-xl leading-none font-semibold tracking-tight">
                        University of<br/>Olodo
                    </span>
                </div>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-soft">
                    Knowledge. Character. Impact.
                    A young university in Olodo, Ibadan, pairing Nigerian academic
                    tradition with a deliberately modern, digital-native culture.
                </p>
            </div>

            <nav aria-label="Study">
                <h3 class="text-sm font-semibold tracking-wide text-ink uppercase">Study</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ([
                        'Programmes' => '/academics',
                        'Admissions' => '/admissions',
                        'Fees & funding' => '/admissions#fees',
                        'Academic calendar' => '/academics/calendar',
                    ] as $label => $path)
                        <li><a href="{{ $path }}" class="text-ink-soft hover:text-pine">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <nav aria-label="University">
                <h3 class="text-sm font-semibold tracking-wide text-ink uppercase">University</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ([
                        'About' => '/about',
                        'Campus life' => '/campus-life',
                        'Research' => '/research',
                        'News & events' => '/news',
                    ] as $label => $path)
                        <li><a href="{{ $path }}" class="text-ink-soft hover:text-pine">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <div>
                <h3 class="text-sm font-semibold tracking-wide text-ink uppercase">Contact</h3>
                <address class="mt-3 space-y-2 text-sm not-italic text-ink-soft">
                    <p>Oladipo Alayande Road,<br/>Olodo, Ibadan, Oyo State</p>
                    <p><a href="mailto:info@olodo.edu.ng" class="hover:text-pine">info@olodo.edu.ng</a></p>
                    <p><a href="/contact" class="font-medium text-pine hover:underline">All enquiries →</a></p>
                </address>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-2 border-t border-line-soft pt-6 text-xs text-ink-faint sm:flex-row sm:justify-between">
            <p>© {{ date('Y') }} University of Olodo. A fictional institution — sample content for demonstration.</p>
            <p class="flex gap-4">
                <a href="/policies" class="hover:text-ink-soft">Policies</a>
                <a href="/contact" class="hover:text-ink-soft">Accessibility</a>
            </p>
        </div>
    </div>
</footer>

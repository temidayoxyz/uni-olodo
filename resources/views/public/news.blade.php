<x-layout.public title="News & Events">
    <section class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold tracking-widest text-pine uppercase">Newsroom</p>
            <h1 class="mt-3 font-display text-4xl leading-tight font-semibold tracking-tight sm:text-5xl">News & events</h1>
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-ink-soft">
                Announcements, achievements, and what is happening on campus this month.
            </p>
        </div>
    </section>

    <section>
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1.5fr_1fr] lg:gap-16 lg:px-8">
            {{-- Article listing --}}
            <div>
                @forelse ($articles as $article)
                    <article class="border-b border-line py-7 first:pt-0">
                        <div class="flex items-center gap-3 text-xs">
                            <span class="font-semibold tracking-wide text-pine uppercase">{{ $article->category }}</span>
                            <time datetime="{{ $article->published_at->toDateString() }}" class="tabular-nums text-ink-faint">{{ $article->published_at->format('j F Y') }}</time>
                        </div>
                        <h2 class="mt-2 font-display text-2xl leading-snug font-semibold tracking-tight">
                            <a href="{{ route('news.show', $article) }}" class="hover:text-pine">{{ $article->title }}</a>
                        </h2>
                        <p class="mt-2 max-w-2xl leading-relaxed text-ink-soft">{{ $article->excerpt }}</p>
                        <a href="{{ route('news.show', $article) }}" class="mt-3 inline-block text-sm font-medium text-pine hover:underline">Read the article →</a>
                    </article>
                @empty
                    <p class="py-8 text-ink-soft">No articles published yet.</p>
                @endforelse

                <x-ui.pagination :paginator="$articles" />
            </div>

            {{-- Events sidebar --}}
            <aside aria-labelledby="events-heading" class="lg:border-s lg:border-line lg:ps-12">
                <h2 id="events-heading" class="text-sm font-bold tracking-wide uppercase">Upcoming events</h2>
                <ul class="mt-6 space-y-5">
                    @forelse ($events as $event)
                        <li class="rounded-[var(--radius-surface)] border border-line bg-surface p-5">
                            <p class="text-xs font-bold tracking-wide text-pine uppercase tabular-nums">
                                {{ $event->starts_at->format('l j F · g:i a') }}
                            </p>
                            <h3 class="mt-1.5 font-semibold leading-snug">{{ $event->title }}</h3>
                            @if ($event->location)
                                <p class="mt-1 flex items-center gap-1.5 text-sm text-ink-faint">
                                    <x-lucide-map-pin class="size-3.5" /> {{ $event->location }}
                                </p>
                            @endif
                            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-soft">{{ $event->description }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-ink-soft">No upcoming events — check back soon.</li>
                    @endforelse
                </ul>
            </aside>
        </div>
    </section>
</x-layout.public>

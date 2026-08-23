<x-layout.public :title="$article->title">
    <article class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        <header>
            <div class="flex items-center gap-3 text-xs">
                <span class="font-semibold tracking-wide text-pine uppercase">{{ $article->category }}</span>
                <time datetime="{{ $article->published_at->toDateString() }}" class="tabular-nums text-ink-faint">{{ $article->published_at->format('j F Y') }}</time>
            </div>
            <h1 class="mt-3 font-display text-4xl leading-tight font-semibold tracking-tight text-balance">{{ $article->title }}</h1>
            <p class="mt-4 text-lg leading-relaxed text-ink-soft">{{ $article->excerpt }}</p>
            <div class="divider mt-8"></div>
        </header>

        <div class="rich-text mt-8">{!! str($article->body)->markdown() !!}</div>

        <footer class="divider mt-10 pt-5 text-sm text-ink-faint">
            Published by the {{ $article->author?->name ?? 'University of Olodo newsroom' }} ·
            <a href="{{ route('news.index') }}" class="font-medium text-pine hover:underline">All news →</a>
        </footer>
    </article>

    @if ($more->isNotEmpty())
        <aside class="border-t border-line bg-paper-deep/50" aria-label="More news">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <h2 class="text-sm font-bold tracking-wide uppercase">More from the newsroom</h2>
                <ul class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($more as $item)
                        <li>
                            <time datetime="{{ $item->published_at->toDateString() }}" class="text-xs tabular-nums text-ink-faint">{{ $item->published_at->format('j M Y') }}</time>
                            <h3 class="mt-1 font-display text-lg leading-snug font-semibold tracking-tight">
                                <a href="{{ route('news.show', $item) }}" class="hover:text-pine">{{ $item->title }}</a>
                            </h3>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    @endif
</x-layout.public>

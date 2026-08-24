<x-layout.portal :title="$module->title.' — '.$offering->course->code">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.index') }}" class="hover:text-pine">Courses</a>
        <span aria-hidden="true"> / </span>
        <a href="{{ route('courses.home', $offering) }}" class="tabular-nums hover:text-pine">{{ $offering->course->code }}</a>
        <span aria-hidden="true"> / </span>
        <span>{{ $module->title }}</span>
    </nav>

    <x-ui.page-header :title="$module->title" :subtitle="$module->summary" />

    <div class="mx-auto max-w-3xl space-y-6">
        @forelse ($contents as $content)
            <article class="panel px-6 py-6">
                @if ($content->published_at === null)
                    <p class="mb-2"><span class="badge-neutral">Draft — not visible to students</span></p>
                @endif

                <h2 class="font-display text-xl font-semibold tracking-tight">{{ $content->title }}</h2>

                @if ($content->type === 'text')
                    <div class="rich-text mt-4">{!! str($content->body)->markdown() !!}</div>
                @elseif ($content->type === 'file')
                    <p class="mt-3 flex items-center gap-2 text-sm">
                        <x-lucide-file-text class="size-4 text-pine" />
                        {{ $content->file_name ?? basename((string) $content->file_path) }}
                    </p>
                    <a href="{{ route('courses.material', [$offering, $content]) }}"
                       class="btn-secondary btn-sm mt-3"><x-lucide-download class="size-3.5" /> Download material</a>
                @elseif (in_array($content->type, ['link', 'video']))
                    <a href="{{ $content->external_url }}" target="_blank" rel="noopener"
                       class="btn-secondary btn-sm mt-3">
                        <x-lucide-{{ $content->type === 'video' ? 'play' : 'external-link' }} class="size-3.5" />
                        {{ $content->type === 'video' ? 'Watch video' : 'Open resource' }}
                        <span class="sr-only">(opens in a new tab)</span>
                    </a>
                @endif
            </article>
        @empty
            <x-ui.empty-state icon="file-stack" title="Nothing published in this module yet">Check back after the next lecture.</x-ui.empty-state>
        @endforelse
    </div>
</x-layout.portal>

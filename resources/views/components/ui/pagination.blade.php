@props(['paginator'])
@php
    $pages = [];
    if ($paginator->hasPages()) {
        $last = $paginator->lastPage();
        $current = $paginator->currentPage();

        if ($last <= 7) {
            $pages = range(1, $last);
        } else {
            $pages = collect([1, $current - 1, $current, $current + 1, $last])
                ->filter(fn ($p) => $p >= 1 && $p <= $last)
                ->sort()->unique()->values();
        }
    }
@endphp
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="mt-5 flex items-center justify-between">
        <p class="text-xs text-ink-faint">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            of {{ number_format($paginator->total()) }}
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex size-8 items-center justify-center rounded-[var(--radius-control)] text-ink-faint" aria-hidden="true"><x-lucide-chevron-left class="size-4" /></span>
                <span class="sr-only">No previous page</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"
                   class="inline-flex size-8 items-center justify-center rounded-[var(--radius-control)] border border-line bg-surface hover:bg-surface-dim">
                    <x-lucide-chevron-left class="size-4" />
                </a>
            @endif

            @foreach ($pages as $index => $page)
                @if ($index > 0 && $page - $pages[$index - 1] > 1)
                    <span class="px-1 text-ink-faint" aria-hidden="true">…</span>
                @endif

                @if ($page == $paginator->currentPage())
                    <span aria-current="page" class="inline-flex size-8 items-center justify-center rounded-[var(--radius-control)] bg-pine text-sm font-semibold text-white tabular-nums">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="inline-flex size-8 items-center justify-center rounded-[var(--radius-control)] border border-line bg-surface text-sm tabular-nums hover:bg-surface-dim">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"
                   class="inline-flex size-8 items-center justify-center rounded-[var(--radius-control)] border border-line bg-surface hover:bg-surface-dim">
                    <x-lucide-chevron-right class="size-4" />
                </a>
            @else
                <span class="inline-flex size-8 items-center justify-center rounded-[var(--radius-control)] text-ink-faint" aria-hidden="true"><x-lucide-chevron-right class="size-4" /></span>
                <span class="sr-only">No next page</span>
            @endif
        </div>
    </nav>
@endif

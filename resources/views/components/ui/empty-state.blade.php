@props(['icon' => 'inbox', 'title', 'actionUrl' => null, 'actionLabel' => null])
<div class="panel flex flex-col items-center px-6 py-14 text-center">
    <div class="flex size-12 items-center justify-center rounded-full bg-pine-tint text-pine">
        <x-lucide-{{ $icon }} class="size-6" />
    </div>
    <h3 class="mt-4 font-display text-lg font-semibold text-ink">{{ $title }}</h3>
    <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-ink-soft">{{ $slot }}</p>
    @if ($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn-primary mt-5">{{ $actionLabel }}</a>
    @endif
</div>

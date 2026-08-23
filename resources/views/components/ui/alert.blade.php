@props(['type' => 'info', 'title' => null])
@php
$styles = [
    'info' => ['bg-info-tint border-info-line text-info', 'circle-alert'],
    'success' => ['bg-success-tint border-success-line text-success', 'circle-check'],
    'warning' => ['bg-warning-tint border-warning-line text-warning', 'triangle-alert'],
    'danger' => ['bg-danger-tint border-danger-line text-danger', 'octagon-alert'],
    'neutral' => ['bg-paper-deep border-line text-ink-soft', 'info'],
];
[$classes, $icon] = $styles[$type] ?? $styles['info'];
@endphp
<div {{ $attributes->merge(['class' => "rounded-[var(--radius-surface)] border px-4 py-3", 'role' => in_array($type, ['warning', 'danger']) ? 'alert' : 'status']) }}>
    <div class="flex items-start gap-3">
        <x-lucide-{{ $icon }} class="mt-0.5 size-4.5 shrink-0" />
        <div class="min-w-0 text-sm">
            @if ($title)<p class="font-semibold {{ $type === 'neutral' ? 'text-ink' : '' }}">{{ $title }}</p>@endif
            <div class="{{ $title ? 'mt-1' : '' }} leading-relaxed {{ $type === 'neutral' ? 'text-ink-soft' : '' }}">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

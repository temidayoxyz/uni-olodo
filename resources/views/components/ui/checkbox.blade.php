@props(['name', 'label', 'checked' => false])
<label class="flex cursor-pointer items-start gap-2.5 text-sm">
    <input
        type="checkbox"
        name="{{ $name }}"
        value="1"
        {{ checked(old($name, $checked)) }}
        class="mt-0.5 size-4 rounded border-line text-pine accent-[var(--color-pine)]"
    />
    <span>{{ $label }}</span>
</label>

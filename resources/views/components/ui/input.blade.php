@props(['label' => null, 'name' => null, 'hint' => null, 'type' => 'text', 'value' => null, 'required' => false])
@php
    $error = $name ? $errors->first($name) : null;
    $describedBy = collect([$name ? $name.'-hint' : null, $error ? $name.'-error' : null])->filter()->implode(' ');
@endphp
@if ($label !== null && $name !== null)
    <label for="{{ $name }}" class="label">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
    </label>
@endif

<input
    {{ $attributes->merge([
        'type' => $type,
        'id' => $name,
        'name' => $name,
        'value' => old($name, $value),
        'class' => 'input',
        'aria-invalid' => $error ? 'true' : null,
        'aria-describedby' => $describedBy ?: null,
    ]) }}
    @required($required)
/>

@if ($hint)
    <p id="{{ $name.'-hint' }}" class="hint">{{ $hint }}</p>
@endif

@if ($error)
    <p id="{{ $name.'-error' }}" class="error-text" role="alert">
        <x-lucide-circle-alert class="mt-px size-3.5 shrink-0" />
        {{ $error }}
    </p>
@endif

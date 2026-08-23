@props(['label' => null, 'name' => null, 'hint' => null, 'value' => null, 'rows' => 4, 'required' => false])
@php
    $error = $name ? $errors->first($name) : null;
@endphp
@if ($label !== null && $name !== null)
    <label for="{{ $name }}" class="label">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
    </label>
@endif

<textarea
    {{ $attributes->merge([
        'id' => $name,
        'name' => $name,
        'rows' => $rows,
        'class' => 'input',
        'aria-invalid' => $error ? 'true' : null,
    ]) }}
    @required($required)
>{{ old($name, $value) }}</textarea>

@if ($hint)
    <p id="{{ $name.'-hint' }}" class="hint">{{ $hint }}</p>
@endif

@if ($error)
    <p id="{{ $name.'-error' }}" class="error-text" role="alert">
        <x-lucide-circle-alert class="mt-px size-3.5 shrink-0" />
        {{ $error }}
    </p>
@endif

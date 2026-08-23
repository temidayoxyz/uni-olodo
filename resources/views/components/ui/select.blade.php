@props(['label' => null, 'name' => null, 'hint' => null, 'options' => [], 'selected' => null, 'placeholder' => null, 'required' => false])
@php
    $error = $name ? $errors->first($name) : null;
@endphp
@if ($label !== null && $name !== null)
    <label for="{{ $name }}" class="label">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
    </label>
@endif

<select
    {{ $attributes->merge([
        'id' => $name,
        'name' => $name,
        'class' => 'input appearance-none bg-[url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2352615b%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27m6 9 6 6 6-6%27/%3E%3C/svg%3E")] bg-[length:1rem] bg-[position:right_0.65rem_center] bg-no-repeat pe-9',
        'aria-invalid' => $error ? 'true' : null,
    ]) }}
    @required($required)
>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $text)
        <option value="{{ $value }}" @selected((string) old($name, $selected) === (string) $value)>{{ $text }}</option>
    @endforeach
</select>

@if ($hint)
    <p id="{{ $name.'-hint' }}" class="hint">{{ $hint }}</p>
@endif

@if ($error)
    <p id="{{ $name.'-error' }}" class="error-text" role="alert">
        <x-lucide-circle-alert class="mt-px size-3.5 shrink-0" />
        {{ $error }}
    </p>
@endif

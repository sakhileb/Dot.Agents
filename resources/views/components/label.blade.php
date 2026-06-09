@props(['value'])

<label {{ $attributes->merge(['class' => 'da-label']) }}>
    {{ $value ?? $slot }}
</label>

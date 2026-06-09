@props(['for'])

@error($for)
    <p {{ $attributes->merge(['class' => 'da-error-msg']) }}>{{ $message }}</p>
@enderror

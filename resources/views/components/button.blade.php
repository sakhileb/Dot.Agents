<button {{ $attributes->merge(['type' => 'submit', 'class' => 'da-btn-primary']) }}>
    {{ $slot }}
</button>

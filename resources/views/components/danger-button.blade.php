<button {{ $attributes->merge(['type' => 'submit', 'class' => 'da-btn-danger']) }}>
    {{ $slot }}
</button>

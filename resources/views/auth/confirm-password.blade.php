<x-guest-layout>
    <div>
        <h1 class="text-2xl font-bold text-[#111111] font-display mb-1">Confirm your password</h1>
        <p class="text-sm text-[#909088] mb-8">
            This is a secure area. Please confirm your password before continuing.
        </p>

        <x-validation-errors class="mb-5 da-alert da-alert-critical" />

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <div>
                <x-label for="password" value="Password" />
                <x-input id="password" type="password" name="password"
                         required autocomplete="current-password" autofocus
                         placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
            </div>

            <button type="submit" class="da-btn-primary w-full py-2.5 text-sm font-semibold">
                Confirm &amp; Continue
            </button>
        </form>
    </div>
</x-guest-layout>

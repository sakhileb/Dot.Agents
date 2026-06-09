<x-guest-layout>
    <div x-data="{ recovery: false }">
        <div x-show="! recovery">
            <h1 class="text-2xl font-bold text-[#111111] font-display mb-1">Two-factor authentication</h1>
            <p class="text-sm text-[#909088] mb-8">
                Enter the code from your authenticator app.
            </p>
        </div>

        <div x-cloak x-show="recovery">
            <h1 class="text-2xl font-bold text-[#111111] font-display mb-1">Use a recovery code</h1>
            <p class="text-sm text-[#909088] mb-8">
                Enter one of your emergency recovery codes.
            </p>
        </div>

        <x-validation-errors class="mb-5 da-alert da-alert-critical" />

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <div x-show="! recovery">
                <x-label for="code" value="Authentication Code" />
                <x-input id="code" type="text" inputmode="numeric" name="code"
                         autofocus x-ref="code" autocomplete="one-time-code"
                         placeholder="6-digit code" />
            </div>

            <div x-cloak x-show="recovery">
                <x-label for="recovery_code" value="Recovery Code" />
                <x-input id="recovery_code" type="text" name="recovery_code"
                         x-ref="recovery_code" autocomplete="one-time-code"
                         placeholder="xxxx-xxxx" />
            </div>

            <button type="submit" class="da-btn-primary w-full py-2.5 text-sm font-semibold">
                Verify &amp; Sign In
            </button>
        </form>

        <p class="mt-6 text-center">
            <button type="button"
                    class="text-sm text-brand-purple font-medium hover:text-brand-purple-mid transition-colors"
                    x-show="! recovery"
                    x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                Use a recovery code instead
            </button>
            <button type="button"
                    class="text-sm text-brand-purple font-medium hover:text-brand-purple-mid transition-colors"
                    x-cloak x-show="recovery"
                    x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                Use authenticator code instead
            </button>
        </p>
    </div>
</x-guest-layout>

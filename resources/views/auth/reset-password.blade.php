<x-guest-layout>
    <div>
        <h1 class="text-2xl font-bold text-[#111111] font-display mb-1">Set new password</h1>
        <p class="text-sm text-[#909088] mb-8">Choose a strong password for your account.</p>

        <x-validation-errors class="mb-5 da-alert da-alert-critical" />

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-label for="email" value="Work Email" />
                <x-input id="email" type="email" name="email"
                         :value="old('email', $request->email)"
                         required autofocus autocomplete="username" />
            </div>

            <div>
                <x-label for="password" value="New Password" />
                <x-input id="password" type="password" name="password"
                         required autocomplete="new-password"
                         placeholder="Min. 8 characters" />
            </div>

            <div>
                <x-label for="password_confirmation" value="Confirm New Password" />
                <x-input id="password_confirmation" type="password" name="password_confirmation"
                         required autocomplete="new-password"
                         placeholder="Repeat password" />
            </div>

            <button type="submit" class="da-btn-primary w-full py-2.5 text-sm font-semibold">
                Reset Password
            </button>
        </form>
    </div>
</x-guest-layout>

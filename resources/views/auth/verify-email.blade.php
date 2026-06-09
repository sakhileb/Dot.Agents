<x-guest-layout>
    <div>
        <h1 class="text-2xl font-bold text-[#111111] font-display mb-1">Verify your email</h1>
        <p class="text-sm text-[#909088] mb-8">
            We sent a verification link to your email address. Click it to activate your account.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-6 da-alert da-alert-success">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                A new verification link has been sent to your email address.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="da-btn-primary w-full py-2.5 text-sm font-semibold">
                Resend Verification Email
            </button>
        </form>

        <div class="mt-5 flex items-center justify-between">
            <a href="{{ route('profile.show') }}"
               class="text-sm text-brand-purple hover:text-brand-purple-mid transition-colors font-medium">
                Edit Profile
            </a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit"
                        class="text-sm text-[#909088] hover:text-[#111111] transition-colors">
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>

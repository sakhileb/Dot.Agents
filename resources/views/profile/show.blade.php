<x-layouts.platform>
    <x-slot:header>Account Settings</x-slot:header>

    <div class="max-w-4xl mx-auto space-y-10">

        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
            @livewire('profile.update-profile-information-form')
        @endif

        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
            <x-section-border />
            @livewire('profile.update-password-form')
        @endif

        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <x-section-border />
            @livewire('profile.two-factor-authentication-form')
        @endif

        <x-section-border />
        @livewire('profile.logout-other-browser-sessions-form')

        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
            <x-section-border />
            @livewire('profile.delete-user-form')
        @endif

    </div>
</x-layouts.platform>

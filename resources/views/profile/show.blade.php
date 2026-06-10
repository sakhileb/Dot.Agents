<x-layouts.platform>
    <x-slot:header>Account Settings</x-slot:header>

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Profile Information --}}
        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Profile Information</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Update your name, email and profile photo.</p>
                </div>
                <div class="p-6">
                    @livewire('profile.update-profile-information-form')
                </div>
            </div>
        @endif

        {{-- Update Password --}}
        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Update Password</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Use a long, random password to keep your account secure.</p>
                </div>
                <div class="p-6">
                    @livewire('profile.update-password-form')
                </div>
            </div>
        @endif

        {{-- Two-Factor Authentication --}}
        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Two-Factor Authentication</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Add a second layer of security to your account.</p>
                </div>
                <div class="p-6">
                    @livewire('profile.two-factor-authentication-form')
                </div>
            </div>
        @endif

        {{-- Browser Sessions --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Browser Sessions</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Manage and log out of your active sessions on other devices.</p>
            </div>
            <div class="p-6">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>
        </div>

        {{-- Delete Account --}}
        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-red-100 dark:border-red-900/40 overflow-hidden">
                <div class="px-6 py-4 border-b border-red-100 dark:border-red-900/40">
                    <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">Danger Zone</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Permanently delete your account and all associated data.</p>
                </div>
                <div class="p-6">
                    @livewire('profile.delete-user-form')
                </div>
            </div>
        @endif

    </div>
</x-layouts.platform>

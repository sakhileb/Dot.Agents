<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
            {{ __('Platform Terms of Service') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Accept Terms to Continue
                </h3>

                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    To use the Dot.Agents platform you must accept our Terms of Service and Privacy Policy.
                    Your data is processed in accordance with GDPR and POPIA regulations.
                </p>

                <form method="POST" action="{{ route('consent.accept') }}">
                    @csrf
                    <div class="flex items-center mb-6">
                        <input id="accept" type="checkbox" name="accept" required
                               class="w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-gray-600">
                        <label for="accept" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            I accept the Platform Terms of Service and Privacy Policy.
                        </label>
                    </div>
                    <x-button type="submit">
                        Accept & Continue
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

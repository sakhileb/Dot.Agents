<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dot.Agents') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-[#f9f9f7]">
        <x-banner />

        <div class="min-h-screen">
            @livewire('navigation-menu')

            @if (isset($header))
                <header class="bg-white border-b border-[#e8e8e2]">
                    <div class="max-w-7xl mx-auto py-5 px-6">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts

        <script>
            // Guard against Alpine.js $wire proxy forwarding `toJSON` as a Livewire server call.
            // When JSON.stringify() runs on an Alpine data context that contains a $wire proxy,
            // JavaScript calls proxy.toJSON(key). Livewire 3's $wire fallback forwards unknown
            // property accesses as server method calls — this filter removes them before dispatch.
            document.addEventListener('livewire:init', () => {
                Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                    if (commit.calls) {
                        commit.calls = commit.calls.filter(call => call.method !== 'toJSON');
                    }
                });
            });
        </script>
    </body>
</html>

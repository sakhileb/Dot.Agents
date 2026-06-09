<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ sidebarOpen: true, darkMode: false }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Dot.Agents') }}</title>
    <link rel="icon" href="/dot.logos3.png" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#f9f9f7] dark:bg-gray-950 text-[#111111] dark:text-gray-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- ═══════════════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════════════════ --}}
    <aside
        x-show="sidebarOpen"
        x-transition:enter="transition ease-in-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-60 flex flex-col bg-[#1e1660]
               lg:relative lg:translate-x-0"
        style="display: flex;"
    >
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-4 border-b border-white/10 flex-shrink-0">
            <img src="/dot.logos3.png" alt="Dot.Agents" class="h-7 w-auto flex-shrink-0">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-white font-display leading-tight">Dot.Agents</p>
                <p class="text-2xs text-white/40 leading-tight">Workforce Platform</p>
            </div>
        </div>

        {{-- Org Switcher --}}
        <div class="px-3 py-3 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-da cursor-pointer
                        bg-white/8 hover:bg-white/12 transition-colors group">
                <div class="w-6 h-6 rounded flex items-center justify-center bg-brand-yellow
                            text-[#1a1400] text-xs font-bold flex-shrink-0">
                    {{ substr(auth()->user()?->currentTeam?->name ?? 'O', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-white truncate leading-tight">
                        {{ auth()->user()?->currentTeam?->name ?? 'Organization' }}
                    </p>
                    <p class="text-2xs text-white/40 leading-tight">Pro Plan</p>
                </div>
                <svg class="w-3 h-3 text-white/30 group-hover:text-white/60 flex-shrink-0 transition-colors"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                </svg>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-2 py-3 overflow-y-auto space-y-0.5" aria-label="Platform navigation">
            @php
                $navItems = [
                    ['label' => 'Dashboard',         'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'route' => 'dashboard'],
                    ['label' => 'Workforce Catalog', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'route' => 'marketplace'],
                    ['label' => 'Active Agents',     'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2', 'route' => 'agents.deployments'],
                    ['label' => 'Workflows',         'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2', 'route' => 'workflows'],
                ];
                $govItems = [
                    ['label' => 'Approvals',        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'route' => 'governance.approvals'],
                    ['label' => 'Audit Logs',       'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'route' => 'governance.audit'],
                    ['label' => 'Decision Logs',    'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'route' => 'governance.decisions'],
                    ['label' => 'Security Center',  'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'route' => 'security.center'],
                ];
                $orgItems = [
                    ['label' => 'Departments',      'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'route' => 'org.departments'],
                    ['label' => 'Members',          'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'route' => 'org.members'],
                    ['label' => 'Knowledge Base',   'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'route' => 'org.knowledge'],
                    ['label' => 'Billing',          'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'route' => 'billing'],
                ];
            @endphp

            @foreach($navItems as $item)
                <a href="{{ route($item['route']) }}"
                   class="da-nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs($item['route']) ? 'page' : 'false' }}">
                    <svg class="nav-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach

            <p class="da-nav-section">Governance</p>
            @foreach($govItems as $item)
                <a href="{{ route($item['route']) }}"
                   class="da-nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                    <svg class="nav-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach

            <p class="da-nav-section">Organization</p>
            @foreach($orgItems as $item)
                <a href="{{ route($item['route']) }}"
                   class="da-nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                    <svg class="nav-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- User footer --}}
        <div class="border-t border-white/10 px-3 py-3 flex-shrink-0">
            <div class="flex items-center gap-2.5 px-2 py-1.5 rounded-da hover:bg-white/8 transition-colors">
                <img class="w-7 h-7 rounded-full object-cover ring-1 ring-brand-yellow/50 flex-shrink-0"
                     src="{{ auth()->user()?->profile_photo_url }}"
                     alt="{{ auth()->user()?->name }}">
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-white truncate leading-tight">{{ auth()->user()?->name }}</p>
                    <p class="text-2xs text-white/40 truncate leading-tight">{{ auth()->user()?->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="text-white/30 hover:text-red-400 transition-colors p-0.5" aria-label="Sign out">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">

        {{-- Top Bar --}}
        <header class="bg-white dark:bg-gray-900 border-b border-[#e8e8e2] dark:border-gray-800
                       px-6 h-14 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen"
                        class="text-[#909088] hover:text-[#111111] dark:hover:text-white transition-colors p-1 -ml-1 rounded"
                        aria-label="Toggle navigation">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                </button>
                @isset($header)
                    <h1 class="text-sm font-semibold text-[#111111] dark:text-white tracking-tight">{{ $header }}</h1>
                @endisset
            </div>

            <div class="flex items-center gap-2">
                <div class="hidden sm:flex items-center gap-1.5 px-3 py-1 bg-green-50 dark:bg-green-900/20
                            rounded-full text-xs font-medium text-green-700 dark:text-green-400
                            border border-green-100 dark:border-green-800">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse-slow"></span>
                    DIS Active
                </div>
                <button class="relative text-[#909088] hover:text-[#111111] dark:hover:text-white p-2 rounded transition-colors"
                        aria-label="Notifications">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                </button>
                <button @click="darkMode = !darkMode"
                        class="text-[#909088] hover:text-[#111111] dark:hover:text-white p-2 rounded transition-colors"
                        aria-label="Toggle dark mode">
                    <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
                <a href="{{ route('profile.show') }}"
                   class="block w-7 h-7 rounded-full overflow-hidden ring-2 ring-[#e8e8e2] hover:ring-brand-purple transition-all">
                    <img src="{{ auth()->user()?->profile_photo_url }}"
                         alt="{{ auth()->user()?->name }}" class="w-full h-full object-cover">
                </a>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="mx-6 mt-4 da-alert da-alert-success" role="alert">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="mx-6 mt-4 da-alert da-alert-critical" role="alert">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto">
            {{ $slot }}
        </main>
    </div>
</div>

{{-- Mobile overlay --}}
<div x-show="sidebarOpen" @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-black/50 lg:hidden" x-cloak></div>

@stack('modals')
@livewireScripts
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('security-alert', (event) => {
            console.warn('Security Alert:', event.message);
        });
    });
</script>
</body>
</html>
        :root {
            --brand-yellow: #f5be1c;
            --brand-yellow-light: #fde989;
            --brand-purple: #3d2ea0;
            --brand-purple-mid: #5b48c8;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">
        {{-- SIDEBAR --}}
        <aside
            x-show="sidebarOpen"
            x-transition:enter="transition ease-in-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col lg:relative lg:translate-x-0"
        >
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                <img src="/dot.logos3.png" alt="Dot.Agents" class="h-8 w-auto">
                <div>
                    <span class="font-bold text-gray-900 dark:text-white text-sm">Dot.Agents</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">AI Workforce Platform</p>
                </div>
            </div>

            {{-- Org Switcher --}}
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-2 px-3 py-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg cursor-pointer hover:bg-purple-100 dark:hover:bg-purple-900/30 transition">
                    <div class="w-7 h-7 rounded-md bg-gradient-to-br from-purple-600 to-purple-800 flex items-center justify-center text-white text-xs font-bold">
                        {{ substr(auth()->user()?->currentTeam?->name ?? 'O', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
                            {{ auth()->user()?->currentTeam?->name ?? 'Organization' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pro Plan</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-1">

                @php
                    $navItems = [
                        ['label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'route' => 'dashboard'],
                        ['label' => 'Agent Marketplace', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'route' => 'marketplace'],
                        ['label' => 'My Agents', 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2', 'route' => 'agents.deployments'],
                        ['label' => 'Workflows', 'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2', 'route' => 'workflows'],
                    ];
                    $govItems = [
                        ['label' => 'Approval Queue', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'route' => 'governance.approvals', 'badge' => 'approvals_count'],
                        ['label' => 'Audit Logs', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'route' => 'governance.audit'],
                        ['label' => 'Decision Logs', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'route' => 'governance.decisions'],
                        ['label' => 'Security Center', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'route' => 'security.center'],
                    ];
                    $orgItems = [
                        ['label' => 'Departments', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'route' => 'org.departments'],
                        ['label' => 'Team Members', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'route' => 'org.members'],
                        ['label' => 'Knowledge Base', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'route' => 'org.knowledge'],
                        ['label' => 'Billing', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'route' => 'billing'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs($item['route']) ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Governance</p>
                </div>

                @foreach($govItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs($item['route']) ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Organization</p>
                </div>

                @foreach($orgItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs($item['route']) ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- User section --}}
            <div class="border-t border-gray-200 dark:border-gray-800 px-4 py-4">
                <div class="flex items-center gap-3">
                    <img class="w-8 h-8 rounded-full object-cover ring-2 ring-yellow-400"
                         src="{{ auth()->user()?->profile_photo_url }}"
                         alt="{{ auth()->user()?->name }}">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()?->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()?->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Top bar --}}
            <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-6 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-900 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    </button>
                    @isset($header)
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $header }}</h1>
                    @endisset
                </div>

                <div class="flex items-center gap-3">
                    {{-- DIS Status Indicator --}}
                    <div class="flex items-center gap-1.5 px-3 py-1.5 bg-green-50 dark:bg-green-900/20 rounded-full text-xs font-medium text-green-700 dark:text-green-400">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                        DIS Active
                    </div>

                    {{-- Notifications --}}
                    <button class="relative text-gray-500 hover:text-gray-900 dark:hover:text-white p-1.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-0.5 right-0.5 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    {{-- Dark mode toggle --}}
                    <button @click="darkMode = !darkMode" class="text-gray-500 hover:text-gray-900 dark:hover:text-white p-1.5">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                </div>
            </header>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="mx-6 mt-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    <script>
        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('security-alert', (event) => {
                alert('Security Alert: ' + event.message);
            });
        });
    </script>
</body>
</html>

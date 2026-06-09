<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dot.Agents — Enterprise Digital Workforce Platform</title>
    <meta name="description" content="Deploy specialized AI agents across departments, workflows, and operations — with full governance, accountability, and measurable outcomes.">
    <link rel="icon" href="/dot.logos3.png" type="image/png">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="font-sans antialiased bg-white text-[#111111]">

{{-- ═══════════════════════════════════════════════
     NAVIGATION
════════════════════════════════════════════════ --}}
<nav class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur border-b border-[#e8e8e2]">
    <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2.5">
            <img src="/dot.logos3.png" alt="Dot.Agents" class="h-8 w-auto">
            <span class="font-semibold text-[#111111] font-display text-base">Dot.Agents</span>
        </a>

        <div class="hidden md:flex items-center gap-8">
            <a href="#capabilities" class="text-sm text-[#555550] hover:text-[#111111] transition-colors">Capabilities</a>
            <a href="#governance" class="text-sm text-[#555550] hover:text-[#111111] transition-colors">Governance</a>
            <a href="#departments" class="text-sm text-[#555550] hover:text-[#111111] transition-colors">Departments</a>
        </div>

        <div class="flex items-center gap-3">
            @auth
                <a href="{{ url('/dashboard') }}" class="da-btn-primary da-btn-sm">
                    Open Platform
                </a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-[#555550] hover:text-[#111111] transition-colors font-medium">
                    Sign In
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="da-btn-primary da-btn-sm">
                        Get Started
                    </a>
                @endif
            @endauth
        </div>
    </div>
</nav>

{{-- ═══════════════════════════════════════════════
     HERO
════════════════════════════════════════════════ --}}
<section class="bg-[#1e1660] pt-32 pb-24 px-6 relative overflow-hidden">
    {{-- Background texture --}}
    <div class="absolute inset-0 opacity-[0.04]"
         style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 32px 32px;"></div>
    <div class="absolute bottom-0 right-0 w-[600px] h-[600px] bg-brand-purple-mid/15 rounded-full blur-3xl translate-x-1/3 translate-y-1/3"></div>
    <div class="absolute top-0 left-0 w-[400px] h-[400px] bg-brand-yellow/5 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>

    <div class="max-w-4xl mx-auto relative z-10 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 border border-white/15 rounded-full px-4 py-1.5 mb-8">
            <span class="w-1.5 h-1.5 bg-brand-yellow rounded-full"></span>
            <span class="text-white/70 text-xs font-medium">Enterprise Digital Workforce Platform</span>
        </div>

        <h1 class="text-5xl md:text-6xl font-bold text-white font-display leading-tight mb-6">
            Manage Digital Workforces<br>
            <span class="text-brand-yellow">Like Real Teams</span>
        </h1>

        <p class="text-xl text-white/55 leading-relaxed max-w-2xl mx-auto mb-10">
            Deploy specialized agents across departments, workflows, and operations
            while maintaining governance, accountability, and measurable outcomes.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            @if (Route::has('register'))
                <a href="{{ route('register') }}"
                   class="da-btn-yellow da-btn-lg px-8 font-semibold shadow-lg">
                    Start Building
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            @endif
            <a href="#capabilities"
               class="da-btn text-white/70 hover:text-white border border-white/20 hover:border-white/40 bg-transparent hover:bg-white/8 da-btn-lg px-8">
                Explore Marketplace
            </a>
        </div>

        {{-- Stats row --}}
        <div class="mt-16 pt-12 border-t border-white/10 grid grid-cols-2 md:grid-cols-4 gap-8">
            @foreach([
                ['value' => '400+', 'label' => 'Enterprise Clients'],
                ['value' => '50+', 'label' => 'Agent Specializations'],
                ['value' => '99.9%', 'label' => 'Platform Uptime'],
                ['value' => '< 48h', 'label' => 'Average Deployment'],
            ] as $stat)
                <div class="text-center">
                    <p class="text-3xl font-bold text-white font-display">{{ $stat['value'] }}</p>
                    <p class="text-sm text-white/40 mt-1">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════
     DEPARTMENT SHOWCASE
════════════════════════════════════════════════ --}}
<section id="departments" class="py-24 px-6 bg-[#f9f9f7]">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-14">
            <p class="text-xs font-semibold uppercase tracking-widest text-brand-purple mb-3">Workforce Departments</p>
            <h2 class="text-3xl font-bold font-display text-[#111111]">
                Agents for every department
            </h2>
            <p class="text-[#909088] mt-3 max-w-xl mx-auto">
                Purpose-built agents that understand business context — not generic AI tools.
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            @foreach([
                ['dept' => 'Finance',    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 16v-1m0-2c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2 1.343-2 3-2m0 8c.89 0 1.77-.195 2.599-1M12 12v-1', 'color' => 'bg-green-50 text-green-700'],
                ['dept' => 'Operations', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'color' => 'bg-blue-50 text-blue-700'],
                ['dept' => 'HR',         'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'bg-purple-50 text-purple-700'],
                ['dept' => 'IT',         'icon' => 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18', 'color' => 'bg-gray-100 text-gray-700'],
                ['dept' => 'Sales',      'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'color' => 'bg-yellow-50 text-yellow-700'],
                ['dept' => 'Marketing',  'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z', 'color' => 'bg-pink-50 text-pink-700'],
                ['dept' => 'Executive',  'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'color' => 'bg-brand-purple-pale text-brand-purple'],
            ] as $dept)
                <div class="da-card p-4 text-center hover:shadow-da-md transition-all hover:-translate-y-0.5 cursor-default">
                    <div class="w-10 h-10 rounded-lg {{ $dept['color'] }} flex items-center justify-center mx-auto mb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $dept['icon'] }}"/>
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-[#111111]">{{ $dept['dept'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════
     PLATFORM CAPABILITIES
════════════════════════════════════════════════ --}}
<section id="capabilities" class="py-24 px-6 bg-white">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-14">
            <p class="text-xs font-semibold uppercase tracking-widest text-brand-purple mb-3">Platform Capabilities</p>
            <h2 class="text-3xl font-bold font-display text-[#111111]">
                Built for operational excellence
            </h2>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['title' => 'Workforce Deployment',   'desc' => 'Deploy agents to specific departments with configured autonomy levels — from advisory to fully autonomous execution.', 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2'],
                ['title' => 'Agent Marketplace',      'desc' => 'Browse a curated catalog of specialized agents by department, function, and performance score — install in minutes.', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
                ['title' => 'Workflow Automation',    'desc' => 'Design multi-agent workflows visually. Connect agents, set conditions, and automate entire business processes without code.', 'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'],
                ['title' => 'Governance & Compliance', 'desc' => 'Every decision is logged, scored, and auditable. Approval workflows ensure humans remain in control at defined confidence thresholds.', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                ['title' => 'Performance Analytics',  'desc' => 'Track agent performance across 10 dimensions — accuracy, speed, cost, reliability, safety, and more — with actionable scorecards.', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['title' => 'Multi-Tenant Organizations', 'desc' => 'Full organizational hierarchy — divisions, departments, teams — with role-based permissions and complete tenant data isolation.', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ] as $cap)
                <div class="da-card p-6 hover:shadow-da-md transition-all">
                    <div class="w-10 h-10 bg-brand-purple-pale rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-brand-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $cap['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-[#111111] mb-2">{{ $cap['title'] }}</h3>
                    <p class="text-sm text-[#909088] leading-relaxed">{{ $cap['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════
     ENTERPRISE GOVERNANCE
════════════════════════════════════════════════ --}}
<section id="governance" class="py-24 px-6 bg-[#f9f9f7]">
    <div class="max-w-6xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-brand-purple mb-4">Enterprise Governance</p>
                <h2 class="text-3xl font-bold font-display text-[#111111] mb-6">
                    Control, visibility,<br>and accountability
                </h2>
                <p class="text-[#555550] leading-relaxed mb-8">
                    Every agent action is logged, scored, and auditable. No agent operates outside the boundaries you define. Humans remain in the decision loop — exactly where governance requires.
                </p>

                <div class="space-y-5">
                    @foreach([
                        ['title' => 'Immutable Audit Trails', 'desc' => 'Every action timestamped and linked to users, agents, and decisions.'],
                        ['title' => 'Confidence-Based Approvals', 'desc' => 'Tasks below your threshold automatically escalate to human review.'],
                        ['title' => 'Delusion Detection', 'desc' => 'Automated hallucination risk scoring on every agent output.'],
                        ['title' => 'Digital Immune System', 'desc' => 'Continuous threat and drift monitoring across all deployments.'],
                    ] as $item)
                        <div class="flex gap-4">
                            <div class="w-5 h-5 rounded-full bg-brand-purple-pale flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-2.5 h-2.5 text-brand-purple" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-[#111111]">{{ $item['title'] }}</p>
                                <p class="text-sm text-[#909088]">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Governance dashboard preview --}}
            <div class="da-card overflow-hidden">
                <div class="bg-[#1e1660] px-5 py-3 flex items-center gap-2">
                    <div class="flex gap-1.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-white/20"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-white/20"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-white/20"></div>
                    </div>
                    <span class="text-white/50 text-xs ml-2">Governance Center</span>
                </div>
                <div class="p-5 space-y-3">
                    @foreach([
                        ['label' => 'DIS Status',       'value' => 'All Clear',       'badge' => 'da-badge-green'],
                        ['label' => 'Pending Approvals','value' => '3 awaiting',      'badge' => 'da-badge-yellow'],
                        ['label' => 'Audit Events (24h)','value' => '1,847 recorded', 'badge' => 'da-badge-gray'],
                        ['label' => 'Threat Signals',   'value' => '0 detected',      'badge' => 'da-badge-green'],
                        ['label' => 'Avg. Trust Score', 'value' => '94 / 100',        'badge' => 'da-badge-purple'],
                    ] as $row)
                        <div class="flex items-center justify-between py-2 border-b border-[#f3f3ef] last:border-0">
                            <span class="text-xs text-[#555550]">{{ $row['label'] }}</span>
                            <span class="{{ $row['badge'] }}">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════
     OUTCOME METRICS
════════════════════════════════════════════════ --}}
<section class="py-24 px-6 bg-[#1e1660]">
    <div class="max-w-5xl mx-auto text-center">
        <p class="text-xs font-semibold uppercase tracking-widest text-brand-yellow mb-4">Measurable Outcomes</p>
        <h2 class="text-3xl font-bold font-display text-white mb-14">
            Real results for real organizations
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['value' => '68%',    'label' => 'Reduction in manual processing time', 'sub' => 'Operations department'],
                ['value' => '3.2×',   'label' => 'Faster workflow completion',           'sub' => 'Cross-department average'],
                ['value' => '94%',    'label' => 'Automated decision accuracy',          'sub' => 'With governance controls'],
                ['value' => '12 hrs', 'label' => 'Average time saved per team per week', 'sub' => 'Across all deployments'],
            ] as $metric)
                <div class="bg-white/8 border border-white/10 rounded-lg p-6">
                    <p class="text-4xl font-bold text-brand-yellow font-display mb-2">{{ $metric['value'] }}</p>
                    <p class="text-sm text-white/80 font-medium leading-snug mb-1">{{ $metric['label'] }}</p>
                    <p class="text-xs text-white/35">{{ $metric['sub'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════
     CTA
════════════════════════════════════════════════ --}}
<section class="py-24 px-6 bg-white">
    <div class="max-w-2xl mx-auto text-center">
        <h2 class="text-3xl font-bold font-display text-[#111111] mb-4">
            Ready to build your digital workforce?
        </h2>
        <p class="text-[#909088] mb-10 leading-relaxed">
            Start deploying agents to your teams today. No engineering required.
            Full governance from day one.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="da-btn-primary da-btn-lg px-8 font-semibold">
                    Start Building — It's Free
                </a>
            @endif
            <a href="{{ route('login') }}" class="da-btn-secondary da-btn-lg px-8">
                Sign In
            </a>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════ --}}
<footer class="bg-[#f9f9f7] border-t border-[#e8e8e2] py-12 px-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-2.5">
                <img src="/dot.logos3.png" alt="Dot.Agents" class="h-7 w-auto">
                <span class="font-semibold text-[#111111] font-display">Dot.Agents</span>
            </div>

            <div class="flex items-center gap-8">
                @if (Route::has('terms.show'))
                    <a href="{{ route('terms.show') }}" class="text-sm text-[#909088] hover:text-[#111111] transition-colors">Terms</a>
                @endif
                @if (Route::has('policy.show'))
                    <a href="{{ route('policy.show') }}" class="text-sm text-[#909088] hover:text-[#111111] transition-colors">Privacy</a>
                @endif
            </div>

            <p class="text-sm text-[#909088]">
                &copy; {{ date('Y') }} Dot.Agents. All rights reserved.
            </p>
        </div>
    </div>
</footer>

</body>
</html>


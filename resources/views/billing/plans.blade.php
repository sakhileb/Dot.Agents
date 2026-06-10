<x-layouts.platform>
    <x-slot:header>Billing Plans</x-slot:header>

    <div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="text-center py-6">
            <h2 class="text-2xl font-bold text-[#111111] dark:text-white font-display">Choose Your Plan</h2>
            <p class="text-sm text-[#909088] dark:text-gray-400 mt-2">Scale your AI workforce with flexible pricing.</p>
        </div>

        {{-- Plans Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">

            {{-- Starter --}}
            <div class="da-card p-6 flex flex-col">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#909088]">Starter</p>
                    <div class="mt-2 flex items-end gap-1">
                        <span class="text-3xl font-bold text-[#111111] dark:text-white font-display">$49</span>
                        <span class="text-sm text-[#909088] mb-1">/mo</span>
                    </div>
                    <p class="text-xs text-[#909088] mt-1">Up to 3 active agents</p>
                </div>
                <ul class="space-y-2 text-sm text-[#555550] dark:text-gray-300 flex-1 mb-6">
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>3 Agent Deployments</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Basic Governance</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Audit Logs (30 days)</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Email Support</li>
                </ul>
                <form method="POST" action="{{ route('billing.checkout', 'starter') }}">
                    @csrf
                    <button type="submit" class="da-btn-secondary w-full">Get Started</button>
                </form>
            </div>

            {{-- Pro (highlighted) --}}
            <div class="da-card p-6 flex flex-col ring-2 ring-brand-purple relative">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-purple text-white text-2xs font-semibold uppercase tracking-wider px-3 py-1 rounded-full">Most Popular</span>
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-purple">Pro</p>
                    <div class="mt-2 flex items-end gap-1">
                        <span class="text-3xl font-bold text-[#111111] dark:text-white font-display">$149</span>
                        <span class="text-sm text-[#909088] mb-1">/mo</span>
                    </div>
                    <p class="text-xs text-[#909088] mt-1">Up to 15 active agents</p>
                </div>
                <ul class="space-y-2 text-sm text-[#555550] dark:text-gray-300 flex-1 mb-6">
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>15 Agent Deployments</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Full Governance Suite</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Audit Logs (1 year)</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Workflow Builder</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Priority Support</li>
                </ul>
                <form method="POST" action="{{ route('billing.checkout', 'pro') }}">
                    @csrf
                    <button type="submit" class="da-btn-primary w-full">Upgrade to Pro</button>
                </form>
            </div>

            {{-- Enterprise --}}
            <div class="da-card p-6 flex flex-col">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#909088]">Enterprise</p>
                    <div class="mt-2 flex items-end gap-1">
                        <span class="text-3xl font-bold text-[#111111] dark:text-white font-display">Custom</span>
                    </div>
                    <p class="text-xs text-[#909088] mt-1">Unlimited agents &amp; custom SLA</p>
                </div>
                <ul class="space-y-2 text-sm text-[#555550] dark:text-gray-300 flex-1 mb-6">
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Unlimited Deployments</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Custom Governance Rules</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Dedicated Infra</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>SSO &amp; SCIM</li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>24/7 Dedicated Support</li>
                </ul>
                <a href="mailto:sales@infodot.co.za" class="da-btn-secondary w-full text-center">Contact Sales</a>
            </div>

        </div>

        {{-- FAQ --}}
        <div class="max-w-2xl mx-auto pt-6">
            <p class="text-xs text-center text-[#909088]">All plans include a 14-day free trial. No credit card required. <a href="{{ route('billing.index') }}" class="text-brand-purple hover:underline">Manage current subscription →</a></p>
        </div>

    </div>
</x-layouts.platform>

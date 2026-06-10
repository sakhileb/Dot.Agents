<x-layouts.platform>
    <x-slot:header>Billing Settings</x-slot:header>

    <div class="p-6 max-w-3xl space-y-6">

        {{-- Current Plan --}}
        <div class="da-card">
            <div class="da-card-header">
                <h2 class="text-sm font-semibold text-[#111111] dark:text-white">Current Subscription</h2>
                <a href="{{ route('billing.plans') }}" class="da-btn-secondary da-btn-sm">Change Plan</a>
            </div>
            <div class="da-card-body flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-xs text-[#909088] uppercase tracking-wide">Active Plan</p>
                    <p class="text-base font-semibold text-[#111111] dark:text-white mt-0.5">Pro — $149/mo</p>
                    <p class="text-xs text-[#909088] mt-0.5">Renews on <strong>July 10, 2026</strong></p>
                </div>
                <span class="da-badge-green">Active</span>
            </div>
        </div>

        {{-- Payment Method --}}
        <div class="da-card">
            <div class="da-card-header">
                <h2 class="text-sm font-semibold text-[#111111] dark:text-white">Payment Method</h2>
                <form method="POST" action="{{ route('billing.portal') }}">
                    @csrf
                    <button type="submit" class="da-btn-secondary da-btn-sm">Manage via Portal</button>
                </form>
            </div>
            <div class="da-card-body flex items-center gap-4">
                <div class="w-10 h-7 bg-[#f3f3ef] dark:bg-gray-800 rounded flex items-center justify-center">
                    <svg class="w-6 h-4 text-[#555550]" fill="currentColor" viewBox="0 0 24 16">
                        <rect width="24" height="16" rx="2" fill="#e8e8e2"/>
                        <circle cx="9" cy="8" r="4" fill="#909088" opacity=".8"/>
                        <circle cx="15" cy="8" r="4" fill="#555550" opacity=".6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-[#111111] dark:text-white">•••• •••• •••• 4242</p>
                    <p class="text-xs text-[#909088]">Expires 12/27</p>
                </div>
            </div>
        </div>

        {{-- Invoices --}}
        <div class="da-card">
            <div class="da-card-header">
                <h2 class="text-sm font-semibold text-[#111111] dark:text-white">Recent Invoices</h2>
                <form method="POST" action="{{ route('billing.portal') }}">
                    @csrf
                    <button type="submit" class="da-btn-secondary da-btn-sm">View All</button>
                </form>
            </div>
            <div class="da-card-body">
                <table class="da-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-[#909088]">Jun 1, 2026</td>
                            <td>Pro Plan — Monthly</td>
                            <td>$149.00</td>
                            <td><span class="da-badge-green">Paid</span></td>
                        </tr>
                        <tr>
                            <td class="text-[#909088]">May 1, 2026</td>
                            <td>Pro Plan — Monthly</td>
                            <td>$149.00</td>
                            <td><span class="da-badge-green">Paid</span></td>
                        </tr>
                    </tbody>
                </table>
                <p class="text-xs text-[#909088] mt-4">Invoice history is managed via the <a href="#" class="text-brand-purple hover:underline">Stripe billing portal</a>.</p>
            </div>
        </div>

        {{-- Cancel --}}
        <div class="da-card border-red-100 dark:border-red-900/30">
            <div class="da-card-header">
                <h2 class="text-sm font-semibold text-red-700 dark:text-red-400">Cancel Subscription</h2>
            </div>
            <div class="da-card-body flex items-center justify-between gap-4 flex-wrap">
                <p class="text-sm text-[#555550] dark:text-gray-400">Cancelling will downgrade your account at the end of the current billing period.</p>
                <form method="POST" action="{{ route('billing.portal') }}">
                    @csrf
                    <button type="submit" class="da-btn-danger da-btn-sm">Cancel via Portal</button>
                </form>
            </div>
        </div>

    </div>
</x-layouts.platform>

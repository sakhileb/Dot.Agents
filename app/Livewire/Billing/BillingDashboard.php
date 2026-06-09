<?php

namespace App\Livewire\Billing;

use App\Models\Invoice;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\UsageRecord;
use Livewire\Component;

class BillingDashboard extends Component
{
    public function getOrganizationIdProperty(): ?int
    {
        return session('current_organization_id');
    }

    public function getSubscriptionProperty(): ?OrganizationSubscription
    {
        return OrganizationSubscription::with('plan')
            ->where('organization_id', $this->organizationId)
            ->where('status', 'active')
            ->first();
    }

    public function getPlansProperty()
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get();
    }

    public function getCurrentMonthUsageProperty(): array
    {
        $records = UsageRecord::where('organization_id', $this->organizationId)
            ->whereMonth('recorded_date', now()->month)
            ->whereYear('recorded_date', now()->year)
            ->get();

        return [
            'tokens' => $records->where('metric_type', 'tokens')->sum('quantity'),
            'tasks' => $records->where('metric_type', 'tasks')->sum('quantity'),
            'api_calls' => $records->where('metric_type', 'api_calls')->sum('quantity'),
            'total_cost' => $records->sum('total_cost'),
        ];
    }

    public function getInvoicesProperty()
    {
        return Invoice::where('organization_id', $this->organizationId)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();
    }

    public function render()
    {
        return view('livewire.billing.billing-dashboard');
    }
}

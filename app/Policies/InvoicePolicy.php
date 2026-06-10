<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * InvoicePolicy — billing records are highly sensitive.
 *
 * Only owners and admins may view financial records.
 * No user may ever create, update, or delete invoices directly —
 * that is handled exclusively through the billing system (Stripe webhooks).
 */
class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        $orgId = (int) session('current_organization_id');

        return $user->organizations()
            ->where('organizations.id', $orgId)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        $orgId = (int) session('current_organization_id');

        return $invoice->organization_id === $orgId
            && $user->organizations()
                ->where('organizations.id', $orgId)
                ->wherePivotIn('role', ['owner', 'admin'])
                ->exists();
    }

    public function create(User $user): bool
    {
        return false; // System-only via Stripe webhook
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return false; // System-only via Stripe webhook
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return false; // Financial records must be retained per compliance requirements
    }
}

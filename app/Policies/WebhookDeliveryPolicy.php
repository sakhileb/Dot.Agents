<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookDelivery;

class WebhookDeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WebhookDelivery $delivery): bool
    {
        return $user->organizations()->where('organizations.id', $delivery->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Deliveries are system-generated only
    }

    public function update(User $user, WebhookDelivery $delivery): bool
    {
        return false; // Delivery records are immutable
    }

    public function delete(User $user, WebhookDelivery $delivery): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function restore(User $user, WebhookDelivery $delivery): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function forceDelete(User $user, WebhookDelivery $delivery): bool
    {
        return $user->hasRole('platform_admin');
    }
}

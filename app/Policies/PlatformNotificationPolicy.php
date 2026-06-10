<?php

namespace App\Policies;

use App\Models\PlatformNotification;
use App\Models\User;

class PlatformNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    /** Users can only view notifications addressed to their organization or to them personally. */
    public function view(User $user, PlatformNotification $notification): bool
    {
        // Notification addressed to this user specifically
        if ($notification->user_id && $notification->user_id === $user->id) {
            return true;
        }

        // Notification addressed to this user's organization
        return $notification->organization_id !== null
            && $user->organizations()
                ->where('organizations.id', $notification->organization_id)
                ->exists();
    }

    /** Notifications are system-generated — no user creation. */
    public function create(User $user): bool
    {
        return false;
    }

    /** Users can mark their own notifications as read (treated as an update). */
    public function update(User $user, PlatformNotification $notification): bool
    {
        return $this->view($user, $notification);
    }

    /** Users can dismiss/delete their own notifications. */
    public function delete(User $user, PlatformNotification $notification): bool
    {
        return $this->view($user, $notification);
    }

    public function forceDelete(User $user, PlatformNotification $notification): bool
    {
        return false;
    }
}

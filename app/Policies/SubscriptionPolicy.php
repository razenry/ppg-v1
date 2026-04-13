<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Subscription $subscription)
    {
        return $user->id === $subscription->user_id;
    }

    public function create(User $user)
    {
        return true;
    } // Users can subscribe

    public function update(User $user, Subscription $subscription)
    {
        return $user->id === $subscription->user_id;
    }

    public function delete(User $user, Subscription $subscription)
    {
        return false; // Typically users can't hard-delete subs, just cancel
    }
}

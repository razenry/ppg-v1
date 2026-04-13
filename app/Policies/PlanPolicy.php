<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($ability === 'viewAny' || $ability === 'view') {
            return true; // Users might need to view available plans
        }

        return false;
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Plan $plan)
    {
        return true;
    }

    public function create(User $user)
    {
        return false;
    }

    public function update(User $user, Plan $plan)
    {
        return false;
    }

    public function delete(User $user, Plan $plan)
    {
        return false;
    }

    public function restore(User $user, Plan $plan)
    {
        return false;
    }

    public function forceDelete(User $user, Plan $plan)
    {
        return false;
    }
}

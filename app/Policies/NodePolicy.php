<?php

namespace App\Policies;

use App\Models\Node;
use App\Models\User;

class NodePolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false; // Nodes are completely restricted to admins
    }

    // Following methods will be intercepted by before()
    public function viewAny(User $user)
    {
        return false;
    }

    public function view(User $user, Node $node)
    {
        return false;
    }

    public function create(User $user)
    {
        return false;
    }

    public function update(User $user, Node $node)
    {
        return false;
    }

    public function delete(User $user, Node $node)
    {
        return false;
    }

    public function restore(User $user, Node $node)
    {
        return false;
    }

    public function forceDelete(User $user, Node $node)
    {
        return false;
    }
}

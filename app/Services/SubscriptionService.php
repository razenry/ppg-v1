<?php

namespace App\Services;

use App\Jobs\DeleteServerJob;
use App\Jobs\ProvisionServerJob;
use App\Models\Subscription;

class SubscriptionService
{
    /**
     * Handle status change of a subscription.
     */
    public function handleStatusChange(Subscription $subscription, string $oldStatus): void
    {
        $newStatus = $subscription->status;

        if ($newStatus === 'active') {
            $this->activateServers($subscription);
        } elseif ($newStatus === 'suspended') {
            $this->suspendServers($subscription);
        } elseif ($newStatus === 'terminated') {
            $this->terminateServers($subscription);
        }
    }

    protected function activateServers(Subscription $subscription): void
    {
        foreach ($subscription->servers as $server) {
            $server->update(['status' => 'pending']);
            ProvisionServerJob::dispatch($server);
        }
    }

    protected function suspendServers(Subscription $subscription): void
    {
        foreach ($subscription->servers as $server) {
            DeleteServerJob::dispatch($server, false); // false = don't delete from DB
            $server->update(['status' => 'suspended']);
        }
    }

    protected function terminateServers(Subscription $subscription): void
    {
        foreach ($subscription->servers as $server) {
            DeleteServerJob::dispatch($server, true); // true = delete from DB
        }
        $subscription->delete();
    }
}

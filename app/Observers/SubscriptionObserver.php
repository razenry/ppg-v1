<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Services\SubscriptionService;

class SubscriptionObserver
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    /**
     * Handle the Subscription "updated" event.
     */
    public function updated(Subscription $subscription): void
    {
        if ($subscription->isDirty('status')) {
            $this->subscriptionService->handleStatusChange(
                $subscription,
                $subscription->getOriginal('status')
            );
        }
    }
}

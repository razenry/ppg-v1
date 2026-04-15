<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\ServerService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired subscriptions and suspend associated servers';

    /**
     * Execute the console command.
     */
    public function handle(ServerService $serverService): void
    {
        $this->info('Checking for expired subscriptions...');

        $expiredSubscriptions = Subscription::where('status', 'active')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', Carbon::now())
            ->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('No newly expired subscriptions found.');

            return;
        }

        foreach ($expiredSubscriptions as $subscription) {
            $this->warn("Suspending subscription ID: {$subscription->id} for user ID: {$subscription->user_id}");

            $serverService->suspend($subscription);

            $subscription->update(['status' => 'suspended']);

            $this->info("Subscription {$subscription->id} and its servers have been suspended.");
        }

        $this->info('Done.');
    }
}

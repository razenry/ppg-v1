<?php

namespace App\Console\Commands;

use App\Livewire\Admin\UserManager;
use App\Models\Subscription;
use Illuminate\Console\Command;

class DebugRenew extends Command
{
    protected $signature = 'debug:renew';

    public function handle()
    {
        $sub = Subscription::latest()->first();
        if (! $sub) {
            $this->info('No subs found');

            return;
        }

        $this->info("Latest sub: ID {$sub->id}, status {$sub->status}, expired_at {$sub->expired_at}");

        try {
            $manager = new UserManager;
            $manager->manageSubUserId = $sub->user_id;
            $manager->renewSubscription($sub->id);
            $this->info('Renew method successfully executed');

            $sub->refresh();
            $this->info("After renew: status {$sub->status}, expired_at {$sub->expired_at}");
        } catch (\Exception $e) {
            $this->error('Exception: '.$e->getMessage().' on line '.$e->getLine().' in '.$e->getFile());
        }
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserManager;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagerSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    /**
     * Bug fix: assignSubscription was using CarbonImmutable incorrectly
     * (calling addDays() without capturing the return value), which caused
     * the subscription to be created with expired_at = now() (immediately expired).
     */
    public function test_assign_subscription_sets_future_expiry_date(): void
    {
        Setting::set('subscription_duration_default', ['value' => 30, 'unit' => 'days']);

        $plan = Plan::factory()->create(['max_server' => 5]);
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UserManager::class)
            ->call('openSubModal', $user->id)
            ->set('selectedPlanId', $plan->id)
            ->call('assignSubscription');

        $sub = Subscription::where('user_id', $user->id)->first();

        $this->assertNotNull($sub, 'Subscription should have been created');
        $this->assertNotNull($sub->expired_at, 'expired_at should be set');
        $this->assertTrue(
            $sub->expired_at->isFuture(),
            "expired_at should be in the future, got: {$sub->expired_at}"
        );
        $this->assertTrue(
            $sub->expired_at->greaterThan(now()->addDays(25)),
            'expired_at should be ~30 days from now'
        );
    }

    public function test_assign_subscription_respects_months_unit(): void
    {
        Setting::set('subscription_duration_default', ['value' => 3, 'unit' => 'months']);

        $plan = Plan::factory()->create(['max_server' => 5]);
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UserManager::class)
            ->call('openSubModal', $user->id)
            ->set('selectedPlanId', $plan->id)
            ->call('assignSubscription');

        $sub = Subscription::where('user_id', $user->id)->first();

        $this->assertNotNull($sub->expired_at);
        $this->assertTrue($sub->expired_at->isFuture());
        $this->assertTrue($sub->expired_at->greaterThan(now()->addMonths(2)));
    }

    public function test_assign_subscription_respects_years_unit(): void
    {
        Setting::set('subscription_duration_default', ['value' => 1, 'unit' => 'years']);

        $plan = Plan::factory()->create(['max_server' => 5]);
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UserManager::class)
            ->call('openSubModal', $user->id)
            ->set('selectedPlanId', $plan->id)
            ->call('assignSubscription');

        $sub = Subscription::where('user_id', $user->id)->first();

        $this->assertNotNull($sub->expired_at);
        $this->assertTrue($sub->expired_at->isFuture());
        $this->assertTrue($sub->expired_at->greaterThan(now()->addMonths(11)));
    }

    /**
     * Bug fix: renewSubscription when subscription status was already 'active' (but expired_at
     * in past) did not re-provision servers because the observer's isDirty('status') check
     * returned false (status didn't change). restoreServers() is now called explicitly.
     */
    public function test_renew_sets_future_expiry_date(): void
    {
        Setting::set('subscription_duration_default', ['value' => 30, 'unit' => 'days']);

        $user = User::factory()->create();
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'suspended',
            'expired_at' => now()->subDays(5),
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserManager::class)
            ->call('openSubModal', $user->id)
            ->call('renewSubscription', $sub->id);

        $sub->refresh();

        $this->assertEquals('active', $sub->status);
        $this->assertTrue(
            $sub->expired_at->isFuture(),
            "After renew, expired_at should be in the future, got: {$sub->expired_at}"
        );
        $this->assertTrue($sub->expired_at->greaterThan(now()->addDays(25)));
    }

    public function test_renew_active_but_expired_subscription_also_sets_future_expiry(): void
    {
        Setting::set('subscription_duration_default', ['value' => 30, 'unit' => 'days']);

        $user = User::factory()->create();
        // Status is still 'active' but expired_at is in the past (cron hasn't run yet)
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expired_at' => now()->subDays(1),
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserManager::class)
            ->call('openSubModal', $user->id)
            ->call('renewSubscription', $sub->id);

        $sub->refresh();

        $this->assertEquals('active', $sub->status);
        $this->assertTrue(
            $sub->expired_at->isFuture(),
            "After renew, expired_at should be in the future, got: {$sub->expired_at}"
        );
    }
}

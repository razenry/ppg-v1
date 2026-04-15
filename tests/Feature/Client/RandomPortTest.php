<?php

namespace Tests\Feature\Client;

use App\Livewire\Client\ServerManager;
use App\Models\Node;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RandomPortTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_creation_generates_random_dest_port_and_uses_manual_src_port(): void
    {
        Http::fake([
            '*/api/admin/gate' => Http::response(['data' => 'test-domain.raznar.net'], 200),
        ]);

        $user = User::factory()->create();
        $node = Node::factory()->create(['status' => 'online']);
        $plan = Plan::factory()->create(['max_server' => 5]);
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expired_at' => now()->addDays(30),
            'max_server' => 5,
            'plan_name' => $plan->name,
        ]);

        Livewire::actingAs($user)
            ->test(ServerManager::class)
            ->set('label', 'Test Server')
            ->set('identifier', 'test-random-port')
            ->set('src_ip', '1.1.1.1')
            ->set('src_port', 30120)
            ->set('node_id', $node->id)
            ->set('subscription_id', $sub->id)
            ->call('createServer')
            ->assertHasNoErrors();

        $server = $user->servers()->first();

        $this->assertNotNull($server);
        $this->assertEquals(30120, $server->src_port);
        $this->assertNotNull($server->dest_port);
        $this->assertGreaterThanOrEqual(20000, $server->dest_port);
        $this->assertLessThanOrEqual(60000, $server->dest_port);
    }

    public function test_multiple_servers_on_same_node_get_different_dest_ports(): void
    {
        Http::fake([
            '*/api/admin/gate' => Http::response(['data' => 'test-domain.raznar.net'], 200),
        ]);

        $user = User::factory()->create();
        $node = Node::factory()->create(['status' => 'online']);
        $plan = Plan::factory()->create(['max_server' => 5]);
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expired_at' => now()->addDays(30),
            'max_server' => 5,
            'plan_name' => $plan->name,
        ]);

        // Create first server
        Livewire::actingAs($user)
            ->test(ServerManager::class)
            ->set('label', 'Server 1')
            ->set('identifier', 'server1')
            ->set('src_ip', '1.1.1.1')
            ->set('src_port', 30120)
            ->set('node_id', $node->id)
            ->set('subscription_id', $sub->id)
            ->call('createServer');

        // Create second server
        Livewire::actingAs($user)
            ->test(ServerManager::class)
            ->set('label', 'Server 2')
            ->set('identifier', 'server2')
            ->set('src_ip', '1.1.1.2')
            ->set('src_port', 30120)
            ->set('node_id', $node->id)
            ->set('subscription_id', $sub->id)
            ->call('createServer');

        $servers = $user->servers()->get();
        $this->assertCount(2, $servers);
        $this->assertNotEquals($servers[0]->dest_port, $servers[1]->dest_port);
    }
}

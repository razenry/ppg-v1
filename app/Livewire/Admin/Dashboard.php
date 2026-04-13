<?php

namespace App\Livewire\Admin;

use App\Models\Node;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'total_users' => User::where('is_admin', false)->count(),
            'total_servers' => Server::count(),
            'active_servers' => Server::where('status', 'active')->count(),
            'pending_servers' => Server::where('status', 'pending')->count(),
            'failed_servers' => Server::where('status', 'failed')->count(),
            'total_nodes' => Node::count(),
            'online_nodes' => Node::where('status', 'online')->count(),
            'offline_nodes' => Node::where('status', 'offline')->count(),
            'active_subs' => Subscription::where('status', 'active')->count(),
            'total_plans' => Plan::count(),
        ];
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getRecentServers(): array
    {
        return Server::with(['user', 'node'])
            ->latest()
            ->take(8)
            ->get()
            ->toArray();
    }

    /**
     * Servers per node (for bar chart).
     *
     * @return array<string, mixed>
     */
    public function getServersPerNode(): array
    {
        $data = Node::withCount('servers')->get();

        return [
            'labels' => $data->pluck('label')->toArray(),
            'values' => $data->pluck('servers_count')->toArray(),
        ];
    }

    /**
     * Server status breakdown for doughnut chart.
     *
     * @return array<string, mixed>
     */
    public function getServerStatusBreakdown(): array
    {
        return [
            'active' => Server::where('status', 'active')->count(),
            'pending' => Server::where('status', 'pending')->count(),
            'failed' => Server::where('status', 'failed')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'stats' => $this->getStats(),
            'recentServers' => $this->getRecentServers(),
            'serversPerNode' => $this->getServersPerNode(),
            'statusBreakdown' => $this->getServerStatusBreakdown(),
            'nodes' => Node::orderBy('status')->latest('last_checked_at')->get(),
        ]);
    }
}

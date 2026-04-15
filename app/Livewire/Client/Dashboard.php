<?php

namespace App\Livewire\Client;

use App\Models\Node;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $servers = $user->servers()->with(['node', 'subscription'])->latest()->get();
        $subs = $user->subscriptions()->get(); // Fetch all to show history accurately
        $activeSubs = $subs->filter(fn ($sub) => $sub->isActive());

        $totalActive = $servers->where('status', 'active')->count();
        $totalUsed = $servers->count(); // All undeleted servers consume quota
        $totalLimit = $activeSubs->sum('max_server');

        return view('livewire.client.dashboard', [
            'user' => $user,
            'servers' => $servers,
            'subs' => $subs,
            'totalActive' => $totalActive,
            'totalUsed' => $totalUsed,
            'totalLimit' => $totalLimit,
            'nodes' => Node::where('status', 'online')->count(),
        ]);
    }
}

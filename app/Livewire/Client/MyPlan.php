<?php

namespace App\Livewire\Client;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyPlan extends Component
{
    public function render()
    {
        $user = Auth::user();
        $subs = $user->subscriptions()->withCount('servers')->latest()->get();

        return view('livewire.client.my-plan', [
            'subs' => $subs,
        ]);
    }
}

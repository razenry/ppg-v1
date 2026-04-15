<?php

namespace App\Livewire\Client;

use App\Models\Server;
use App\Models\Setting;
use App\Services\ServerService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ServerDetail extends Component
{
    public int $serverId;

    // Delete Modal State
    public $showDeleteModal = false;

    public $deleteServerIdentifier = '';

    public $deleteVerificationInput = '';

    public function mount($id)
    {
        // Only store the ID — fetch fresh on every render
        $server = Auth::user()->servers()->findOrFail($id);
        $this->serverId = $server->id;
    }

    /**
     * Always fetches a fresh copy from the database.
     */
    #[Computed]
    public function server(): Server
    {
        return Auth::user()->servers()->with(['node', 'subscription'])->findOrFail($this->serverId);
    }

    public function redeploy(ServerService $serverService)
    {
        $serverService->redeploy($this->server);
        unset($this->server); // clear computed cache
        Flux::toast(text: 'Redeployment queued for '.$this->server->identifier.'.', variant: 'success');
    }

    public function confirmDelete(): void
    {
        $this->deleteServerIdentifier = $this->server->identifier;
        $this->deleteVerificationInput = '';
        $this->showDeleteModal = true;
    }

    public function executeDelete(ServerService $serverService)
    {
        if ($this->deleteVerificationInput !== $this->deleteServerIdentifier) {
            $this->addError('deleteVerificationInput', 'Identifier does not match.');

            return;
        }

        $serverService->delete($this->server);

        return redirect()->route('servers.index');
    }

    public function getDomain(): string
    {
        // Use the domain stored from Golang API response (authoritative source)
        if ($this->server->domain) {
            return $this->server->domain;
        }

        // Fallback: compute from proxy_id or identifier + domain setting
        $cf = Setting::get('cloudflare', []);
        $domain = $cf['domain'] ?? 'raznar.net';
        $gateId = $this->server->proxy_id ?? "kafka_{$this->server->identifier}";

        return "{$gateId}.{$domain}";
    }

    public function render()
    {
        return view('livewire.client.server-detail', [
            'server' => $this->server,
            'domain' => $this->getDomain(),
        ]);
    }
}

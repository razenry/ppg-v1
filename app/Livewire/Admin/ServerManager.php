<?php

namespace App\Livewire\Admin;

use App\Models\Server;
use App\Services\ServerService;
use Flux\Flux;
use Livewire\Component;

class ServerManager extends Component
{
    public $servers;

    public $search = '';

    // Delete Modal State
    public $showDeleteModal = false;

    public $deleteServerId = null;

    public $deleteServerIdentifier = '';

    public $deleteVerificationInput = '';

    public function mount()
    {
        $this->loadServers();
    }

    public function loadServers()
    {
        $this->servers = Server::with(['user', 'node', 'subscription'])
            ->when($this->search, function ($query) {
                $query->where('label', 'like', "%{$this->search}%")
                    ->orWhere('identifier', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($q) {
                        $q->where('email', 'like', "%{$this->search}%");
                    });
            })
            ->get();
    }

    public function updatedSearch()
    {
        $this->loadServers();
    }

    public function forceDeploy($id, ServerService $serverService)
    {
        $server = Server::find($id);
        $serverService->redeploy($server);
        $this->loadServers();
        Flux::toast(text: 'Server redeployment forced globally.', variant: 'success');
    }

    public function confirmDelete($id)
    {
        $server = Server::find($id);
        $this->deleteServerId = $server->id;
        $this->deleteServerIdentifier = $server->identifier;
        $this->deleteVerificationInput = '';
        $this->showDeleteModal = true;
    }

    public function executeDelete(ServerService $serverService)
    {
        if ($this->deleteVerificationInput !== $this->deleteServerIdentifier) {
            $this->addError('deleteVerificationInput', 'Identifier does not match.');

            return;
        }

        $server = Server::find($this->deleteServerId);
        $serverService->delete($server);

        $this->showDeleteModal = false;
        $this->loadServers();
        Flux::toast(text: 'Server terminated globally.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.admin.server-manager');
    }
}

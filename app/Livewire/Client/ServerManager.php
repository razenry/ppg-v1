<?php

namespace App\Livewire\Client;

use App\Models\Node;
use App\Services\ServerService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ServerManager extends Component
{
    public $servers;

    public $nodes;

    public $subscriptions;

    // Create fields
    public $label;

    public $identifier;

    /** FiveM server IP — where traffic is proxied to */
    public $src_ip;

    /** Backend Game Port (FiveM) — e.g. 30120 */
    public $src_port = 30120;

    public $node_id;

    public $subscription_id;

    public $showCreateModal = false;

    // Delete Modal State
    public $showDeleteModal = false;

    public $deleteServerId = null;

    public $deleteServerIdentifier = '';

    public $deleteVerificationInput = '';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->servers = $user->servers()->with(['node', 'subscription'])->get();
        $this->nodes = Node::all();
        $this->subscriptions = $user->subscriptions()
            ->get()
            ->filter(fn ($sub) => $sub->isActive());
    }

    public function resetFields()
    {
        $this->label = '';
        $this->identifier = '';
        $this->src_ip = '';
        $this->src_port = 30120;
        $this->node_id = null;
        $this->subscription_id = $this->subscriptions->first()?->id;
    }

    public function updatedIdentifier()
    {
        $this->validateOnly('identifier', [
            'identifier' => 'required|alpha_dash|min:3|max:32|unique:servers,identifier',
        ]);
    }

    public function updatedLabel(): void
    {
        $this->validateOnly('label', [
            'label' => [
                'required',
                'min:3',
                'max:64',
                Rule::unique('servers', 'label')
                    ->where('user_id', Auth::id()),
            ],
        ]);
    }

    /**
     * When a node is selected, we can auto-fill src_ip if the node matches
     * but we no longer suggest a dest_port (proxy port) because it's random.
     */
    public function updatedNodeId(): void
    {
        if (! $this->node_id) {
            return;
        }

        $node = Node::find($this->node_id);

        if (! $node) {
            return;
        }

        $parsed = parse_url($node->api_url);

        if (! $this->src_ip && ! empty($parsed['host'])) {
            $this->src_ip = $parsed['host'];
        }
    }

    public function openCreateModal(): void
    {
        $this->resetFields();
        $this->showCreateModal = true;
    }

    public function createServer(ServerService $serverService)
    {
        $this->validate([
            'label' => [
                'required',
                'min:3',
                'max:64',
                Rule::unique('servers', 'label')->where('user_id', Auth::id()),
            ],
            'identifier' => 'required|alpha_dash|min:3|max:32|unique:servers,identifier',
            'src_ip' => 'required|ip',
            'src_port' => 'required|integer|min:1|max:65535',
            'node_id' => 'required|exists:nodes,id',
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);

        try {
            $serverService->create(Auth::user(), [
                'label' => $this->label,
                'identifier' => $this->identifier,
                'src_ip' => $this->src_ip,
                'src_port' => (int) $this->src_port,
                'node_id' => $this->node_id,
                'subscription_id' => $this->subscription_id,
            ]);

            $this->showCreateModal = false;
            $this->loadData();
            Flux::toast(text: 'Server provisioning started successfully.', variant: 'success');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Validation failed.';
            $this->addError('subscription_id', $message);
            Flux::toast(text: $message, variant: 'danger', heading: 'Limit Reached');
        } catch (\Exception $e) {
            $this->addError('subscription_id', $e->getMessage());
            Flux::toast(text: 'An unexpected error occurred.', variant: 'danger', heading: 'Provisioning Failed');
        }
    }

    public function redeploy($id, ServerService $serverService): void
    {
        $server = Auth::user()->servers()->find($id);

        if (! $server) {
            Flux::toast(text: 'Server not found.', variant: 'warning');

            return;
        }

        $serverService->redeploy($server);
        $this->loadData();
        Flux::toast(text: 'Redeployment triggered for '.$server->identifier.'.', variant: 'success');
    }

    public function confirmDelete($id): void
    {
        $server = Auth::user()->servers()->find($id);

        if (! $server) {
            Flux::toast(text: 'Server not found or already removed.', variant: 'warning');

            return;
        }

        $this->deleteServerId = $server->id;
        $this->deleteServerIdentifier = $server->identifier;
        $this->deleteVerificationInput = '';
        $this->showDeleteModal = true;
    }

    public function executeDelete(ServerService $serverService): void
    {
        if ($this->deleteVerificationInput !== $this->deleteServerIdentifier) {
            $this->addError('deleteVerificationInput', 'Identifier does not match.');

            return;
        }

        $server = Auth::user()->servers()->find($this->deleteServerId);

        if (! $server) {
            // Already deleted — just close and refresh
            $this->showDeleteModal = false;
            $this->loadData();
            Flux::toast(text: 'Server has already been removed.', variant: 'warning');

            return;
        }

        $serverService->delete($server);

        $this->showDeleteModal = false;
        $this->deleteServerId = null;
        $this->deleteServerIdentifier = '';
        $this->deleteVerificationInput = '';
        $this->loadData();
        Flux::toast(text: 'Server terminated permanently.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.client.server-manager');
    }
}

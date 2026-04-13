<?php

namespace App\Livewire\Admin;

use App\Models\Node;
use App\Services\NodeService;
use Flux\Flux;
use Livewire\Component;

class NodeManager extends Component
{
    public $nodes;

    public $name;

    public $label;

    public $description;

    public $api_url;

    public $api_token;

    public $editingNodeId;

    public $showModal = false;

    // Delete Modal State
    public $showDeleteModal = false;

    public $deleteNodeId = null;

    public $deleteNodeExpected = '';

    public $deleteVerificationInput = '';

    public function mount()
    {
        $this->loadNodes();
    }

    public function loadNodes()
    {
        $this->nodes = Node::all();
    }

    public function resetFields()
    {
        $this->name = '';
        $this->label = '';
        $this->description = '';
        $this->api_url = '';
        $this->api_token = '';
        $this->editingNodeId = null;
    }

    public function openModal($id = null)
    {
        $this->resetFields();
        if ($id) {
            $this->editingNodeId = $id;
            $node = Node::find($id);
            $this->name = $node->name;
            $this->label = $node->label;
            $this->description = $node->description;
            $this->api_url = $node->api_url;
            $this->api_token = $node->api_token;
        }
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|unique:nodes,name,'.$this->editingNodeId,
            'label' => 'required',
            'api_url' => 'required|url',
            'api_token' => 'required',
        ]);

        Node::updateOrCreate(
            ['id' => $this->editingNodeId],
            [
                'name' => $this->name,
                'label' => $this->label,
                'description' => $this->description,
                'api_url' => $this->api_url,
                'api_token' => $this->api_token,
            ]
        );

        $this->showModal = false;
        $this->loadNodes();
        Flux::toast(text: 'Node saved successfully.', variant: 'success');
    }

    public function confirmDelete($id)
    {
        $node = Node::find($id);

        if (! $node) {
            Flux::toast(text: 'Node not found.', variant: 'danger');

            return;
        }

        $this->deleteNodeId = $node->id;
        $this->deleteNodeExpected = $node->name;
        $this->deleteVerificationInput = '';
        $this->showDeleteModal = true;
    }

    public function executeDelete()
    {
        if ($this->deleteVerificationInput !== $this->deleteNodeExpected) {
            $this->addError('deleteVerificationInput', 'Node name does not match.');

            return;
        }

        $node = Node::find($this->deleteNodeId);

        if (! $node) {
            $this->showDeleteModal = false;
            Flux::toast(text: 'Node already deleted.', variant: 'warning');
            $this->loadNodes();

            return;
        }

        $node->delete();
        $this->showDeleteModal = false;
        $this->loadNodes();
        Flux::toast(text: 'Node deleted effectively.', variant: 'success');
    }

    public function testConnection($id, NodeService $nodeService)
    {
        $node = Node::find($id);
        $result = $nodeService->testConnection($node);

        // Persist real-time status to DB
        $node->update([
            'status' => $result['online'] ? 'online' : 'offline',
            'latency_ms' => $result['latency_ms'],
            'last_checked_at' => now(),
        ]);

        $this->loadNodes();

        if ($result['online']) {
            $latency = $result['latency_ms'];
            Flux::toast(text: "Connected — {$latency}ms latency.", variant: 'success', heading: 'Node Online');
        } else {
            Flux::toast(text: 'Unable to reach the node. Check API URL and token.', variant: 'danger', heading: 'Node Offline');
        }
    }

    public function render()
    {
        return view('livewire.admin.node-manager');
    }
}

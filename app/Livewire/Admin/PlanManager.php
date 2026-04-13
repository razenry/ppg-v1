<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use Flux\Flux;
use Livewire\Component;

class PlanManager extends Component
{
    public $plans;

    public $name;

    public $max_server;

    public $editingPlanId;

    public $showModal = false;

    // Delete Modal State
    public $showDeleteModal = false;

    public $deletePlanId = null;

    public $deletePlanExpected = '';

    public $deleteVerificationInput = '';

    public function mount()
    {
        $this->loadPlans();
    }

    public function loadPlans()
    {
        $this->plans = Plan::all();
    }

    public function resetFields()
    {
        $this->name = '';
        $this->max_server = 1;
        $this->editingPlanId = null;
    }

    public function openModal($id = null)
    {
        $this->resetFields();
        if ($id) {
            $this->editingPlanId = $id;
            $plan = Plan::find($id);
            $this->name = $plan->name;
            $this->max_server = $plan->max_server;
        }
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|unique:plans,name,'.$this->editingPlanId,
            'max_server' => 'required|integer|min:1',
        ]);

        Plan::updateOrCreate(
            ['id' => $this->editingPlanId],
            [
                'name' => $this->name,
                'max_server' => $this->max_server,
            ]
        );

        $this->showModal = false;
        $this->loadPlans();
        Flux::toast(text: 'Plan saved successfully.', variant: 'success');
    }

    public function confirmDelete($id)
    {
        $plan = Plan::find($id);
        $this->deletePlanId = $plan->id;
        $this->deletePlanExpected = $plan->name;
        $this->deleteVerificationInput = '';
        $this->showDeleteModal = true;
    }

    public function executeDelete()
    {
        if ($this->deleteVerificationInput !== $this->deletePlanExpected) {
            $this->addError('deleteVerificationInput', 'Plan name does not match.');

            return;
        }

        Plan::find($this->deletePlanId)->delete();
        $this->showDeleteModal = false;
        $this->loadPlans();
        Flux::toast(text: 'Plan deleted effectively.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.admin.plan-manager');
    }
}

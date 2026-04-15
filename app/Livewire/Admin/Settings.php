<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Settings extends Component
{
    public $port_range_start;

    public $port_range_end;

    public $subscription_duration_value;

    public $subscription_duration_unit;

    public function mount()
    {
        $portRange = Setting::get('port_range', ['min' => 20000, 'max' => 60000]);
        $this->port_range_start = $portRange['min'];
        $this->port_range_end = $portRange['max'];

        $subDuration = Setting::get('subscription_duration_default', ['value' => 30, 'unit' => 'days']);
        $this->subscription_duration_value = $subDuration['value'];
        $this->subscription_duration_unit = $subDuration['unit'];
    }

    public function save()
    {
        $this->validate([
            'port_range_start' => 'required|integer|min:1|max:65534',
            'port_range_end' => 'required|integer|gt:port_range_start|max:65535',
            'subscription_duration_value' => 'required|integer|min:1',
            'subscription_duration_unit' => 'required|in:days,months,years',
        ]);

        Setting::set('port_range', [
            'min' => (int) $this->port_range_start,
            'max' => (int) $this->port_range_end,
        ]);

        Setting::set('subscription_duration_default', [
            'value' => (int) $this->subscription_duration_value,
            'unit' => $this->subscription_duration_unit,
        ]);

        Flux::toast(text: 'System Settings have been updated successfully.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}

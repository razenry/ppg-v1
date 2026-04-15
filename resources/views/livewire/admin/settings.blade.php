<div class="space-y-6 max-w-4xl">
    <header class="mb-6">
        <flux:heading size="xl" level="1">System Settings</flux:heading>
        <flux:subheading>Global configurations for random port generation and automated subscription lifecycle.</flux:subheading>
    </header>

    <form wire:submit="save" class="space-y-8">
        {{-- Proxy Port Randomization Rules --}}
        <flux:card>
            <div class="mb-4">
                <flux:heading size="lg">Port Array Limits</flux:heading>
                <flux:subheading>Configure the safe TCP bounds that the proxy manager will assign to new server deployments on nodes.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Starting Port Range</flux:label>
                    <flux:input type="number" wire:model="port_range_start" min="1" max="65534" placeholder="20000" />
                    <flux:error name="port_range_start" />
                </flux:field>

                <flux:field>
                    <flux:label>Ending Port Range</flux:label>
                    <flux:input type="number" wire:model="port_range_end" min="2" max="65535" placeholder="60000" />
                    <flux:error name="port_range_end" />
                </flux:field>
            </div>
            <p class="text-xs text-zinc-500 mt-3 flex items-center gap-1.5"><flux:icon icon="information-circle" size="xs"/> <span>If a generated port strictly collides with another user's proxy server on the exact same node, it automatically retries within this array.</span></p>
        </flux:card>

        {{-- Subscription Automation Rules --}}
        <flux:card>
            <div class="mb-4">
                <flux:heading size="lg">Automated Billing Rules</flux:heading>
                <flux:subheading>Set the timeframe of the default global cycle. When a plan is assigned, <code>expired_at</code> will be evaluated precisely against these units.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Default Duration</flux:label>
                    <flux:input type="number" wire:model="subscription_duration_value" min="1" placeholder="30" />
                    <flux:error name="subscription_duration_value" />
                </flux:field>

                <flux:field>
                    <flux:label>Duration Metric</flux:label>
                    <flux:select wire:model="subscription_duration_unit">
                        <option value="days">Days</option>
                        <option value="months">Months</option>
                        <option value="years">Years</option>
                    </flux:select>
                    <flux:error name="subscription_duration_unit" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary" icon="check" class="px-8 bg-blue-600 hover:bg-blue-500 border-0">Save Variables</flux:button>
            <span wire:loading wire:target="save" class="text-sm text-zinc-500 flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Saving securely to edge...
            </span>
        </div>
    </form>
</div>

<div class="space-y-6">
    <header class="flex justify-between items-end gap-4">
        <div>
            <flux:heading size="xl" level="1">Subscription Plans</flux:heading>
            <flux:subheading>Manage service tiers and resource limits for customers.</flux:subheading>
        </div>
        <flux:button wire:click="openModal()" variant="primary" icon="plus">Create Plan</flux:button>
    </header>

    <flux:separator variant="subtle" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($plans as $plan)
            <flux:card class="flex flex-col gap-4">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                        <flux:text variant="subtle" size="sm">Subscription Tier</flux:text>
                    </div>
                    <flux:icon icon="credit-card" class="text-zinc-200" />
                </div>

                <div class="flex items-baseline gap-1 mt-2">
                    <span class="text-3xl font-bold tracking-tight">{{ $plan->max_server }}</span>
                    <span class="text-zinc-500 text-sm">Servers Max</span>
                </div>

                <div class="mt-4 flex gap-2">
                    <flux:button wire:click="openModal({{ $plan->id }})" variant="ghost" size="sm" icon="pencil" class="flex-1">Edit</flux:button>
                    <flux:button wire:click="confirmDelete({{ $plan->id }})" variant="ghost" color="red" size="sm" icon="trash" class="flex-1">Delete</flux:button>
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingPlanId ? 'Edit Plan' : 'Create Plan' }}</flux:heading>
                <flux:subheading>Define the resources for this tier.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Plan Name</flux:label>
                <flux:input wire:model="name" placeholder="Pro, Enterprise, etc." />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Max Server Allowed</flux:label>
                <flux:input wire:model="max_server" type="number" />
                <flux:error name="max_server" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save Plan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Secure Delete Modal --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-[28rem]">
        <form wire:submit="executeDelete" class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-500">Delete Subscription Plan?</flux:heading>
                <flux:subheading class="mt-2">
                    <p>You're about to delete this plan. New users won't be able to subscribe to it.</p>
                    <p class="mt-2">Please type <strong class="text-zinc-900 dark:text-white font-mono bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">{{ $deletePlanExpected }}</strong> to confirm.</p>
                </flux:subheading>
            </div>

            <flux:field>
                <flux:input wire:model.live="deleteVerificationInput" placeholder="{{ $deletePlanExpected }}" autocomplete="off" />
                <flux:error name="deleteVerificationInput" />
            </flux:field>

            <div class="flex gap-2" x-data="{ countdown: 5 }" x-init="
                $watch('$wire.showDeleteModal', value => {
                    if (value) { countdown = 5; let i = setInterval(() => { if(countdown > 0) countdown--; else clearInterval(i); }, 1000); }
                })
            ">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="danger" x-bind:disabled="countdown > 0 || $wire.deleteVerificationInput !== $wire.deletePlanExpected">
                    <span x-show="countdown > 0" x-text="'Wait ' + countdown + 's'"></span>
                    <span x-show="countdown === 0">Confirm Delete</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<div class="space-y-6">
    <header class="flex justify-between items-end gap-4">
        <div>
            <flux:heading size="xl" level="1">Infrastructure Nodes</flux:heading>
            <flux:subheading>Manage the distributed proxy nodes that handle your traffic.</flux:subheading>
        </div>
        <flux:button wire:click="openModal()" variant="primary" icon="plus" class="bg-blue-600 hover:bg-blue-500 border-0">Add Node</flux:button>
    </header>

    <flux:separator variant="subtle" />

    <flux:card p="0" class="overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Location / Name</flux:table.column>
                <flux:table.column class="hidden md:table-cell">API Endpoint</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($nodes as $node)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:icon icon="cpu-chip" size="sm" class="text-zinc-400" />
                                <div>
                                    <div class="font-medium">{{ $node->label }}</div>
                                    <div class="text-xs text-zinc-500 font-mono">{{ $node->name }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell font-mono text-xs text-zinc-500">
                            {{ $node->api_url }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($node->status === 'online')
                                <div class="flex flex-col gap-0.5">
                                    <flux:badge color="green" variant="subtle" size="sm" icon="check-circle">Online</flux:badge>
                                    @if($node->latency_ms !== null)
                                        <span class="text-[10px] text-zinc-400 font-mono">{{ $node->latency_ms }}ms</span>
                                    @endif
                                </div>
                            @elseif($node->status === 'offline')
                                <flux:badge color="red" variant="subtle" size="sm" icon="x-circle">Offline</flux:badge>
                            @else
                                <flux:badge variant="subtle" size="sm" icon="question-mark-circle">Unknown</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown align="end" class="pointer-events-auto">
                                <flux:button variant="ghost" icon="ellipsis-horizontal" size="sm" />
                                <flux:menu>
                                    <flux:menu.item wire:click="testConnection({{ $node->id }})" icon="signal">Test Ping</flux:menu.item>
                                    <flux:menu.item wire:click="openModal({{ $node->id }})" icon="pencil">Edit</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="confirmDelete({{ $node->id }})" variant="danger" icon="trash">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingNodeId ? 'Edit Node' : 'Register Node' }}</flux:heading>
                <flux:subheading>Update connection details for this infrastructure point.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Identifier (Slug)</flux:label>
                <flux:input wire:model="name" placeholder="sg-01" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Display Label</flux:label>
                <flux:input wire:model="label" placeholder="Singapore cluster" />
                <flux:error name="label" />
            </flux:field>

            <flux:field>
                <flux:label>API URL</flux:label>
                <flux:input wire:model="api_url" placeholder="https://api.domain.com" />
                <flux:error name="api_url" />
            </flux:field>

            <flux:field>
                <flux:label>API Token</flux:label>
                <flux:input wire:model="api_token" type="password" />
                <flux:error name="api_token" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-500 border-0">Save Node</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Secure Delete Modal --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-[28rem]">
        <form wire:submit="executeDelete" class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-500">Delete Infrastructure Node?</flux:heading>
                <flux:subheading class="mt-2">
                    <p>You're about to remove this node from the panel. Its API will no longer receive provisioning commands.</p>
                    <p class="mt-2">Please type <strong class="text-zinc-900 dark:text-white font-mono bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">{{ $deleteNodeExpected }}</strong> to confirm.</p>
                </flux:subheading>
            </div>

            <flux:field>
                <flux:input wire:model.live="deleteVerificationInput" placeholder="{{ $deleteNodeExpected }}" autocomplete="off" />
                <flux:error name="deleteVerificationInput" />
            </flux:field>

            <div class="flex gap-2" x-data="{ countdown: 5 }" x-init="
                $watch('$wire.showDeleteModal', value => {
                    if (value) { countdown = 5; let i = setInterval(() => { if(countdown > 0) countdown--; else clearInterval(i); }, 1000); }
                })
            ">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="danger" x-bind:disabled="countdown > 0 || $wire.deleteVerificationInput !== $wire.deleteNodeExpected">
                    <span x-show="countdown > 0" x-text="'Wait ' + countdown + 's'"></span>
                    <span x-show="countdown === 0">Confirm Delete</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<div class="space-y-6">
    <header class="flex justify-between items-end gap-4">
        <div>
            <flux:heading size="xl" level="1">Global Proxy Fleet</flux:heading>
            <flux:subheading>Manage all proxy instances provisioned across the platform.</flux:subheading>
        </div>
        <div class="w-full md:w-64">
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search unique identifiers..." />
        </div>
    </header>

    <flux:separator variant="subtle" />

    <flux:card p="0" class="overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Owner / Label</flux:table.column>
                <flux:table.column class="hidden md:table-cell">Edge Info</flux:table.column>
                <flux:table.column>Identifier</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($servers as $server)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="xs" :name="$server->user->name" class="rounded" />
                                <div>
                                    <div class="font-medium text-sm">{{ $server->label }}</div>
                                    <div class="text-[10px] text-zinc-500 uppercase">{{ $server->user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            <div class="flex items-center gap-2">
                                <flux:icon icon="map-pin" size="xs" class="text-zinc-400" />
                                <span class="text-xs">{{ $server->node->label }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-xs font-mono text-zinc-600 bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-400 px-2 py-0.5 rounded-md inline-block">
                                {{ $server->domain ?? ('kafka_'.$server->identifier.'.'.(\App\Models\Setting::get('cloudflare', [])['domain'] ?? 'raznar.net')) }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($server->status === 'active')
                                <flux:badge color="blue" variant="pill" size="sm">Active</flux:badge>
                            @elseif($server->status === 'pending')
                                <flux:badge color="yellow" variant="pill" size="sm">Provisioning</flux:badge>
                            @else
                                <flux:badge color="red" variant="pill" size="sm">Failed</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" icon="ellipsis-horizontal" size="sm" />
                                <flux:menu>
                                    <flux:menu.item wire:click="forceDeploy({{ $server->id }})" icon="arrow-path">Redeploy</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="confirmDelete({{ $server->id }})" variant="danger" icon="trash">Terminate</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Secure Delete Modal --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-[28rem]">
        <form wire:submit="executeDelete" class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-500">Terminate Global Instance?</flux:heading>
                <flux:subheading class="mt-2">
                    <p>You're about to forcefully delete this client's server globally. This action cannot be undone.</p>
                    <p class="mt-2">Please type <strong class="text-zinc-900 dark:text-white font-mono bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">{{ $deleteServerIdentifier }}</strong> to confirm.</p>
                </flux:subheading>
            </div>

            <flux:field>
                <flux:input wire:model.live="deleteVerificationInput" placeholder="{{ $deleteServerIdentifier }}" autocomplete="off" />
                <flux:error name="deleteVerificationInput" />
            </flux:field>

            <div class="flex gap-2" x-data="{ countdown: 5 }" x-init="
                $watch('$wire.showDeleteModal', value => {
                    if (value) { countdown = 5; let i = setInterval(() => { if(countdown > 0) countdown--; else clearInterval(i); }, 1000); }
                })
            ">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="danger" x-bind:disabled="countdown > 0 || $wire.deleteVerificationInput !== $wire.deleteServerIdentifier">
                    <span x-show="countdown > 0" x-text="'Wait ' + countdown + 's'"></span>
                    <span x-show="countdown === 0">Force Terminate</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

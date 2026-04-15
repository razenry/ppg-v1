<div class="space-y-6" @if($server->status === 'pending') wire:poll.5000ms @endif>
    <header class="flex items-center gap-4">
        <flux:button variant="ghost" icon="chevron-left" href="{{ route('servers.index') }}" wire:navigate />
        <div>
            <flux:heading size="xl" level="1">{{ $server->label }}</flux:heading>
            <flux:subheading class="font-mono text-xs uppercase">{{ $server->identifier }}</flux:subheading>
        </div>
        <flux:spacer />
        @if($server->status === 'active')
            <flux:badge color="green" variant="pill" icon="check-circle">Operational</flux:badge>
        @elseif($server->status === 'pending')
            <flux:badge color="yellow" variant="pill" icon="clock" class="animate-pulse">Provisioning…</flux:badge>
        @else
            <flux:badge color="red" variant="pill" icon="x-circle">Failed</flux:badge>
        @endif
    </header>

    <flux:separator variant="subtle" />

    @if($server->status === 'failed')
        <flux:callout variant="danger" heading="Provisioning Failed">
            The gate node could not be configured. This slot still counts against your quota.
            Use <strong>Redeploy</strong> to retry, or <strong>Terminate</strong> to free the slot.
        </flux:callout>
    @elseif($server->status === 'pending')
        <flux:callout variant="warning" heading="Provisioning in Progress">
            Your server is being configured on the node. This page will reflect the latest status automatically.
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">

            {{-- Endpoint --}}
            <flux:card class="space-y-4">
                <div>
                    <flux:heading level="2" size="lg">Gate Endpoint</flux:heading>
                    <flux:subheading>Public domain routed through the proxy node.</flux:subheading>
                </div>
                <div class="flex items-center gap-4 p-5 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-800">
                    @if($server->status === 'active')
                        <div class="text-xl font-mono font-bold tracking-tighter truncate flex-1 leading-none text-blue-600 dark:text-blue-400">
                            {{ $domain }}
                        </div>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="clipboard"
                            x-on:click="navigator.clipboard.writeText('{{ $domain }}'); $flux.toast({ text: 'Copied to clipboard!', variant: 'success' })"
                        />
                    @else
                        <div class="text-sm text-zinc-400 italic">Domain will appear after provisioning completes.</div>
                    @endif
                </div>
                @if($server->proxy_id)
                    <flux:text variant="subtle" size="xs" class="font-mono">Gate ID: {{ $server->proxy_id }}</flux:text>
                @endif
            </flux:card>

            {{-- Target & Node --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:card class="space-y-4">
                    <flux:heading level="3">Target Settings</flux:heading>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm py-2 border-b border-zinc-100 dark:border-zinc-800">
                            <flux:text variant="subtle">Backend IP</flux:text>
                            <flux:text class="font-mono font-medium">{{ $server->src_ip }}</flux:text>
                        </div>
                        <div class="flex justify-between text-sm py-2 border-b border-zinc-100 dark:border-zinc-800">
                            <flux:text variant="subtle">Target Port</flux:text>
                            <flux:text class="font-mono font-medium">{{ $server->src_port }}</flux:text>
                        </div>
                        <div class="flex justify-between text-sm py-2">
                            <flux:text variant="subtle">Created</flux:text>
                            <flux:text class="text-xs">{{ $server->created_at->diffForHumans() }}</flux:text>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="space-y-4">
                    <flux:heading level="3">Node Infrastructure</flux:heading>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm py-2 border-b border-zinc-100 dark:border-zinc-800">
                            <flux:text variant="subtle">Region</flux:text>
                            <flux:text class="font-bold">{{ $server->node->label }}</flux:text>
                        </div>
                        <div class="flex justify-between text-sm py-2 border-b border-zinc-100 dark:border-zinc-800">
                            <flux:text variant="subtle">Node Slug</flux:text>
                            <flux:text class="font-mono text-[11px]">{{ $server->node->name }}</flux:text>
                        </div>
                        <div class="flex justify-between text-sm py-2">
                            <flux:text variant="subtle">Node Status</flux:text>
                            @if($server->node->status === 'online')
                                <flux:badge color="green" size="sm" variant="subtle">Online</flux:badge>
                            @elseif($server->node->status === 'offline')
                                <flux:badge color="red" size="sm" variant="subtle">Offline</flux:badge>
                            @else
                                <flux:badge size="sm" variant="subtle">Unknown</flux:badge>
                            @endif
                        </div>
                    </div>
                </flux:card>
            </div>

            {{-- Operational Log --}}
            <flux:card p="0" class="overflow-hidden">
                <div class="p-5 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center">
                    <flux:heading level="3">Event Log</flux:heading>
                    <flux:badge variant="subtle" size="sm" class="font-mono text-[10px]">{{ $server->updated_at->format('Y-m-d H:i:s') }}</flux:badge>
                </div>
                <div class="bg-zinc-950 p-6 font-mono text-xs leading-relaxed h-44 overflow-y-auto space-y-1">
                    <div class="text-zinc-500">[{{ $server->created_at->format('H:i:s') }}] Server record created for {{ $server->identifier }}</div>
                    <div class="text-zinc-500">[{{ $server->created_at->format('H:i:s') }}] Dispatching to node: {{ $server->node->name }}</div>
                    @if($server->proxy_id)
                        <div class="text-emerald-400">[{{ $server->updated_at->format('H:i:s') }}] Gate registered — ID: {{ $server->proxy_id }}</div>
                        <div class="text-emerald-400">[{{ $server->updated_at->format('H:i:s') }}] Proxy listening on {{ $domain }}</div>
                    @elseif($server->status === 'failed')
                        <div class="text-red-400">[{{ $server->updated_at->format('H:i:s') }}] PROVISIONING FAILED — Check node connectivity</div>
                        <div class="text-red-500">[{{ $server->updated_at->format('H:i:s') }}] Retry via redeploy or terminate to free slot</div>
                    @elseif($server->status === 'pending')
                        <div class="text-amber-400 animate-pulse">[{{ now()->format('H:i:s') }}] Waiting for node confirmation…</div>
                    @endif
                    <div class="text-zinc-700 mt-2">_</div>
                </div>
            </flux:card>
        </div>

        {{-- Sidebar Actions --}}
        <div class="space-y-6">
            <flux:card class="space-y-4">
                <flux:heading level="3">Instance Actions</flux:heading>
                <div class="grid gap-3 pt-1">
                    <flux:button wire:click="redeploy" icon="arrow-path" class="w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="redeploy">Redeploy Node</span>
                        <span wire:loading wire:target="redeploy">Triggering…</span>
                    </flux:button>
                    <div class="pt-4 mt-1 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:button wire:click="confirmDelete" variant="ghost" color="red" icon="trash" class="w-full">
                            Terminate Instance
                        </flux:button>
                    </div>
                </div>
            </flux:card>

            {{-- Subscription Info --}}
            @if($server->subscription)
                <flux:card class="space-y-3">
                    <flux:heading level="3">Subscription</flux:heading>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <flux:text variant="subtle">Plan</flux:text>
                            <flux:text class="font-semibold">{{ $server->subscription->plan_name }}</flux:text>
                        </div>
                        <div class="flex justify-between text-sm">
                            <flux:text variant="subtle">Max Servers</flux:text>
                            <flux:text class="font-mono">{{ $server->subscription->max_server }}</flux:text>
                        </div>
                        <div class="flex justify-between text-sm">
                            <flux:text variant="subtle">Sub ID</flux:text>
                            <flux:text class="font-mono text-[10px] text-zinc-400">{{ $server->subscription->external_id }}</flux:text>
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Secure Delete Modal --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-[28rem]">
        <form wire:submit="executeDelete" class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-500">Terminate Instance?</flux:heading>
                <flux:subheading class="mt-2">
                    <p>You're about to delete this server and its configurations. This action cannot be undone.</p>
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
                    <span x-show="countdown === 0">Terminate Permanently</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

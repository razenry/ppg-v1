<div class="space-y-8">
    <header class="flex justify-between items-end gap-4">
        <div>
            <flux:heading size="xl" level="1">My Servers</flux:heading>
            <flux:subheading>Manage your global proxy fleet and cloud endpoints.</flux:subheading>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus" class="bg-blue-600 hover:bg-blue-500 border-0" :disabled="$subscriptions->isEmpty()">New Instance</flux:button>
    </header>

    @if($subscriptions->isEmpty())
        <flux:callout variant="warning" heading="Subscription Required">
            You need an active subscription to start provisioning proxy servers.
        </flux:callout>
    @endif

    {{-- Auto-refresh every 5s while provisioning is in progress --}}
    @if($servers->contains('status', 'pending'))
        <div wire:poll.5000ms="loadData" class="flex items-center gap-3 px-4 py-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-800 dark:text-amber-300">
            <svg class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span><strong>Provisioning in progress</strong> — Setting up NGINX proxy and Cloudflare DNS. This may take up to 2 minutes. Page refreshes automatically.</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($servers as $server)
            <flux:card class="group flex flex-col justify-between p-5">
                <div class="space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <flux:heading size="lg" class="font-bold">{{ $server->label }}</flux:heading>
                            <flux:text color="blue" size="sm" class="font-mono">{{ $server->identifier }}</flux:text>
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($server->status === 'active')
                                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                                <span class="text-[10px] font-bold text-emerald-600 uppercase">Online</span>
                            @elseif($server->status === 'pending')
                                <flux:badge color="yellow" variant="subtle" size="sm" class="animate-pulse">Provisioning</flux:badge>
                            @else
                                <flux:badge color="red" variant="subtle" size="sm">Failed</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2 p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-lg border border-zinc-100 dark:border-zinc-800">
                        <div class="flex justify-between text-[10px] font-mono">
                            <span class="text-zinc-500 uppercase">Region</span>
                            <span class="font-bold text-zinc-700 dark:text-zinc-300">{{ $server->node->label }}</span>
                        </div>
                        <div class="flex justify-between text-[10px] font-mono">
                            <span class="text-zinc-500 uppercase">Public Endpoint</span>
                            <span class="truncate ml-4 text-blue-600 dark:text-blue-400 font-bold">{{ $server->domain ?? ('kafka_'.$server->identifier.'.'.( \App\Models\Setting::get('cloudflare', [])['domain'] ?? 'raznar.net')) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    <flux:button href="{{ route('servers.show', $server->id) }}" variant="ghost" size="sm" class="flex-1 font-semibold">Settings</flux:button>
                    <flux:dropdown align="end">
                        <flux:button variant="ghost" icon="ellipsis-horizontal" size="sm" />
                        <flux:menu>
                            <flux:menu.item wire:click="redeploy({{ $server->id }})" icon="arrow-path">Redeploy Cluster</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item wire:click="confirmDelete({{ $server->id }})" variant="danger" icon="trash">Terminate</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </flux:card>
        @endforeach

        @if($servers->isEmpty())
            <flux:card class="col-span-full py-20 flex flex-col items-center justify-center text-center border-dashed">
                <flux:icon icon="server-stack" size="xl" class="mb-4 text-zinc-300" />
                <flux:heading>No active instances</flux:heading>
                <flux:text variant="subtle" class="mt-2 text-sm">Deploy your first reverse proxy to our global edge network.</flux:text>
                <flux:button wire:click="openCreateModal" class="mt-6 bg-blue-600 hover:bg-blue-500 border-0" variant="primary">Provision Now</flux:button>
            </flux:card>
        @endif
    </div>

    {{-- Create Modal --}}
    <flux:modal wire:model="showCreateModal" class="md:w-[32rem]">
        <form wire:submit="createServer" class="space-y-6">
            <div>
                <flux:heading size="lg">Provision New Server</flux:heading>
                <flux:subheading>A reverse proxy gate will be created on the selected node.</flux:subheading>
            </div>

            <div class="space-y-5">
                {{-- Label --}}
                <flux:field>
                    <flux:label badge="Required">Server Name</flux:label>
                    <flux:input wire:model.live="label" placeholder="e.g. My FiveM RP Server" autocomplete="off" maxlength="64" />
                    <flux:description>A friendly name to identify this server. Must be unique across your servers.</flux:description>
                    <flux:error name="label" />
                </flux:field>

                {{-- Identifier --}}
                <flux:field>
                    <flux:label badge="Required">Public Identifier</flux:label>
                    <flux:input
                        wire:model.live="identifier"
                        placeholder="myrpserver"
                        autocomplete="off"
                        pattern="[a-z0-9\-_]+"
                        maxlength="32"
                    />
                    <flux:description>3–32 chars, lowercase letters/numbers/hyphens. Globally unique across all servers.</flux:description>
                    @if($identifier)
                        <div class="mt-1.5 flex items-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-950/40 rounded-lg border border-blue-100 dark:border-blue-900">
                            <flux:icon icon="globe-alt" size="xs" class="text-blue-500 shrink-0" />
                            <span class="font-mono text-xs text-blue-700 dark:text-blue-300 truncate">
                                kafka_{{ strtolower($identifier) }}.{{ \App\Models\Setting::get('cloudflare', [])['domain'] ?? 'raznar.net' }}
                            </span>
                        </div>
                    @endif
                    <flux:error name="identifier" />
                </flux:field>

                {{-- Backend IP and Port --}}
                <div class="space-y-4">
                    <div>
                        <flux:label badge="Required">Game Server Network</flux:label>
                        <p class="text-xs text-zinc-500 mt-1">Your actual FiveM server IP and game port. Traffic is proxied from our node to this address.</p>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <flux:field>
                                <flux:label>Server IP</flux:label>
                                <flux:input wire:model="src_ip" placeholder="103.42.116.182" />
                                <flux:error name="src_ip" />
                            </flux:field>
                        </div>
                        <div>
                            <flux:field>
                                <flux:label>Game Port</flux:label>
                                <flux:input wire:model="src_port" type="number" placeholder="30120" />
                                <flux:error name="src_port" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Node --}}
                <flux:field>
                    <flux:label badge="Required">Proxy Region</flux:label>
                    <flux:select wire:model.live="node_id">
                        <option value="">Select a region…</option>
                        @forelse($nodes as $node)
                            <option value="{{ $node->id }}" {{ $node->status === 'offline' ? 'disabled' : '' }}>
                                {{ $node->label }}
                                @if($node->status === 'online') ✓ Online @if($node->latency_ms) ({{ $node->latency_ms }}ms) @endif
                                @elseif($node->status === 'offline') ✗ Offline
                                @endif
                            </option>
                        @empty
                            <option value="" disabled>No regions available</option>
                        @endforelse
                    </flux:select>
                    <flux:description>The system will automatically assign an available proxy port on this node.</flux:description>
                    <flux:error name="node_id" />
                </flux:field>

                {{-- Subscription --}}
                <flux:field>
                    <flux:label badge="Required">Subscription Plan</flux:label>
                    <flux:select wire:model="subscription_id">
                        @if($subscriptions->isEmpty())
                            <option value="" disabled selected>No active subscription</option>
                        @else
                            <option value="" disabled selected>Choose a plan…</option>
                            @foreach($subscriptions as $sub)
                                @php
                                    $used  = auth()->user()->servers()->where('subscription_id', $sub->id)->count();
                                    $left  = $sub->max_server - $used;
                                    $full  = $left <= 0;
                                @endphp
                                <option value="{{ $sub->id }}" {{ $full ? 'disabled' : '' }}>
                                    {{ $sub->plan_name }} — {{ $left }}/{{ $sub->max_server }} slots
                                    @if($sub->expired_at)
                                        (Exp: {{ $sub->expired_at->locale('id')->translatedFormat('d F Y H:i') }} WIB)
                                    @endif
                                    {{ $full ? '- Full' : '' }}
                                </option>
                            @endforeach
                        @endif
                    </flux:select>
                    <flux:description>Each server occupies one slot from the subscription's quota.</flux:description>
                    <flux:error name="subscription_id" />
                </flux:field>
            </div>

            <div class="flex gap-2 pt-1">
                <flux:spacer />
                <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-500 border-0 px-8" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createServer">Deploy Now</span>
                    <span wire:loading wire:target="createServer">Provisioning…</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

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

<div class="space-y-8">
    {{-- Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-8 text-white shadow-xl">
        <div class="relative z-10">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-blue-200 text-sm font-medium mb-1">Welcome back,</p>
                    <h1 class="text-3xl font-bold tracking-tight">{{ $user->name }}</h1>
                    <p class="text-blue-200 text-sm mt-1">{{ $user->email }}</p>
                </div>
                <flux:button variant="primary" href="{{ route('servers.index') }}" class="bg-white/15 hover:bg-white/25 border-white/20 text-white border backdrop-blur-sm" icon="plus">
                    New Instance
                </flux:button>
            </div>

            <div class="mt-8 grid grid-cols-3 gap-6">
                <div>
                    <div class="text-4xl font-bold">{{ $totalActive }}</div>
                    <div class="text-blue-200 text-xs mt-1 uppercase tracking-wider">Active Servers</div>
                </div>
                <div>
                    <div class="text-4xl font-bold">{{ $totalLimit }}</div>
                    <div class="text-blue-200 text-xs mt-1 uppercase tracking-wider">Server Quota</div>
                </div>
                <div>
                    <div class="text-4xl font-bold">{{ $nodes }}</div>
                    <div class="text-blue-200 text-xs mt-1 uppercase tracking-wider">Online Nodes</div>
                </div>
            </div>
        </div>

        {{-- Decorative blobs --}}
        <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-16 h-56 w-56 rounded-full bg-indigo-500/20 blur-2xl pointer-events-none"></div>
    </div>

    {{-- Usage Bar --}}
    @if($subs->isNotEmpty())
    <flux:card class="p-5">
            <div class="flex justify-between items-center mb-3">
                <flux:heading level="2">Resource Usage</flux:heading>
                <span class="text-sm font-mono text-zinc-500">{{ $totalUsed }} / {{ $totalLimit }} used</span>
            </div>
            <div class="w-full h-3 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                @php
                    $pct = $totalLimit > 0 ? min(100, round(($totalUsed / $totalLimit) * 100)) : 0;
                    $color = $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber-400' : 'bg-blue-500');
                @endphp
                <div class="{{ $color }} h-full rounded-full transition-all duration-700" style="width: {{ $pct }}%"></div>
            </div>
            <div class="flex justify-between mt-2">
                <flux:text variant="subtle" size="xs">{{ $pct }}% of quota used (all active and suspended servers)</flux:text>
                @if($pct >= 90)
                    <flux:text size="xs" class="text-red-500 font-medium">Near limit!</flux:text>
                @endif
            </div>
        </flux:card>
    @endif

    {{-- Active Subscriptions --}}
    @if($subs->isNotEmpty())
        <div>
            <flux:heading level="2" class="mb-4">Your Plans</flux:heading>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($subs as $sub)
                    @php
                        // Count all statuses to match ServerService limit enforcement accurately
                        $used   = $servers->where('subscription_id', $sub->id)->count();
                        $subPct = $sub->max_server > 0 ? min(100, round(($used / $sub->max_server) * 100)) : 0;
                    @endphp
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $sub->plan_name }}</div>
                                <div class="text-xs text-zinc-400 font-mono mt-0.5">{{ $sub->external_id }}</div>
                                @if($sub->expired_at)
                                    <div class="text-[11px] text-orange-500 font-medium mt-1">
                                        Exp: {{ $sub->expired_at->locale('id')->translatedFormat('d M Y H:i') }} WIB
                                    </div>
                                @endif
                            </div>
                            @if($sub->isActive())
                                <flux:badge color="green" size="sm" variant="subtle">Active</flux:badge>
                            @else
                                <flux:badge color="red" size="sm" variant="subtle">Expired</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-baseline gap-1 mt-4 mb-2">
                            <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $used }}</span>
                            <span class="text-zinc-400 text-sm">/ {{ $sub->max_server }}</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                            <div class="h-full rounded-full bg-blue-500 transition-all" style="width: {{ $subPct }}%"></div>
                        </div>
                        <flux:text variant="subtle" size="xs" class="mt-1.5">{{ $sub->max_server - $used }} slots remaining</flux:text>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <flux:callout variant="warning" heading="No Active Subscription">
            You don't have an active subscription. Contact support to get started.
        </flux:callout>
    @endif

    {{-- My Servers --}}
    <div>
        <div class="flex justify-between items-center mb-4">
            <flux:heading level="2">My Servers</flux:heading>
            <flux:button href="{{ route('servers.index') }}" variant="ghost" size="sm" icon="arrow-right">Manage all</flux:button>
        </div>

        @if($servers->isEmpty())
            <flux:card class="py-16 text-center border-dashed">
                <flux:icon icon="server-stack" size="xl" class="mx-auto mb-4 text-zinc-300" />
                <flux:heading>No active instances</flux:heading>
                <flux:text variant="subtle" class="mt-1 text-sm">Deploy your first reverse proxy server.</flux:text>
                <flux:button href="{{ route('servers.index') }}" class="mt-6 bg-blue-600 hover:bg-blue-500 border-0" variant="primary" icon="plus">Provision Now</flux:button>
            </flux:card>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($servers->take(6) as $server)
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $server->label }}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-400 font-mono mt-0.5">{{ $server->identifier }}</div>
                            </div>
                            @if($server->status === 'active')
                                <div class="flex items-center gap-1.5">
                                    <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.6)]"></div>
                                    <span class="text-[10px] font-bold text-emerald-600 uppercase">Live</span>
                                </div>
                            @elseif($server->status === 'pending')
                                <flux:badge color="yellow" size="sm" variant="subtle" class="animate-pulse">Provisioning</flux:badge>
                            @else
                                <flux:badge color="red" size="sm" variant="subtle">Failed</flux:badge>
                            @endif
                        </div>
                        <div class="text-xs text-zinc-400 flex items-center gap-1.5">
                            <flux:icon icon="map-pin" size="xs" />
                            {{ $server->node->label ?? 'Unknown' }}
                        </div>
                        <a href="{{ route('servers.show', $server->id) }}" class="text-[11px] text-blue-600 hover:underline flex items-center gap-1">
                            View Details <flux:icon icon="arrow-right" size="xs" />
                        </a>
                    </div>
                @endforeach
            </div>
            @if($servers->count() > 6)
                <div class="mt-4 text-center">
                    <flux:button href="{{ route('servers.index') }}" variant="ghost" size="sm">
                        View {{ $servers->count() - 6 }} more servers
                    </flux:button>
                </div>
            @endif
        @endif
    </div>
</div>

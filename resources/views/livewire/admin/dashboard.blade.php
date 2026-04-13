<div class="space-y-8">
    <header class="flex justify-between items-end gap-4">
        <div>
            <flux:heading size="xl" level="1">Admin Dashboard</flux:heading>
            <flux:subheading>Platform overview real-time infrastructure metrics.</flux:subheading>
        </div>
        <flux:badge color="green" icon="check-circle" size="sm">Systems Operational</flux:badge>
    </header>

    <flux:separator variant="subtle" />

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <flux:card class="flex flex-col gap-1 p-4">
            <flux:text variant="subtle" size="xs" class="uppercase tracking-widest">Users</flux:text>
            <span class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $stats['total_users'] }}</span>
            <flux:text variant="subtle" size="xs">Registered clients</flux:text>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4">
            <flux:text variant="subtle" size="xs" class="uppercase tracking-widest">Servers</flux:text>
            <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['active_servers'] }}</span>
            <flux:text variant="subtle" size="xs">of {{ $stats['total_servers'] }} active</flux:text>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4">
            <flux:text variant="subtle" size="xs" class="uppercase tracking-widest">Nodes</flux:text>
            <span class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['online_nodes'] }}</span>
            <flux:text variant="subtle" size="xs">of {{ $stats['total_nodes'] }} online</flux:text>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4">
            <flux:text variant="subtle" size="xs" class="uppercase tracking-widest">Subscriptions</flux:text>
            <span class="text-3xl font-bold text-violet-600 dark:text-violet-400">{{ $stats['active_subs'] }}</span>
            <flux:text variant="subtle" size="xs">Active plans</flux:text>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4">
            <flux:text variant="subtle" size="xs" class="uppercase tracking-widest">Failed</flux:text>
            <span class="text-3xl font-bold {{ $stats['failed_servers'] > 0 ? 'text-red-500' : 'text-zinc-400' }}">
                {{ $stats['failed_servers'] }}
            </span>
            <flux:text variant="subtle" size="xs">Provisioning errors</flux:text>
        </flux:card>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Servers per Node Bar Chart --}}
        <div class="lg:col-span-2">
            <flux:card class="h-full">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <flux:heading level="2">Servers per Node</flux:heading>
                        <flux:subheading size="sm">Distribution across infrastructure nodes</flux:subheading>
                    </div>
                    <flux:badge variant="subtle" size="sm">Bar Chart</flux:badge>
                </div>
                <div class="relative h-56">
                    <canvas id="serversPerNodeChart"></canvas>
                </div>  
            </flux:card>
        </div>

        {{-- Server Status Doughnut --}}
        <flux:card>
            <div class="mb-6">
                <flux:heading level="2">Server Status</flux:heading>
                <flux:subheading size="sm">Current status breakdown</flux:subheading>
            </div>
            <div class="relative h-44 flex items-center justify-center">
                <canvas id="serverStatusChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <div class="h-2.5 w-2.5 rounded-full bg-emerald-500"></div>
                        <span class="text-zinc-500">Active</span>
                    </div>
                    <span class="font-bold">{{ $statusBreakdown['active'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <div class="h-2.5 w-2.5 rounded-full bg-amber-400"></div>
                        <span class="text-zinc-500">Provisioning</span>
                    </div>
                    <span class="font-bold">{{ $statusBreakdown['pending'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <div class="h-2.5 w-2.5 rounded-full bg-red-500"></div>
                        <span class="text-zinc-500">Failed</span>
                    </div>
                    <span class="font-bold">{{ $statusBreakdown['failed'] }}</span>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Bottom Row: Node Health Table + Recent Servers --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Node Health --}}
        <flux:card>
            <flux:heading level="2" class="mb-4">Node Health</flux:heading>
            <div class="space-y-3">
                @forelse($nodes as $node)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
                        <div class="flex items-center gap-3">
                            @if($node->status === 'online')
                                <div class="h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.6)] shrink-0"></div>
                            @elseif($node->status === 'offline')
                                <div class="h-2 w-2 rounded-full bg-red-500 shrink-0"></div>
                            @else
                                <div class="h-2 w-2 rounded-full bg-zinc-400 shrink-0"></div>
                            @endif
                            <div>
                                <div class="text-sm font-semibold">{{ $node->label }}</div>
                                <div class="text-[10px] text-zinc-400 font-mono">{{ $node->name }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            @if($node->latency_ms !== null)
                                <div class="text-xs font-bold {{ $node->latency_ms < 100 ? 'text-emerald-500' : ($node->latency_ms < 300 ? 'text-amber-500' : 'text-red-500') }}">
                                    {{ $node->latency_ms }}ms
                                </div>
                            @else
                                <span class="text-[10px] text-zinc-400">—</span>
                            @endif
                            @if($node->last_checked_at)
                                <div class="text-[10px] text-zinc-400">{{ $node->last_checked_at->diffForHumans() }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 text-sm py-6">No nodes registered.</div>
                @endforelse
            </div>
        </flux:card>

        {{-- Recent Servers --}}
        <div class="lg:col-span-2">
            <flux:card class="h-full">
                <div class="flex justify-between items-center mb-4">
                    <flux:heading level="2">Recent Deployments</flux:heading>
                    <flux:button href="{{ route('admin.servers') }}" size="sm" variant="ghost" icon="arrow-right">View all</flux:button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-wider text-zinc-400 border-b border-zinc-100 dark:border-zinc-800">
                                <th class="pb-3 font-medium">Identifier</th>
                                <th class="pb-3 font-medium">Owner</th>
                                <th class="pb-3 font-medium">Node</th>
                                <th class="pb-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse($recentServers as $s)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="py-2.5 font-mono text-xs text-blue-600 dark:text-blue-400">{{ $s['identifier'] }}</td>
                                    <td class="py-2.5 text-xs text-zinc-500">{{ $s['user']['name'] ?? '—' }}</td>
                                    <td class="py-2.5 text-xs text-zinc-500">{{ $s['node']['label'] ?? '—' }}</td>
                                    <td class="py-2.5">
                                        @if($s['status'] === 'active')
                                            <flux:badge color="green" size="sm" variant="pill">Active</flux:badge>
                                        @elseif($s['status'] === 'pending')
                                            <flux:badge color="yellow" size="sm" variant="pill">Provisioning</flux:badge>
                                        @else
                                            <flux:badge color="red" size="sm" variant="pill">Failed</flux:badge>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-zinc-400 text-sm">No servers deployed yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const labelColor = isDark ? '#a1a1aa' : '#71717a';

    // Bar Chart – Servers per Node
    const barCtx = document.getElementById('serversPerNodeChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: @json($serversPerNode['labels']),
                datasets: [{
                    label: 'Servers',
                    data: @json($serversPerNode['values']),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: labelColor } },
                    y: {
                        grid: { color: gridColor },
                        ticks: { color: labelColor, stepSize: 1 },
                        beginAtZero: true,
                    }
                }
            }
        });
    }

    // Doughnut Chart – Server Status
    const doughnutCtx = document.getElementById('serverStatusChart');
    if (doughnutCtx) {
        new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Provisioning', 'Failed'],
                datasets: [{
                    data: [
                        {{ $statusBreakdown['active'] }},
                        {{ $statusBreakdown['pending'] }},
                        {{ $statusBreakdown['failed'] }}
                    ],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                }
            }
        });
    }
});
</script>
@endpush

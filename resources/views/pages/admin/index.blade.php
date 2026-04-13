<x-layouts::app :title="__('Admin Dashboard')">
    <div class="p-6 space-y-6">
        <flux:heading size="xl">Admin Control Center</flux:heading>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-flux::card class="p-6 bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <flux:icon icon="cpu-chip" class="text-blue-600" />
                    </div>
                    <div>
                        <div class="text-sm text-neutral-500">Total Nodes</div>
                        <div class="text-2xl font-bold">{{ \App\Models\Node::count() }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-700">
                    <flux:link href="{{ route('admin.nodes') }}" class="text-sm">Manage Nodes &rarr;</flux:link>
                </div>
            </x-flux::card>

            <x-flux::card class="p-6 bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <flux:icon icon="server" class="text-green-600" />
                    </div>
                    <div>
                        <div class="text-sm text-neutral-500">Active Servers</div>
                        <div class="text-2xl font-bold">{{ \App\Models\Server::where('status', 'active')->count() }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-700">
                    <flux:link href="{{ route('admin.servers') }}" class="text-sm">View All Servers &rarr;</flux:link>
                </div>
            </x-flux::card>

            <x-flux::card class="p-6 bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                        <flux:icon icon="credit-card" class="text-purple-600" />
                    </div>
                    <div>
                        <div class="text-sm text-neutral-500">Plans</div>
                        <div class="text-2xl font-bold">{{ \App\Models\Plan::count() }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-700">
                    <flux:link href="{{ route('admin.plans') }}" class="text-sm">Manage Plans &rarr;</flux:link>
                </div>
            </x-flux::card>
        </div>
    </div>
</x-layouts::app>

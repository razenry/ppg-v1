<div class="space-y-8 max-w-5xl">
    <header class="mb-6">
        <flux:heading size="xl" level="1">My Plans</flux:heading>
        <flux:subheading>Manage your active subscriptions and check your resource quotas.</flux:subheading>
    </header>

    @if($subs->isEmpty())
        <flux:callout variant="warning" heading="No Subscriptions Found">
            You currently do not have any active subscriptions. Please contact support.
        </flux:callout>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($subs as $sub)
                <flux:card class="flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold">{{ $sub->plan_name }}</h3>
                                <p class="text-sm font-mono text-zinc-500 mt-1">{{ $sub->external_id }}</p>
                            </div>
                            @if($sub->isActive())
                                <flux:badge color="green">Active</flux:badge>
                            @else
                                <flux:badge color="red">Inactive</flux:badge>
                            @endif
                        </div>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <span class="text-sm text-zinc-500">Resource Quota</span>
                                <span class="text-sm font-medium">{{ $sub->servers_count }} / {{ $sub->max_server }} Servers</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <span class="text-sm text-zinc-500">Expiration Date</span>
                                <span class="text-sm font-medium {{ $sub->expired_at && $sub->expired_at->isPast() ? 'text-red-500' : 'text-zinc-900 dark:text-white' }}">
                                    @if($sub->expired_at)
                                        {{ $sub->expired_at->locale('id')->translatedFormat('d F Y H:i') }} WIB
                                    @else
                                        Lifetime
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <span class="text-sm text-zinc-500">Created At</span>
                                <span class="text-sm font-medium">{{ $sub->created_at->locale('id')->translatedFormat('d F Y') }}</span>
                            </div>
                        </div>
                    </div>

                    @if($sub->isActive() && $sub->servers_count < $sub->max_server)
                        <flux:button href="{{ route('servers.index') }}" variant="primary" icon="server">Deploy New Server</flux:button>
                    @endif
                </flux:card>
            @endforeach
        </div>
    @endif
</div>

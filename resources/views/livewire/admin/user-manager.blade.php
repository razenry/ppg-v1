<div class="space-y-6">
    <header class="flex justify-between items-end gap-4">
        <div>
            <flux:heading size="xl" level="1">User Management</flux:heading>
            <flux:subheading>Manage all accounts, roles, and security details.</flux:subheading>
        </div>
        <div class="w-full md:w-auto flex gap-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search users..." />
            <flux:button variant="primary" wire:click="openModal" icon="user-plus" class="shrink-0">New User</flux:button>
        </div>
    </header>

    <flux:separator variant="subtle" />

    @error('delete')
        <flux:toast variant="danger" heading="Action Denied" text="{{ $message }}" class="mb-4" />
    @enderror

    <flux:card p="0" class="overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>User</flux:table.column>
                <flux:table.column>Security</flux:table.column>
                <flux:table.column>Servers</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($users as $user)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:profile :initials="$user->initials()" size="sm" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white flex items-center gap-2">
                                        {{ $user->name }}
                                        @if($user->is_admin)
                                            <flux:badge size="sm" color="blue" icon="shield-check">Admin</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($user->two_factor_secret)
                                <flux:badge size="sm" color="green" icon="check-circle">2FA Active</flux:badge>
                            @else
                                <flux:badge size="sm" variant="subtle" icon="x-circle">2FA Inactive</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:badge size="sm" variant="subtle">{{ $user->servers_count }} Servers</flux:badge>
                                <flux:badge size="sm" variant="subtle">{{ $user->subscriptions_count }} Subs</flux:badge>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" icon="ellipsis-horizontal" size="sm" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item href="{{ route('admin.impersonate.start', $user->id) }}" icon="arrow-right-end-on-rectangle">Login as User</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="openModal({{ $user->id }})" icon="pencil-square">Edit User</flux:menu.item>
                                    <flux:menu.item wire:click="openSubModal({{ $user->id }})" icon="ticket">Manage Subs</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="confirmDeleteUser({{ $user->id }})" icon="trash" variant="danger">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center text-zinc-500 py-8">
                            No users found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <!-- User Modal -->
    <flux:modal wire:model="showModal" class="md:w-[500px]">
        <div class="mb-6">
            <flux:heading size="lg">{{ $editingUserId ? 'Edit User' : 'Create User' }}</flux:heading>
            <flux:subheading>Modify user access and details.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:field>
                <flux:label>Full Name</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email Address</flux:label>
                <flux:input wire:model="email" type="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>{{ $editingUserId ? 'New Password (leave blank to keep current)' : 'Password' }}</flux:label>
                <flux:input wire:model="password" type="password" viewable />
                <flux:error name="password" />
            </flux:field>

            <div class="pt-2">
                <flux:switch wire:model="is_admin" label="Administrator privileges" description="Allows full access to manage the entire panel and network." />
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Subscriptions Modal -->
    <flux:modal wire:model="showSubModal" class="md:w-[700px]">
        @if($manageUser)
            <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700 pb-4">
                <flux:heading size="lg">Manage Subscriptions</flux:heading>
                <flux:subheading>Assign or revoke plans for <strong>{{ $manageUser->name }}</strong>.</flux:subheading>
            </div>

            @error('subscription')
                <flux:toast variant="danger" heading="Cannot Revoke" text="{{ $message }}" class="mb-4" />
            @enderror

            <!-- Active Subscriptions Table -->
            <div class="mb-8">
                <flux:heading size="md" class="mb-3">Assigned Subscriptions</flux:heading>
                <div class="border rounded-xl border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="px-4 py-3 font-medium">Plan Name</th>
                                <th class="px-4 py-3 font-medium">Limits</th>
                                <th class="px-4 py-3 font-medium">Status & Expiration</th>
                                <th class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($manageUser->subscriptions as $sub)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ $sub->plan_name }}</div>
                                        <div class="text-xs font-mono text-zinc-500 mt-0.5">{{ $sub->external_id }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm"><span class="font-medium">{{ $sub->servers->count() }}</span> / {{ $sub->max_server }} limits</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($sub->isActive())
                                            <flux:badge color="green" size="sm" variant="subtle">Active</flux:badge>
                                        @else
                                            <flux:badge color="red" size="sm" variant="subtle">Expired</flux:badge>
                                        @endif
                                        @if($sub->expired_at)
                                            <div class="mt-1 text-[11px] font-medium text-zinc-500">Exp: {{ $sub->expired_at->locale('id')->translatedFormat('d M Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right flex gap-2 justify-end">
                                        @if(!$sub->isActive())
                                            <flux:button variant="primary" size="sm" icon="arrow-path" wire:click="renewSubscription({{ $sub->id }})">Renew</flux:button>
                                        @endif
                                        <flux:button variant="danger" size="sm" icon="x-mark" wire:click="confirmRevokeSubscription({{ $sub->id }})">Revoke</flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-zinc-500">
                                        This user has no active subscriptions.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Provision New Subscription -->
            <div>
                <flux:heading size="md" class="mb-3">Assign New Plan</flux:heading>
                <form wire:submit="assignSubscription" class="flex items-end gap-3">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>Select Plan to Assign</flux:label>
                            <flux:select wire:model="selectedPlanId">
                                <option value="" disabled selected>Choose a package...</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} (Up to {{ $plan->max_server }} servers)</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="selectedPlanId" />
                        </flux:field>
                    </div>
                    <flux:button type="submit" variant="primary" icon="plus">Assign Plan</flux:button>
                </form>
            </div>
            
            <div class="mt-8 flex justify-end">
                <flux:button variant="ghost" wire:click="$set('showSubModal', false)">Close Panel</flux:button>
            </div>
        @endif
    </flux:modal>

    {{-- Secure Delete Modal (Generic) --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-[28rem]">
        <form wire:submit="executeDelete" class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-500">
                    {{ $deleteType === 'user' ? 'Delete User?' : 'Revoke Subscription?' }}
                </flux:heading>
                <flux:subheading class="mt-2">
                    <p>You're about to forcefully and permanently perform this action. This cannot be undone.</p>
                    <p class="mt-2">Please type <strong class="text-zinc-900 dark:text-white font-mono bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">{{ $deleteTargetExpected }}</strong> to confirm.</p>
                </flux:subheading>
            </div>

            <flux:field>
                <flux:input wire:model.live="deleteVerificationInput" placeholder="{{ $deleteTargetExpected }}" autocomplete="off" />
                <flux:error name="deleteVerificationInput" />
            </flux:field>

            <div class="flex gap-2" x-data="{ countdown: 5 }" x-init="
                $watch('$wire.showDeleteModal', value => {
                    if (value) { countdown = 5; let i = setInterval(() => { if(countdown > 0) countdown--; else clearInterval(i); }, 1000); }
                })
            ">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="danger" x-bind:disabled="countdown > 0 || $wire.deleteVerificationInput !== $wire.deleteTargetExpected">
                    <span x-show="countdown > 0" x-text="'Wait ' + countdown + 's'"></span>
                    <span x-show="countdown === 0">Confirm Delete</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

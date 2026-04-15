<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile" class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700 font-sans">
        <flux:sidebar.header>
            <flux:brand href="{{ route('dashboard') }}" logo="https://fluxui.com/img/demo/logo.png" name="Raznar Hosting" class="px-2" />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:navlist variant="minimal">
            <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Dashboard') }}
            </flux:navlist.item>
            @if(auth()->user()?->is_admin == false)
                <flux:navlist.item icon="server" :href="route('servers.index')" :current="request()->routeIs('servers.*')" wire:navigate>
                    {{ __('My Servers') }}
                </flux:navlist.item>
                <flux:navlist.item icon="ticket" :href="route('my-plan')" :current="request()->routeIs('my-plan')" wire:navigate>
                    {{ __('My Plans') }}
                </flux:navlist.item>
            @endif

            @if(auth()->user()?->is_admin)
                <flux:navlist.group heading="Administration" class="mt-8">
                    <flux:navlist.item icon="cpu-chip" :href="route('admin.nodes')" :current="request()->routeIs('admin.nodes')" wire:navigate>
                        {{ __('Nodes') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="credit-card" :href="route('admin.plans')" :current="request()->routeIs('admin.plans')" wire:navigate>
                        {{ __('Plans') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="adjustments-horizontal" :href="route('admin.servers')" :current="request()->routeIs('admin.servers')" wire:navigate>
                        {{ __('Manage Servers') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                        {{ __('Users') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="cog-8-tooth" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" wire:navigate>
                        {{ __('System Settings') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @endif
        </flux:navlist>

        <flux:spacer />

        @auth
            @if(session()->has('sso_admin_id'))
                <flux:navlist variant="minimal" class="mb-4">
                    <form id="sso-return-form" action="{{ route('sso.return') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <flux:navlist.item icon="arrow-uturn-left" href="#" onclick="event.preventDefault(); document.getElementById('sso-return-form').submit();" class="text-orange-600 dark:text-orange-400 font-bold bg-orange-50 dark:bg-orange-950/50">
                        {{ __('Return to Admin') }}
                    </flux:navlist.item>
                </flux:navlist>
            @endif

            <flux:navlist variant="minimal">
                <flux:navlist.item icon="cog-6-tooth" :href="route('profile.edit')" wire:navigate>{{ __('Settings') }}</flux:navlist.item>
            </flux:navlist>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" icon-trailing="chevron-up-down" />

                <flux:menu>
                    @if(auth()->user()->is_admin)
                        <flux:menu.item icon="arrow-path-rounded-square" href="{{ route('admin.impersonate.start', auth()->user()->id) }}">
                            {{ __('Switch to Client Mode') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                    @endif
                    <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        @endauth
    </flux:sidebar>

    <flux:header class="lg:hidden border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:brand href="{{ route('dashboard') }}" logo="https://fluxui.com/img/demo/logo.png" name="Raznar Hosting" />
        <flux:spacer />
        @auth
            <flux:dropdown position="bottom" align="end">
                <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                <flux:menu>
                    <flux:menu.item icon="user" :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                    <flux:menu.separator />
                    @if(auth()->user()->is_admin)
                        <flux:menu.item icon="arrow-path-rounded-square" href="{{ route('admin.impersonate.start', auth()->user()->id) }}">
                            {{ __('Switch to Client Mode') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                    @endif
                    <flux:menu.item
                        icon="arrow-right-start-on-rectangle"
                        href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    >
                        {{ __('Log out') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endauth
    </flux:header>

    {{-- The $slot will be wrapped in flux:main by layouts/app.blade.php --}}
    {{ $slot }}

    @fluxScripts
    @stack('scripts')
</body>

</html>

<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main container>
        @if(session()->has('impersonated_user_id'))
            <div class="mb-6 flex items-center justify-between px-4 py-3 bg-amber-600 text-white rounded-xl shadow-lg animate-pulse">
                <div class="flex items-center gap-3">
                    <flux:icon icon="user-circle" size="md" />
                    <div>
                        @if(session('impersonated_user_id') == session('original_admin_id'))
                            <span class="font-bold">Client Mode Active</span>
                            <p class="text-xs opacity-90">You are currently testing the interface as a standard Client.</p>
                        @else
                            <span class="font-bold">Impersonation Mode Active</span>
                            <p class="text-xs opacity-90">You are currently logged in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).</p>
                        @endif
                    </div>
                </div>
                <form action="{{ route('admin.impersonate.stop') }}" method="POST">
                    @csrf
                    <flux:button type="submit" variant="ghost" size="sm" icon="arrow-left-end-on-rectangle" class="font-bold text-white bg-white/20 hover:bg-white/30 border-0">Exit Client Mode</flux:button>
                </form>
            </div>
        @endif

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

<x-layouts::app :title="__('Dashboard')">
    @if(auth()->user()?->is_admin)
        <livewire:admin.dashboard />
    @else
        <livewire:client.dashboard />
    @endif
</x-layouts::app>

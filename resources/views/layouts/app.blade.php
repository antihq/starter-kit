<x-layouts.app.sidebar :title="$title ?? null" :breadcrumbs="$breadcrumbs ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
    <livewire:organizations.create />
    <flux:toast />
</x-layouts.app.sidebar>

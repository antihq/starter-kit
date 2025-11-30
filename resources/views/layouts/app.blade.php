<x-layouts.app.header :title="$title ?? null">
    <flux:main class="lg:bg-white dark:lg:bg-zinc-900 lg:p-10">
        {{ $slot }}
    </flux:main>

    @auth
        <livewire:organizations.create />
    @endauth

    <flux:toast />
</x-layouts.app.header>

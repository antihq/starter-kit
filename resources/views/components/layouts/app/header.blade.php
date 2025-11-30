<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950 dark antialiased">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950">
        <flux:header class="lg:border-b border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" size="sm" />

            <a href="{{ route('dashboard') }}" class="max-lg:hidden mr-5">
                <x-logo class="h-6" />
            </a>

            @auth
                <div class="max-lg:hidden flex items-center h-full">
                    <livewire:organizations-dropdown />
                    <flux:separator vertical class="my-5 mx-1" />
                </div>
            @endauth

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            @auth
                <flux:dropdown position="top" align="end">
                    <flux:button size="sm" variant="ghost" square>
                        <flux:avatar size="xs" :name="Auth::user()->name" color="auto" initials:single />
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item :href="route('settings.profile')" icon="cog-8-tooth" icon:variant="micro" wire:navigate>{{ __('Settings') }}</flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" icon:variant="micro" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:button :href="route('dashboard')" variant="subtle">Account</flux:button>
            @endauth
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')">
                    <flux:navlist.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>

        {{ $slot }}

        <flux:footer class="lg:border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <flux:text class="text-sm/6">
                    Built with
                    <flux:icon.heart variant="micro" class="inline" />
                    by
                    <flux:link href="https://x.com/oliverservinX" :accent="false">Oliver Servín</flux:link>
                </flux:text>
                <flux:text class="mt-6 lg:mt-8 text-sm/6">
                    &copy; {{ date('Y') }} Anti Software. All rights reserved. Problems or questions? Contact <
                    <flux:link href="mailto:support@antihq.com" :accent="false">support@antihq.com</flux:link>
                    >.
                </flux:text>
            </div>
        </flux:footer>

        @fluxScripts
    </body>
</html>

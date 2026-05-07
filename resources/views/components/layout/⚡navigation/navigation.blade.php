<nav x-data="{ open: false }" class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                    <img src="{{ Vite::asset('resources/assets/metodista-favicon.png') }}" alt="" class="h-8 w-auto">
                    <span class="font-semibold tracking-tight">{{ config('app.name') }}</span>
                </a>

                <div class="hidden items-center gap-6 sm:flex">
                    <a href="{{ route('posts.index') }}" wire:navigate
                       class="text-sm font-medium {{ request()->routeIs('home') || (request()->routeIs('posts.*') && ! request()->routeIs('admin.*')) ? 'text-[#c8202f] dark:text-rose-300' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white' }}">
                        {{ __('Posts') }}
                    </a>

                    @auth
                        <a href="{{ route('prayer.index') }}" wire:navigate
                           class="text-sm font-medium {{ request()->routeIs('prayer.*') && ! request()->routeIs('admin.*') ? 'text-[#c8202f] dark:text-rose-300' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white' }}">
                            {{ __('Prayer schedule') }}
                        </a>
                        <a href="{{ route('fasting.index') }}" wire:navigate
                           class="text-sm font-medium {{ request()->routeIs('fasting.*') ? 'text-[#c8202f] dark:text-rose-300' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white' }}">
                            {{ __('Fasting calendar') }}
                        </a>

                        @php
                            $hasPostMgmt = auth()->user()->can('posts.create.shared') || auth()->user()->can('posts.create.local') || auth()->user()->can('comments.moderate');
                            $hasSettings = auth()->user()->can('church.manage') || auth()->user()->can('users.manage') || auth()->user()->can('users.manage.local') || auth()->user()->can('prayer.schedule.manage');
                        @endphp

                        @if ($hasPostMgmt || $hasSettings)
                            <flux:dropdown align="start">
                                <flux:button size="sm" variant="ghost" icon="cog-6-tooth" icon-trailing="chevron-down">
                                    {{ __('Admin') }}
                                </flux:button>
                                <flux:menu class="min-w-[14rem]">
                                    @if ($hasPostMgmt)
                                        <flux:menu.group :heading="__('Posts management')">
                                            @can('posts.create.local')
                                                <flux:menu.item :href="route('admin.posts.index')" wire:navigate icon="document-text">
                                                    {{ __('Posts manager') }}
                                                </flux:menu.item>
                                            @endcan
                                            @can('comments.moderate')
                                                <flux:menu.item :href="route('admin.comments.index')" wire:navigate icon="chat-bubble-left-right">
                                                    {{ __('Moderate comments') }}
                                                </flux:menu.item>
                                            @endcan
                                        </flux:menu.group>
                                    @endif

                                    @if ($hasPostMgmt && $hasSettings)
                                        <flux:menu.separator />
                                    @endif

                                    @if ($hasSettings)
                                        <flux:menu.group :heading="__('Settings')">
                                            @can('church.manage')
                                                <flux:menu.item :href="route('admin.churches.index')" wire:navigate icon="building-library">
                                                    {{ __('Churches') }}
                                                </flux:menu.item>
                                            @endcan

                                            @if (auth()->user()->can('users.manage') || auth()->user()->can('users.manage.local'))
                                                <flux:menu.item :href="route('admin.users.index')" wire:navigate icon="user-group">
                                                    {{ __('Administrators') }}
                                                </flux:menu.item>
                                                <flux:menu.item :href="route('admin.members.index')" wire:navigate icon="users">
                                                    {{ __('Members') }}
                                                </flux:menu.item>
                                            @endif

                                            @can('church.manage')
                                                <flux:menu.item :href="route('admin.regions.index')" wire:navigate icon="globe-americas">
                                                    {{ __('Ecclesiastical regions') }}
                                                </flux:menu.item>
                                            @endcan

                                            @can('prayer.schedule.manage')
                                                <flux:menu.item :href="route('admin.prayer-schedules.index')" wire:navigate icon="clock">
                                                    {{ __('Prayer schedules') }}
                                                </flux:menu.item>
                                                <flux:menu.item :href="route('admin.prayer-campaigns.index')" wire:navigate icon="megaphone">
                                                    {{ __('Prayer campaigns') }}
                                                </flux:menu.item>
                                            @endcan

                                            @can('fasting.calendar.manage')
                                                <flux:menu.item :href="route('admin.fasting-campaigns.index')" wire:navigate icon="calendar">
                                                    {{ __('Fasting campaigns') }}
                                                </flux:menu.item>
                                            @endcan
                                        </flux:menu.group>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                @auth
                    <livewire:church-context-switcher />
                @endauth
                <livewire:appearance-switcher />
                <livewire:locale-switcher />

                @auth
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon-trailing="chevron-down">
                            <span x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item :href="route('profile')" wire:navigate icon="user">{{ __('Profile') }}</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item wire:click="logout" icon="arrow-right-start-on-rectangle">{{ __('Log out') }}</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                @else
                    <flux:button :href="route('login')" size="sm" variant="ghost" wire:navigate>{{ __('Sign in') }}</flux:button>
                    <flux:button :href="route('register')" size="sm" variant="primary" wire:navigate>{{ __('Sign up') }}</flux:button>
                @endauth
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <a href="{{ route('posts.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Posts') }}</a>
            @auth
                <a href="{{ route('prayer.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Prayer schedule') }}</a>
                <a href="{{ route('fasting.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Fasting calendar') }}</a>

                @can('posts.create.local')
                    <a href="{{ route('admin.posts.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Posts manager') }}</a>
                @endcan
                @can('comments.moderate')
                    <a href="{{ route('admin.comments.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Moderate comments') }}</a>
                @endcan
                @can('church.manage')
                    <a href="{{ route('admin.churches.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Churches') }}</a>
                    <a href="{{ route('admin.regions.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Ecclesiastical regions') }}</a>
                @endcan
                @if (auth()->user()->can('users.manage') || auth()->user()->can('users.manage.local'))
                    <a href="{{ route('admin.users.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Administrators') }}</a>
                    <a href="{{ route('admin.members.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Members') }}</a>
                @endif
                @can('prayer.schedule.manage')
                    <a href="{{ route('admin.prayer-schedules.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Prayer schedules') }}</a>
                    <a href="{{ route('admin.prayer-campaigns.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Prayer campaigns') }}</a>
                @endcan
                @can('fasting.calendar.manage')
                    <a href="{{ route('admin.fasting-campaigns.index') }}" wire:navigate class="block px-4 py-2 text-sm font-medium">{{ __('Fasting campaigns') }}</a>
                @endcan
            @endauth
        </div>

        <div class="border-t border-zinc-200 px-4 pb-3 pt-3 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                @auth <livewire:church-context-switcher /> @endauth
                <livewire:appearance-switcher />
                <livewire:locale-switcher />
            </div>
            <div class="mt-3 space-y-1">
                @auth
                    <a href="{{ route('profile') }}" wire:navigate class="block px-1 py-1 text-sm">{{ __('Profile') }}</a>
                    <button wire:click="logout" class="block w-full text-start px-1 py-1 text-sm">{{ __('Log out') }}</button>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="block px-1 py-1 text-sm">{{ __('Sign in') }}</a>
                    <a href="{{ route('register') }}" wire:navigate class="block px-1 py-1 text-sm">{{ __('Sign up') }}</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
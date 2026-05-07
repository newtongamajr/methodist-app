<div>
    @php
        $hasPostMgmt = auth()->check() && (auth()->user()->can('posts.create.shared') || auth()->user()->can('posts.create.local') || auth()->user()->can('comments.moderate'));
        $hasSettings = auth()->check() && (auth()->user()->can('church.manage') || auth()->user()->can('users.manage') || auth()->user()->can('users.manage.local') || auth()->user()->can('prayer.schedule.manage') || auth()->user()->can('fasting.calendar.manage'));
        $currentAppearance = collect($this->appearanceOptions)->firstWhere('value', $this->appearance);
        $currentLocale = collect($this->localeOptions)->firstWhere('value', $this->locale);
    @endphp

    <flux:header container class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:brand
            :href="route('home')"
            wire:navigate
            :logo="Vite::asset('resources/assets/metodista-brand-light.jpg')"
            :name="__('Methodist Church')"
            class="me-6 dark:hidden"
        />
        <flux:brand
            :href="route('home')"
            wire:navigate
            :logo="Vite::asset('resources/assets/metodista-brand-dark.jpg')"
            :name="__('Methodist Church')"
            class="me-6 hidden dark:flex"
        />

        <flux:navbar class="-mb-px max-lg:hidden">
            <flux:navbar.item :href="route('posts.index')" wire:navigate>{{ __('Posts') }}</flux:navbar.item>
            @auth
                <flux:navbar.item :href="route('prayer.index')" wire:navigate>{{ __('Prayer schedule') }}</flux:navbar.item>
                <flux:navbar.item :href="route('fasting.index')" wire:navigate>{{ __('Fasting calendar') }}</flux:navbar.item>

                @if ($hasPostMgmt || $hasSettings)
                    <flux:dropdown>
                        <flux:navbar.item icon="cog-6-tooth" icon:trailing="chevron-down">{{ __('Admin') }}</flux:navbar.item>
                        <flux:menu class="min-w-56">
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
        </flux:navbar>

        <flux:spacer />

        @auth
            <livewire:church-context-switcher />

            <flux:dropdown align="end">
                <flux:profile
                    circle
                    :chevron="false"
                    :avatar="auth()->user()->avatarUrl()"
                    :avatar:name="auth()->user()->name"
                />

                <flux:menu class="min-w-[16rem]">
                    <div class="px-2 py-1.5">
                        <flux:text size="sm">{{ __('Signed in as') }}</flux:text>
                        <flux:heading
                            class="mt-1! truncate"
                            x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                            x-text="name"
                            x-on:profile-updated.window="name = $event.detail.name"
                        ></flux:heading>
                        <flux:text size="sm" class="truncate text-zinc-500 dark:text-zinc-400">
                            {{ auth()->user()->email }}
                        </flux:text>
                    </div>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('profile')" wire:navigate icon="user">
                        {{ __('Profile') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    <flux:menu.submenu :heading="__('Theme')" :icon="$currentAppearance['icon'] ?? 'computer-desktop'">
                        @foreach ($this->appearanceOptions as $option)
                            @php $isCurrent = $option['value'] === $this->appearance; @endphp
                            <flux:menu.item
                                :icon="$option['icon']"
                                :class="$isCurrent ? 'text-accent-content!' : ''"
                                x-on:click="window.applyAppearance('{{ $option['value'] }}'); $wire.switchAppearance('{{ $option['value'] }}');"
                            >
                                {{ $option['label'] }}
                                @if ($isCurrent)
                                    <flux:icon.check class="ms-auto size-4" />
                                @endif
                            </flux:menu.item>
                        @endforeach
                    </flux:menu.submenu>

                    <flux:menu.submenu :heading="__('Language').': '.($currentLocale['short'] ?? 'PT')" icon="language">
                        @foreach ($this->localeOptions as $option)
                            @php $isCurrent = $option['value'] === $this->locale; @endphp
                            <flux:menu.item
                                icon="flag"
                                :class="$isCurrent ? 'text-accent-content!' : ''"
                                wire:click="switchLocale('{{ $option['value'] }}')"
                            >
                                {{ $option['label'] }}
                                @if ($isCurrent)
                                    <flux:icon.check class="ms-auto size-4" />
                                @endif
                            </flux:menu.item>
                        @endforeach
                    </flux:menu.submenu>

                    <flux:menu.separator />

                    <flux:menu.item wire:click="logout" icon="arrow-right-start-on-rectangle">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @else
            <livewire:appearance-switcher />
            <livewire:locale-switcher />
            <flux:button :href="route('login')" size="sm" variant="ghost" wire:navigate>{{ __('Sign in') }}</flux:button>
            <flux:button :href="route('register')" size="sm" variant="primary" wire:navigate>{{ __('Sign up') }}</flux:button>
        @endauth
    </flux:header>

    <flux:sidebar sticky stashable collapsible="mobile" class="lg:hidden border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand
                :href="route('home')"
                :logo="Vite::asset('resources/assets/metodista-brand-light.jpg')"
                :logo:dark="Vite::asset('resources/assets/metodista-brand-dark.jpg')"
                :name="__('Methodist Church')"
            />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item :href="route('posts.index')" wire:navigate icon="newspaper">{{ __('Posts') }}</flux:sidebar.item>
            @auth
                <flux:sidebar.item :href="route('prayer.index')" wire:navigate icon="hand-raised">{{ __('Prayer schedule') }}</flux:sidebar.item>
                <flux:sidebar.item :href="route('fasting.index')" wire:navigate icon="calendar">{{ __('Fasting calendar') }}</flux:sidebar.item>

                @if ($hasPostMgmt)
                    <flux:sidebar.group expandable :heading="__('Posts management')">
                        @can('posts.create.local')
                            <flux:sidebar.item :href="route('admin.posts.index')" wire:navigate>{{ __('Posts manager') }}</flux:sidebar.item>
                        @endcan
                        @can('comments.moderate')
                            <flux:sidebar.item :href="route('admin.comments.index')" wire:navigate>{{ __('Moderate comments') }}</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif

                @if ($hasSettings)
                    <flux:sidebar.group expandable :heading="__('Settings')">
                        @can('church.manage')
                            <flux:sidebar.item :href="route('admin.churches.index')" wire:navigate>{{ __('Churches') }}</flux:sidebar.item>
                        @endcan
                        @if (auth()->user()->can('users.manage') || auth()->user()->can('users.manage.local'))
                            <flux:sidebar.item :href="route('admin.users.index')" wire:navigate>{{ __('Administrators') }}</flux:sidebar.item>
                            <flux:sidebar.item :href="route('admin.members.index')" wire:navigate>{{ __('Members') }}</flux:sidebar.item>
                        @endif
                        @can('church.manage')
                            <flux:sidebar.item :href="route('admin.regions.index')" wire:navigate>{{ __('Ecclesiastical regions') }}</flux:sidebar.item>
                        @endcan
                        @can('prayer.schedule.manage')
                            <flux:sidebar.item :href="route('admin.prayer-schedules.index')" wire:navigate>{{ __('Prayer schedules') }}</flux:sidebar.item>
                            <flux:sidebar.item :href="route('admin.prayer-campaigns.index')" wire:navigate>{{ __('Prayer campaigns') }}</flux:sidebar.item>
                        @endcan
                        @can('fasting.calendar.manage')
                            <flux:sidebar.item :href="route('admin.fasting-campaigns.index')" wire:navigate>{{ __('Fasting campaigns') }}</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif
            @endauth
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        @guest
            <flux:sidebar.nav>
                <flux:sidebar.item :href="route('login')" wire:navigate>{{ __('Sign in') }}</flux:sidebar.item>
                <flux:sidebar.item :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:sidebar.item>
            </flux:sidebar.nav>
        @endguest
    </flux:sidebar>
</div>
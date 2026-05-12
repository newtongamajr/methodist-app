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
                            @can('church.manage')
                                <flux:menu.submenu :heading="__('Structure')" icon="building-library">
                                    <flux:menu.item :href="route('admin.regions.index')" wire:navigate>{{ __('Ecclesiastical regions') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.districts.index')" wire:navigate>{{ __('Districts') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.churches.index')" wire:navigate>{{ __('Churches') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.groups.index')" wire:navigate>{{ __('Groups') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.assignment-roles.index')" wire:navigate>{{ __('Assignment roles') }}</flux:menu.item>
                                </flux:menu.submenu>
                            @endcan

                            @if (auth()->user()->can('users.manage') || auth()->user()->can('users.manage.local'))
                                <flux:menu.submenu :heading="__('People')" icon="identification">
                                    <flux:menu.item :href="route('admin.people.index')" wire:navigate>{{ __('People (generic)') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.people.index', ['nature' => 'pastor'])" wire:navigate>{{ __('Pastors') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.members.index')" wire:navigate>{{ __('Members') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.people.index', ['nature' => 'youth'])" wire:navigate>{{ __('Youth') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.people.index', ['nature' => 'teenager'])" wire:navigate>{{ __('Teenagers') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.people.index', ['nature' => 'child'])" wire:navigate>{{ __('Children') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.people.index', ['nature' => 'interested'])" wire:navigate>{{ __('Interested') }}</flux:menu.item>
                                    <flux:menu.item :href="route('admin.people.index', ['nature' => 'visitor'])" wire:navigate>{{ __('Visitors') }}</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item :href="route('admin.users.index')" wire:navigate>{{ __('Administrators') }}</flux:menu.item>
                                </flux:menu.submenu>
                            @endif

                            @if ($hasPostMgmt)
                                <flux:menu.submenu :heading="__('Posts management')" icon="document-text">
                                    @can('posts.create.local')
                                        <flux:menu.item :href="route('admin.posts.index')" wire:navigate>{{ __('Posts manager') }}</flux:menu.item>
                                    @endcan
                                    @can('comments.moderate')
                                        <flux:menu.item :href="route('admin.comments.index')" wire:navigate>{{ __('Moderate comments') }}</flux:menu.item>
                                    @endcan
                                </flux:menu.submenu>
                            @endif

                            @if (auth()->user()->can('prayer.schedule.manage') || auth()->user()->can('fasting.calendar.manage'))
                                <flux:menu.submenu :heading="__('Miscellaneous')" icon="megaphone">
                                    @can('prayer.schedule.manage')
                                        <flux:menu.item :href="route('admin.prayer-campaigns.index')" wire:navigate>{{ __('Prayer campaigns') }}</flux:menu.item>
                                    @endcan
                                    @can('fasting.calendar.manage')
                                        <flux:menu.item :href="route('admin.fasting-campaigns.index')" wire:navigate>{{ __('Fasting campaigns') }}</flux:menu.item>
                                    @endcan
                                </flux:menu.submenu>
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                @endif
            @endauth
        </flux:navbar>

        <flux:spacer />

        @auth
            <flux:tooltip :content="__('Search').' (⌘K)'">
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="magnifying-glass"
                    x-on:click="$dispatch('modal-show', { name: 'command-palette' })"
                    aria-label="{{ __('Search') }}"
                />
            </flux:tooltip>

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

                @can('church.manage')
                    <flux:sidebar.group expandable :heading="__('Structure')">
                        <flux:sidebar.item :href="route('admin.regions.index')" wire:navigate>{{ __('Ecclesiastical regions') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.districts.index')" wire:navigate>{{ __('Districts') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.churches.index')" wire:navigate>{{ __('Churches') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.groups.index')" wire:navigate>{{ __('Groups') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.assignment-roles.index')" wire:navigate>{{ __('Assignment roles') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @if (auth()->user()->can('users.manage') || auth()->user()->can('users.manage.local'))
                    <flux:sidebar.group expandable :heading="__('People')">
                        <flux:sidebar.item :href="route('admin.people.index')" wire:navigate>{{ __('People (generic)') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.people.index', ['nature' => 'pastor'])" wire:navigate>{{ __('Pastors') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.members.index')" wire:navigate>{{ __('Members') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.people.index', ['nature' => 'youth'])" wire:navigate>{{ __('Youth') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.people.index', ['nature' => 'teenager'])" wire:navigate>{{ __('Teenagers') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.people.index', ['nature' => 'child'])" wire:navigate>{{ __('Children') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.people.index', ['nature' => 'interested'])" wire:navigate>{{ __('Interested') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.people.index', ['nature' => 'visitor'])" wire:navigate>{{ __('Visitors') }}</flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.users.index')" wire:navigate>{{ __('Administrators') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

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

                @if (auth()->user()->can('prayer.schedule.manage') || auth()->user()->can('fasting.calendar.manage'))
                    <flux:sidebar.group expandable :heading="__('Miscellaneous')">
                        @can('prayer.schedule.manage')
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

    @auth
        {{-- Command palette: ⌘K / Ctrl+K opens it from anywhere on the page. --}}
        <div
            x-data
            x-on:keydown.window.k="if ($event.metaKey || $event.ctrlKey) { $event.preventDefault(); $dispatch('modal-show', { name: 'command-palette' }) }"
        >
            <flux:modal name="command-palette" class="md:max-w-xl! p-0!">
                <flux:command>
                    <flux:command.input wire:model.live.debounce.250ms="commandSearch" :placeholder="__('Search admin pages…')" closable />
                    <flux:command.items>
                        @php $results = $this->commandResults; @endphp

                        @if ($results['posts']->isNotEmpty() || $results['churches']->isNotEmpty() || $results['people']->isNotEmpty())
                            <div class="px-2 pt-1 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Search results') }}</div>

                            @foreach ($results['posts'] as $post)
                                <flux:command.item wire:key="cmd-post-{{ $post['id'] }}" icon="document-text" x-on:click="Livewire.navigate('{{ $post['edit_url'] }}')">
                                    {{ $post['label'] }}
                                </flux:command.item>
                            @endforeach

                            @foreach ($results['churches'] as $church)
                                <flux:command.item wire:key="cmd-church-{{ $church['id'] }}" icon="building-library" x-on:click="Livewire.navigate('{{ $church['edit_url'] }}')">
                                    {{ $church['label'] }}
                                </flux:command.item>
                            @endforeach

                            @foreach ($results['people'] as $person)
                                <flux:command.item wire:key="cmd-person-{{ $person['id'] }}" :icon="$person['is_admin'] ? 'user-group' : 'user'" x-on:click="Livewire.navigate('{{ $person['edit_url'] }}')">
                                    <span class="flex flex-1 items-center justify-between">
                                        <span>{{ $person['label'] }}</span>
                                        <span class="ms-2 text-xs text-zinc-500">{{ $person['sublabel'] }}</span>
                                    </span>
                                </flux:command.item>
                            @endforeach
                        @endif

                        <div class="px-2 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Pages') }}</div>
                        <flux:command.item icon="newspaper" x-on:click="Livewire.navigate('{{ route('posts.index') }}')">{{ __('Posts') }}</flux:command.item>
                        <flux:command.item icon="hand-raised" x-on:click="Livewire.navigate('{{ route('prayer.index') }}')">{{ __('Prayer schedule') }}</flux:command.item>
                        <flux:command.item icon="calendar" x-on:click="Livewire.navigate('{{ route('fasting.index') }}')">{{ __('Fasting calendar') }}</flux:command.item>
                        <flux:command.item icon="user" x-on:click="Livewire.navigate('{{ route('profile') }}')">{{ __('Profile') }}</flux:command.item>

                        @can('church.manage')
                            <div class="px-2 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Structure') }}</div>
                            <flux:command.item icon="globe-americas" x-on:click="Livewire.navigate('{{ route('admin.regions.index') }}')">{{ __('Ecclesiastical regions') }}</flux:command.item>
                            <flux:command.item icon="map" x-on:click="Livewire.navigate('{{ route('admin.districts.index') }}')">{{ __('Districts') }}</flux:command.item>
                            <flux:command.item icon="building-library" x-on:click="Livewire.navigate('{{ route('admin.churches.index') }}')">{{ __('Churches') }}</flux:command.item>
                            <flux:command.item icon="user-group" x-on:click="Livewire.navigate('{{ route('admin.groups.index') }}')">{{ __('Groups') }}</flux:command.item>
                            <flux:command.item icon="identification" x-on:click="Livewire.navigate('{{ route('admin.assignment-roles.index') }}')">{{ __('Assignment roles') }}</flux:command.item>
                        @endcan

                        @if (auth()->user()->can('users.manage') || auth()->user()->can('users.manage.local'))
                            <div class="px-2 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('People') }}</div>
                            <flux:command.item icon="identification" x-on:click="Livewire.navigate('{{ route('admin.people.index') }}')">{{ __('People (generic)') }}</flux:command.item>
                            <flux:command.item icon="user" x-on:click="Livewire.navigate('{{ route('admin.people.index', ['nature' => 'pastor']) }}')">{{ __('Pastors') }}</flux:command.item>
                            <flux:command.item icon="users" x-on:click="Livewire.navigate('{{ route('admin.members.index') }}')">{{ __('Members') }}</flux:command.item>
                            <flux:command.item icon="user" x-on:click="Livewire.navigate('{{ route('admin.people.index', ['nature' => 'youth']) }}')">{{ __('Youth') }}</flux:command.item>
                            <flux:command.item icon="user" x-on:click="Livewire.navigate('{{ route('admin.people.index', ['nature' => 'teenager']) }}')">{{ __('Teenagers') }}</flux:command.item>
                            <flux:command.item icon="user" x-on:click="Livewire.navigate('{{ route('admin.people.index', ['nature' => 'child']) }}')">{{ __('Children') }}</flux:command.item>
                            <flux:command.item icon="user" x-on:click="Livewire.navigate('{{ route('admin.people.index', ['nature' => 'interested']) }}')">{{ __('Interested') }}</flux:command.item>
                            <flux:command.item icon="user-plus" x-on:click="Livewire.navigate('{{ route('admin.people.index', ['nature' => 'visitor']) }}')">{{ __('Visitors') }}</flux:command.item>
                            <flux:command.item icon="user-group" x-on:click="Livewire.navigate('{{ route('admin.users.index') }}')">{{ __('Administrators') }}</flux:command.item>
                        @endif

                        @if ($hasPostMgmt)
                            <div class="px-2 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Posts management') }}</div>
                            @can('posts.create.local')
                                <flux:command.item icon="document-text" x-on:click="Livewire.navigate('{{ route('admin.posts.index') }}')">{{ __('Posts manager') }}</flux:command.item>
                            @endcan
                            @can('comments.moderate')
                                <flux:command.item icon="chat-bubble-left-right" x-on:click="Livewire.navigate('{{ route('admin.comments.index') }}')">{{ __('Moderate comments') }}</flux:command.item>
                            @endcan
                        @endif

                        @if (auth()->user()->can('prayer.schedule.manage') || auth()->user()->can('fasting.calendar.manage'))
                            <div class="px-2 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Miscellaneous') }}</div>
                            @can('prayer.schedule.manage')
                                <flux:command.item icon="megaphone" x-on:click="Livewire.navigate('{{ route('admin.prayer-campaigns.index') }}')">{{ __('Prayer campaigns') }}</flux:command.item>
                            @endcan
                            @can('fasting.calendar.manage')
                                <flux:command.item icon="calendar" x-on:click="Livewire.navigate('{{ route('admin.fasting-campaigns.index') }}')">{{ __('Fasting campaigns') }}</flux:command.item>
                            @endcan
                        @endif
                    </flux:command.items>
                </flux:command>
            </flux:modal>
        </div>
    @endauth
</div>
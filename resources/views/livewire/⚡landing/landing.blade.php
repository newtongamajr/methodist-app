<div class="min-h-screen flex flex-col bg-white dark:bg-zinc-950">
    <header class="absolute inset-x-0 top-0 z-20">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-5 lg:px-10">
            <a href="/" class="flex items-center gap-3">
                <img
                    src="{{ Vite::asset('resources/assets/metodista-logo-horizontal.png') }}"
                    alt="{{ __('Methodist Church in Brazil') }}"
                    class="h-12 w-auto drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)] sm:h-14"
                >
            </a>

            <div class="flex items-center gap-3">
                <livewire:appearance-switcher />
                <livewire:locale-switcher />

                @auth
                    <flux:button :href="route('posts.index')" size="sm" variant="primary" icon-trailing="arrow-right">
                        {{ __('Browse posts') }}
                    </flux:button>
                @else
                    <flux:button :href="route('login')" size="sm" variant="ghost" class="text-white hover:bg-white/10">
                        {{ __('Sign in') }}
                    </flux:button>
                    <flux:button :href="route('register')" size="sm" variant="primary">
                        {{ __('Join now') }}
                    </flux:button>
                @endauth
            </div>
        </div>
    </header>

    <section class="relative isolate overflow-hidden bg-linear-to-br from-methodist-red-900 via-accent to-methodist-red-700 text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(255,255,255,0.18),transparent_60%)]"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 pb-16 pt-32 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:pb-24 lg:pt-40">
            <div>
                <flux:badge color="amber" class="w-fit uppercase tracking-wider">
                    {{ __('May 2026 — National Fasting and Prayer') }}
                </flux:badge>

                <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    {{ __('Seek God together') }}
                </h1>

                <p class="mt-5 max-w-xl text-lg text-rose-50/90 sm:text-xl">
                    {{ __('A movement of dedication and intercession across every region of the Methodist Church in Brazil — REMA, REMNE, and the eight ecclesiastical regions.') }}
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-3">
                    <flux:button :href="route('register')" variant="primary" icon-trailing="arrow-right" class="bg-white! text-accent! hover:bg-rose-50!">
                        {{ __('Join the campaign') }}
                    </flux:button>
                    <flux:button :href="route('posts.index')" variant="ghost" icon="newspaper" class="text-white hover:bg-white/10">
                        {{ __('Browse posts') }}
                    </flux:button>
                    <flux:button :href="route('login')" variant="ghost" class="text-white hover:bg-white/10">
                        {{ __('Already a member? Sign in') }}
                    </flux:button>
                </div>
            </div>

            <div class="relative">
                <div class="overflow-hidden rounded-2xl bg-white/5 ring-1 ring-white/15 shadow-2xl">
                    <img
                        src="{{ Vite::asset('resources/assets/2026.04-jejum-oracao-frontpage.jpeg') }}"
                        alt="{{ __('Fasting and Prayer') }}"
                        class="h-auto w-full"
                        fetchpriority="high"
                    >
                </div>
            </div>
        </div>
    </section>

    <section class="bg-methodist-cream py-20 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-6 lg:px-10">
            <flux:heading size="xl" class="text-center text-accent! dark:text-rose-300!">
                {{ __('What you can do') }}
            </flux:heading>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <flux:card>
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-accent/10 text-accent dark:bg-rose-500/15 dark:text-rose-300">
                            <flux:icon.clock class="size-5" />
                        </div>
                        <flux:heading>{{ __('Reserve a prayer schedule') }}</flux:heading>
                    </div>
                    <flux:text class="mt-3">
                        {{ __('Reserve 30-minute or 1-hour windows during the campaign and pray with brothers and sisters from your church — at the temple or from home.') }}
                    </flux:text>
                </flux:card>

                <flux:card>
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-methodist-blue/10 text-methodist-blue dark:bg-sky-500/15 dark:text-sky-300">
                            <flux:icon.calendar class="size-5" />
                        </div>
                        <flux:heading>{{ __('Log your fasting') }}</flux:heading>
                    </div>
                    <flux:text class="mt-3">
                        {{ __("Track 24-hour, 12-hour, or single-meal fasts; choose the foods or habits you'll set apart for this season.") }}
                    </flux:text>
                </flux:card>

                <flux:card>
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                            <flux:icon.chat-bubble-left-right class="size-5" />
                        </div>
                        <flux:heading>{{ __('Encourage one another') }}</flux:heading>
                    </div>
                    <flux:text class="mt-3">
                        {{ __('Read pastoral messages, watch the daily devotional videos, and react with comments and likes after sign-in.') }}
                    </flux:text>
                </flux:card>
            </div>
        </div>
    </section>

    <section class="bg-white py-20 dark:bg-zinc-950">
        <div class="mx-auto max-w-7xl px-6 lg:px-10">
            <flux:heading size="xl" class="text-center">{{ __('How it works') }}</flux:heading>

            <ol class="mx-auto mt-12 grid max-w-5xl gap-8 md:grid-cols-3">
                @php
                    $steps = [
                        ['title' => __('Pick your church'), 'body' => __("Choose the local Methodist congregation you belong to or the one that's closest to you."), 'color' => 'var(--color-accent)'],
                        ['title' => __('Choose your moment'), 'body' => __("Sign up for the prayer schedules that fit your day. Each church has a coverage goal — your hour might be the one that's missing."), 'color' => 'var(--color-methodist-blue)'],
                        ['title' => __('Walk together'), 'body' => __('Add your fasting plan, share testimonies, and be edified by what others are sharing.'), 'color' => '#d97706'],
                    ];
                @endphp
                @foreach ($steps as $i => $step)
                    <li class="relative">
                        <div class="flex size-10 items-center justify-center rounded-full text-base font-semibold text-white"
                             style="background-color: {{ $step['color'] }};">
                            {{ $i + 1 }}
                        </div>
                        <flux:heading class="mt-4">{{ $step['title'] }}</flux:heading>
                        <flux:text class="mt-2">{{ $step['body'] }}</flux:text>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="bg-methodist-blue py-14 text-white">
        <div class="mx-auto max-w-7xl px-6 text-center lg:px-10">
            <flux:heading size="lg" class="text-white!">
                {{ __('All Brazilian Methodist regions are participating') }}
            </flux:heading>
            <p class="mx-auto mt-3 max-w-3xl text-sky-100">
                {{ __('Eight ecclesiastical regions, plus the Amazon (REMA) and Northeast (REMNE) missionary regions.') }}
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                @foreach (['RE1','RE2','RE3','RE4','RE5','RE6','RE7','RE8','REMA','REMNE'] as $code)
                    <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-sm font-semibold ring-1 ring-white/25">
                        {{ $code }}
                    </span>
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <flux:button :href="route('register')" variant="primary" class="bg-white! text-methodist-blue! hover:bg-sky-50!">
                    {{ __('Join the campaign') }}
                </flux:button>
                <flux:button :href="route('posts.index')" variant="ghost" icon="newspaper" class="text-white hover:bg-white/10">
                    {{ __('Browse posts') }}
                </flux:button>
            </div>
        </div>
    </section>

    <footer class="bg-zinc-950 py-10 text-center text-sm text-zinc-400">
        <p>© {{ now()->year }} {{ __('Methodist Church in Brazil') }} — {{ config('app.name') }}</p>

        <a
            href="https://galileosoft.com.br"
            target="_blank"
            rel="noopener"
            class="mt-5 inline-flex items-center gap-2 text-zinc-300 transition hover:text-white"
        >
            <span>{{ __('Developed by') }}</span>
            <img
                src="{{ Vite::asset('resources/assets/galileosoft-logo-horizontal-white.png') }}"
                alt="GalileoSoft"
                class="h-5 w-auto"
            >
        </a>
    </footer>
</div>
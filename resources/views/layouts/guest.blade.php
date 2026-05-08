<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <link rel="icon" type="image/png" href="{{ Vite::asset('resources/assets/metodista-favicon-v2.png') }}">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/assets/metodista-favicon-v2.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    <script>
        (function (a) {
            var d = document.documentElement;
            if (a === 'dark') { d.classList.add('dark'); }
            else if (a === 'light') { d.classList.remove('dark'); }
            else if (window.matchMedia('(prefers-color-scheme: dark)').matches) { d.classList.add('dark'); }
            else { d.classList.remove('dark'); }
        })(@json($appearance ?? 'system'));
    </script>
</head>
<body class="font-sans antialiased text-zinc-900 dark:text-zinc-100">
    <div class="relative min-h-screen overflow-hidden bg-linear-to-br from-methodist-red-900 via-accent to-methodist-red-700 text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(255,255,255,0.18),transparent_60%)]"></div>

        <header class="relative z-20">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-5 lg:px-10">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img
                        src="{{ Vite::asset('resources/assets/metodista-logo-horizontal.png') }}"
                        alt="{{ __('Methodist Church in Brazil') }}"
                        class="h-10 w-auto drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)] sm:h-12"
                    >
                </a>

                <div class="flex items-center gap-3">
                    <livewire:appearance-switcher />
                    <livewire:locale-switcher />
                </div>
            </div>
        </header>

        <main class="relative z-10 flex min-h-[calc(100vh-200px)] items-start justify-center px-6 pb-20 pt-6">
            <div class="w-full max-w-md rounded-2xl bg-white/95 p-8 shadow-2xl ring-1 ring-white/15 backdrop-blur-sm dark:bg-zinc-900/95 dark:ring-white/10">
                <div class="text-zinc-900 dark:text-zinc-100">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <footer class="relative z-10 pb-8 text-center text-sm text-rose-100/70">
            <p>© {{ now()->year }} {{ __('Methodist Church in Brazil') }} — {{ config('app.name') }}</p>
            <a
                href="https://galileosoft.com.br"
                target="_blank"
                rel="noopener"
                class="mt-3 inline-flex items-center gap-2 transition hover:text-white"
            >
                <span>{{ __('Developed by') }}</span>
                <img
                    src="{{ Vite::asset('resources/assets/galileosoft-logo-horizontal-white.png') }}"
                    alt="GalileoSoft"
                    class="h-4 w-auto"
                >
            </a>
        </footer>
    </div>

    @fluxScripts
</body>
</html>
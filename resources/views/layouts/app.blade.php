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
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-950">
        <livewire:layout.navigation />

        @if (isset($header))
            <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @fluxScripts
</body>
</html>
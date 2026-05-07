<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} — {{ __('Methodist Church in Brazil') }}</title>
    <meta name="description" content="{{ __('A national fasting and prayer campaign of the Methodist Church in Brazil. Join believers across all eight regional conferences in seeking God together.') }}">

    <link rel="icon" type="image/png" href="{{ Vite::asset('resources/assets/metodista-favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/assets/metodista-favicon.png') }}">

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
<body class="font-sans antialiased text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-950">
    {{ $slot }}

    @fluxScripts
</body>
</html>
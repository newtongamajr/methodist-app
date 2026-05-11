{{--
    Shared "Developed by GalileoSoft" footer credit. Picks the right logo
    variant per theme (dark logo on light surfaces, white logo on dark
    surfaces) so a single component works across the app + landing layouts.
    The guest layout uses a tinted rose-on-rose variant inline because
    its surface is the deep brand gradient — it doesn't include this one.
--}}
<footer class="border-t border-zinc-200 bg-white py-6 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
    <div class="mx-auto flex max-w-7xl flex-col items-center gap-2 px-4 sm:px-6 lg:px-8">
        <p>© {{ now()->year }} {{ __('Methodist Church in Brazil') }} — {{ config('app.name') }}</p>
        <a
            href="https://galileosoft.com.br"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 transition hover:text-zinc-900 dark:hover:text-white"
        >
            <span>{{ __('Developed by') }}</span>
            <img
                src="{{ Vite::asset('resources/assets/galileosoft-logo-horizontal.png') }}"
                alt="GalileoSoft"
                class="h-4 w-auto dark:hidden"
            >
            <img
                src="{{ Vite::asset('resources/assets/galileosoft-logo-horizontal-white.png') }}"
                alt="GalileoSoft"
                class="hidden h-4 w-auto dark:inline"
            >
        </a>
    </div>
</footer>

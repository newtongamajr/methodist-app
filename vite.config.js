import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/assets/2026.04-jejum-oracao-frontpage.jpeg',
                'resources/assets/metodista-logo.png',
                'resources/assets/metodista-logo-horizontal.png',
                'resources/assets/metodista-symbol.png',
                'resources/assets/metodista-favicon.png',
                'resources/assets/galileosoft-logo-horizontal.png',
                'resources/assets/galileosoft-logo-horizontal-white.png',
            ],
            refresh: true,
        }),
    ],
});
# Project Assets

Source assets (campaign images, logos, etc.) live here under `resources/assets/`.
They're not served directly — Vite imports them from Blade/JS and emits hashed
copies into `public/build/` at build time.

## Cover image for the landing page

The campaign cover is checked in at:

```
resources/assets/2026.04-jejum-oracao-frontpage.jpeg
```

It's referenced from the landing page via Vite (`@vite(...)` or
`Vite::asset('resources/assets/2026.04-jejum-oracao-frontpage.jpeg')`).

To swap it, drop the new file in this folder and update the reference in
`resources/views/livewire/pages/landing.blade.php`.

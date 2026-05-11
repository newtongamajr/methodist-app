/**
 * Flux's @fluxAppearance defines window.Flux.applyAppearance, but Flux Pro's
 * runtime later replaces window.Flux entirely, dropping that method. Expose
 * our own helper so the appearance switcher always has something to call.
 */

const html = () => document.documentElement;

function applyAppearance(appearance) {
    if (appearance === 'system') {
        window.localStorage.removeItem('flux.appearance');
        const matchesDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        matchesDark ? html().classList.add('dark') : html().classList.remove('dark');
        return;
    }

    if (appearance === 'dark') {
        window.localStorage.setItem('flux.appearance', 'dark');
        html().classList.add('dark');
        return;
    }

    if (appearance === 'light') {
        window.localStorage.setItem('flux.appearance', 'light');
        html().classList.remove('dark');
    }
}

window.applyAppearance = applyAppearance;

/**
 * The SetAppearance middleware writes the resolved appearance into a
 * <meta name="x-appearance"> tag on every request. Livewire's wire:navigate
 * doesn't re-execute inline <head> scripts, so without this hook the
 * <html class="dark"> state would drift across SPA transitions whenever
 * localStorage or Flux Pro's runtime got out of sync with the server.
 * Re-reading the meta on every livewire:navigated keeps the server
 * authoritative.
 */
function applyFromMeta() {
    const meta = document.querySelector('meta[name="x-appearance"]');
    if (meta && meta.content) {
        applyAppearance(meta.content);
    }
}

document.addEventListener('livewire:navigated', applyFromMeta);
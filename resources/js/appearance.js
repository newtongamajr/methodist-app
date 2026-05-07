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
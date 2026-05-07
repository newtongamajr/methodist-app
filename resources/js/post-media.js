import Plyr from 'plyr';
import 'plyr/dist/plyr.css';

const PLYR_DEFAULTS = {
    controls: ['play-large', 'play', 'progress', 'current-time', 'duration', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'],
    youtube: { noCookie: true, rel: 0, modestbranding: 1 },
};

function initPlyrPlayers() {
    document.querySelectorAll('[data-plyr]:not([data-plyr-ready])').forEach((el) => {
        new Plyr(el, PLYR_DEFAULTS);
        el.setAttribute('data-plyr-ready', '1');
    });
}

function destroyPlyrPlayers() {
    // Plyr binds itself to the element; on Livewire navigation, the old DOM is replaced
    // and Plyr's listeners get garbage-collected with their nodes. No explicit teardown needed.
}

document.addEventListener('DOMContentLoaded', initPlyrPlayers);
document.addEventListener('livewire:navigated', () => {
    destroyPlyrPlayers();
    initPlyrPlayers();
});
document.addEventListener('livewire:initialized', initPlyrPlayers);
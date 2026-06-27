import AnimateScrollController from './js/animate-scroll.js';
import ConfettiController from './js/confetti.js';
import MenuController from './js/menu.js';
import SliderController from './js/slider.js';

export function register(c975lSite) {
    c975lSite.register('animateScroll', AnimateScrollController);
    c975lSite.register('confetti', ConfettiController);
    c975lSite.register('menu', MenuController);
    c975lSite.register('slider', SliderController);
}
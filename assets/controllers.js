import AnimateScrollController from './js/animate-scroll.js';
import BlockController from './js/block.js';
import ConfettiController from './js/confetti.js';
import MenuController from './js/menu.js';
import SliderController from './js/slider.js';
import './js/blocks.js';

export function register(c975lSite) {
    c975lSite.register('animateScroll', AnimateScrollController);
    c975lSite.register('block', BlockController);
    c975lSite.register('confetti', ConfettiController);
    c975lSite.register('menu', MenuController);
    c975lSite.register('slider', SliderController);
}
// @TODO : convert blocks.js to a Stimulus controller
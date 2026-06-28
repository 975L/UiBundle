import AnimateScrollController from './js/animate-scroll.js';
import BlockController from './js/block.js';
import ConfettiController from './js/confetti.js';
import EaSortableController from './js/ea-sortable.js';
import MenuController from './js/menu.js';
import SliderController from './js/slider.js';
import './js/trix-editor.js';

export function register(c975lUi) {
    c975lUi.register('animateScroll', AnimateScrollController);
    c975lUi.register('block', BlockController);
    c975lUi.register('confetti', ConfettiController);
    c975lUi.register('eaSortable', EaSortableController);
    c975lUi.register('menu', MenuController);
    c975lUi.register('slider', SliderController);
}
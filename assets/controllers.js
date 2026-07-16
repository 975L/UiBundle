import { startStimulusApp } from '@symfony/stimulus-bundle';
import AnimateScrollController from './js/animate-scroll.js';
import ConfettiController from './js/confetti.js';
import ImageCompareController from './js/image-compare.js';
import MenuController from './js/menu.js';
import SliderController from './js/slider.js';
import VideoIframeController from './js/video-iframe.js';

// Front-end controllers, used on public pages
// Loaded as its own <script type="module"> tag (see importmap.php), starts its own Stimulus app
const app = startStimulusApp();
app.register('animateScroll', AnimateScrollController);
app.register('confetti', ConfettiController);
app.register('imageCompare', ImageCompareController);
app.register('menu', MenuController);
app.register('slider', SliderController);
app.register('videoIframe', VideoIframeController);

import { startStimulusApp } from '@symfony/stimulus-bundle';
import BlockController from './js/block.js';
import EaSortableController from './js/ea-sortable.js';
import './js/trix-editor.js';

// Back-office controllers, used only in EasyAdmin
// Loaded as its own <script type="module"> tag (see importmap.php), starts its own Stimulus app
const app = startStimulusApp();
app.register('block', BlockController);
app.register('eaSortable', EaSortableController);

// Mount eaSortable on <body> automatically: EasyAdmin's layout never sets data-controller
// itself, so without this the collection drag-and-drop silently never connects.
document.body.setAttribute(
    'data-controller',
    [document.body.dataset.controller, 'eaSortable'].filter(Boolean).join(' ')
);

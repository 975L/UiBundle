import { startStimulusApp } from '@symfony/stimulus-bundle';
import BlockController from './js/block.js';
import BlockCollectionController from './js/block-collection.js';
import BlockDuplicateController from './js/block-duplicate.js';
import EaSortableController from './js/ea-sortable.js';
import './js/trix-editor.js';
import './js/media-preview.js';

// Back-office controllers, used only in EasyAdmin
// Loaded as its own <script type="module"> tag (see importmap.php), starts its own Stimulus app
const app = startStimulusApp();
app.register('block', BlockController);
app.register('blockCollection', BlockCollectionController);
app.register('blockDuplicate', BlockDuplicateController);
app.register('eaSortable', EaSortableController);

// Mount eaSortable, blockCollection and blockDuplicate on <body> automatically: EasyAdmin's layout
// never sets data-controller itself, so without this none of the drag-and-drop, new-block
// scroll/focus, or duplicate-block behaviors would ever connect.
document.body.setAttribute(
    'data-controller',
    [document.body.dataset.controller, 'eaSortable', 'blockCollection', 'blockDuplicate'].filter(Boolean).join(' ')
);

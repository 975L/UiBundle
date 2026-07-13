import { startStimulusApp } from '@symfony/stimulus-bundle';
import BlockController from './js/block.js';
import BlockCollectionController from './js/block-collection.js';
import BlockDuplicateController from './js/block-duplicate.js';
import BlockFocusController from './js/block-focus.js';
import EaSortableController from './js/ea-sortable.js';
import GalleryPreviewController from './js/gallery-preview.js';
import './js/trix-editor.js';
import './js/media-preview.js';
import './js/icon-picker.js';

// Back-office controllers, used only in EasyAdmin
// Loaded as its own <script type="module"> tag (see importmap.php), starts its own Stimulus app
const app = startStimulusApp();
app.register('block', BlockController);
app.register('blockCollection', BlockCollectionController);
app.register('blockDuplicate', BlockDuplicateController);
app.register('blockFocus', BlockFocusController);
app.register('eaSortable', EaSortableController);
app.register('galleryPreview', GalleryPreviewController);

// Mount eaSortable, blockCollection, blockDuplicate and blockFocus on <body> automatically:
// EasyAdmin's layout never sets data-controller itself, so without this none of the drag-and-drop,
// new-block scroll/focus, duplicate-block or used-in-media-library scroll/focus behaviors would
// ever connect.
document.body.setAttribute(
    'data-controller',
    [document.body.dataset.controller, 'eaSortable', 'blockCollection', 'blockDuplicate', 'blockFocus'].filter(Boolean).join(' ')
);

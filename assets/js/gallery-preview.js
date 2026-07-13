/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import { Controller } from "@hotwired/stimulus";

// Mounted on each block gallery preview <iframe> (see templates/management/block_gallery.html.twig).
// Each preview is a fully isolated document (its own front-end stylesheets + the rendered block) so
// the site's CSS never bleeds into EasyAdmin's own chrome - this resizes the iframe to fit its actual
// content height, so an alert and a slider don't share one arbitrary fixed height (either clipping the
// taller one or leaving dead space under the shorter one).
//
// Uses a ResizeObserver instead of a one-shot measurement on the iframe's own "load" event: a
// "loading=lazy" image (see Image.html.twig) doesn't block that event, so the very first measurement
// would run before it (or a slider's several images) had actually rendered, leaving the iframe sized
// too short - the content then grows moments later and overflows into a scrollbar. Watching the
// document's own box keeps the height correct through any such later layout change, not just the first one.
export default class extends Controller {
    connect() {
        this.element.addEventListener('load', () => this.observe());
    }

    observe() {
        const doc = this.element.contentDocument;
        if (!doc) return;

        const resize = () => {
            this.element.style.height = doc.documentElement.scrollHeight + 'px';
        };

        resize();
        new ResizeObserver(resize).observe(doc.documentElement);
    }
}

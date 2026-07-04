/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { kindUrl: String };

    loadData(event) {
        const kind     = event.target.value;
        const prefix   = this.element.name.replace(/\[kind\]$/, '');
        const idPrefix = this.element.id.replace(/_kind$/, '');
        const kindRow  = this.element.closest('[data-kind-row]');
        const compound = kindRow && kindRow.parentElement;
        if (!compound) return;

        let container = compound.querySelector('.block-data-form');
        if (!container) {
            container = document.createElement('div');
            container.className = 'block-data-form';
            compound.appendChild(container);
        }

        if (!kind) {
            container.innerHTML = '';
            return;
        }

        fetch(this.kindUrlValue + '?k=' + encodeURIComponent(kind))
            .then(r => r.text())
            .then(html => {
                container.innerHTML = html
                    .replaceAll('_block_[', prefix + '[')
                    // Scoped to attribute-value/anchor starts (id="block_…", for="block_…", href="#block_…")
                    // rather than a blind replace, so it can't corrupt an unrelated class or text containing "block_".
                    .replace(/(["'#])block_/g, `$1${idPrefix}_`);

                // The outer <form>'s enctype is fixed by Symfony at initial render, based on which fields
                // existed server-side at that time. A newly picked kind can inject a file input here that
                // didn't exist yet then, so the form would still submit as urlencoded and silently drop it.
                if (container.querySelector('input[type="file"]')) {
                    const form = this.element.closest('form');
                    if (form) form.enctype = 'multipart/form-data';
                }

                document.dispatchEvent(new CustomEvent('c975l:block-data-loaded'));
                // Wires up EasyAdmin's "add" button for the freshly-injected medias collection —
                // its field-collection.js only scans for unprocessed collections on this event / DOMContentLoaded
                document.dispatchEvent(new CustomEvent('ea.collection.item-added'));
            });
    }
}

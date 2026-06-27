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
                container.innerHTML = html.replaceAll('_block_[', prefix + '[');
                document.dispatchEvent(new CustomEvent('ea.collection.item-added', { detail: {} }));
            });
    }
}

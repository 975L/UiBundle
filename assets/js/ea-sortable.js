/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import { Controller } from "@hotwired/stimulus";
import { addToolbarButton } from "./block-toolbar.js";

// Mounted automatically on <body> by controllers-admin.js — no layout override needed.

// No width/height here, deliberately - EasyAdmin's own icons (e.g. the delete button's) don't set
// them either, relying entirely on its global ".icon svg" CSS to size every icon consistently.
// Hard-coding a size here would make this one the odd one out instead of matching the others.
const UI_MOVE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
    + 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
    + '<polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/>'
    + '<polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/>'
    + '<line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/>'
    + '</svg>';

export default class extends Controller {
    connect() {
        this.element.querySelectorAll('[data-ea-collection-field]').forEach(field => this.initField(field));

        this.boundOnItemAdded = this.onItemAdded.bind(this);
        document.addEventListener('ea.collection.item-added', this.boundOnItemAdded);
    }

    disconnect() {
        document.removeEventListener('ea.collection.item-added', this.boundOnItemAdded);
    }

    initField(field) {
        if (field.dataset.uiSortable || !this.isSortable(field)) return;
        field.dataset.uiSortable = '1';

        const container = this.itemsContainer(field);
        if (!container) return;

        field.querySelectorAll('.field-collection-item').forEach(item => this.addHandle(item));

        let dragging = null;

        container.addEventListener('dragstart', e => {
            const item = e.target.closest('.field-collection-item');
            if (!item) { e.preventDefault(); return; }
            if (item.closest('[data-ea-collection-field]') !== field) return;
            dragging = item;
            requestAnimationFrame(() => {
                item.classList.add('ui-dragging');
                item.style.opacity = '0.4';
                item.style.boxShadow = '0 0 0 2px var(--bs-primary,#0d6efd)';
            });
        });

        container.addEventListener('dragend', () => {
            if (!dragging) return;
            dragging.classList.remove('ui-dragging');
            dragging.style.opacity = '';
            dragging.style.boxShadow = '';
            dragging.removeAttribute('draggable');
            this.updatePositions(field);
            dragging = null;
        });

        container.addEventListener('dragover', e => {
            e.preventDefault();
            if (!dragging) return;
            const after = this.dragAfter(field, e.clientY);
            if (!after) dragging.parentElement.appendChild(dragging);
            else after.parentElement.insertBefore(dragging, after);
        });
    }

    isSortable(field) {
        return (field.dataset.prototype || '').includes('[position]')
            || !!field.querySelector('[name$="[position]"]');
    }

    itemsContainer(field) {
        return field.querySelector('.ea-form-collection-items') || field.querySelector('.form-widget-compound');
    }

    addHandle(item) {
        if (item.dataset.uiHandle) return;
        item.dataset.uiHandle = '1';

        const btn = addToolbarButton(item, {
            title: 'Déplacer',
            icon: UI_MOVE_ICON,
            order: 1,
            onClick: () => {},
        });
        if (!btn) return;

        btn.classList.add('ui-sort-handle');
        btn.style.cursor = 'grab';

        const startDrag = () => item.setAttribute('draggable', 'true');
        const endDrag = () => item.removeAttribute('draggable');
        btn.addEventListener('mousedown', startDrag);
        btn.addEventListener('mouseup', endDrag);

        // Extended to the whole header bar so grabbing isn't limited to this small icon. Only
        // the toolbar itself (duplicate, delete, this handle) is excluded - EasyAdmin's own
        // title/toggle button covers most of the bar's width, and it must stay draggable too,
        // it just keeps toggling normally on a plain click since only an actual drag gesture
        // (pointer movement) hijacks it instead of a click.
        const header = item.querySelector('.accordion-header');
        if (header) {
            header.style.cursor = 'grab';
            header.addEventListener('mousedown', e => {
                if (!e.target.closest('.ui-row-toolbar')) startDrag();
            });
            header.addEventListener('mouseup', endDrag);
        }
    }

    dragAfter(field, y) {
        const items = [...field.querySelectorAll('.field-collection-item:not(.ui-dragging)')]
            .filter(item => item.closest('[data-ea-collection-field]') === field);
        return items.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) return { offset, element: child };
            return closest;
        }, { offset: -Infinity }).element;
    }

    updatePositions(field) {
        [...field.querySelectorAll('.field-collection-item')]
            .filter(item => item.closest('[data-ea-collection-field]') === field)
            .forEach((item, i) => {
                const pos = item.querySelector('[name$="[position]"]');
                if (pos) pos.value = i;
            });
    }

    onItemAdded(e) {
        const newElement = e.detail && e.detail.newElement;
        if (newElement) {
            this.addHandle(newElement);
            const field = newElement.closest('[data-ea-collection-field]');
            if (field) {
                const pos = newElement.querySelector('[name$="[position]"]');
                if (pos) {
                    const siblings = [...field.querySelectorAll('.field-collection-item')]
                        .filter(item => item.closest('[data-ea-collection-field]') === field);
                    pos.value = siblings.length - 1;
                }
            }
        }
        this.element.querySelectorAll('[data-ea-collection-field]').forEach(field => this.initField(field));
    }
}

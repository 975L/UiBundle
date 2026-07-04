/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import { Controller } from "@hotwired/stimulus";

// Mounted automatically on <body> by controllers-admin.js — no layout override needed.

const UI_GRIP = '<svg width="10" height="16" fill="currentColor" viewBox="0 0 10 16">'
    + '<circle cx="3" cy="3" r="1.5"/><circle cx="7" cy="3" r="1.5"/>'
    + '<circle cx="3" cy="8" r="1.5"/><circle cx="7" cy="8" r="1.5"/>'
    + '<circle cx="3" cy="13" r="1.5"/><circle cx="7" cy="13" r="1.5"/>'
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
        const header = item.querySelector('.accordion-header');
        if (!header) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-link p-2 ui-sort-handle';
        btn.title = 'Déplacer';
        btn.style.cssText = 'cursor:grab;flex-shrink:0;color:var(--bs-secondary-color,#6c757d);line-height:1;border:none;background:none';
        btn.innerHTML = UI_GRIP;
        btn.addEventListener('mousedown', () => item.setAttribute('draggable', 'true'));
        btn.addEventListener('mouseup', () => item.removeAttribute('draggable'));
        header.prepend(btn);
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

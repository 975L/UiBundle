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

// No width/height here, deliberately - EasyAdmin's own icons (e.g. the delete button's) don't set them either, relying entirely on its global ".icon svg" CSS to size every icon consistently. Hard-coding a size here would make this one the odd one out instead of matching the others.
const UI_MOVE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
    + 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
    + '<polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/>'
    + '<polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/>'
    + '<line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/>'
    + '</svg>';

export default class extends Controller {
    connect() {
        // Shared across every field's own listeners (not a per-field closure var) so a drag started in
        // one field can be recognized by another field's own dragover/dragend - needed for the
        // cross-collection Block move below (see moveAcrossFields())
        this.dragging = null;
        this.dragOriginField = null;
        this.dragOriginContainer = null;
        this.dragOriginNextSibling = null;

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

        field.querySelectorAll('.field-collection-item').forEach(item => {
            this.addHandle(item);
            this.applyRestriction(item);
        });

        container.addEventListener('dragstart', e => {
            const item = e.target.closest('.field-collection-item');
            if (!item) { e.preventDefault(); return; }
            if (item.closest('[data-ea-collection-field]') !== field) return;

            this.dragging = item;
            this.dragOriginField = field;
            this.dragOriginContainer = item.parentElement;
            this.dragOriginNextSibling = item.nextElementSibling;
            requestAnimationFrame(() => {
                item.classList.add('ui-dragging');
                item.style.opacity = '0.4';
                item.style.boxShadow = '0 0 0 2px var(--bs-primary,#0d6efd)';
            });

            if (this.isBlockCollectionField(field)) this.highlightDropTargets(field);
        });

        container.addEventListener('dragend', () => {
            if (!this.dragging) return;
            const item = this.dragging;
            const originField = this.dragOriginField;
            const originContainer = this.dragOriginContainer;
            const originNextSibling = this.dragOriginNextSibling;

            item.classList.remove('ui-dragging');
            item.style.opacity = '';
            item.style.boxShadow = '';
            item.removeAttribute('draggable');

            const finalField = item.closest('[data-ea-collection-field]');
            if (finalField === originField) {
                this.updatePositions(finalField);
            } else {
                this.moveAcrossFields(item, finalField, originContainer, originNextSibling);
            }

            this.dragging = null;
            this.dragOriginField = null;
            this.dragOriginContainer = null;
            this.dragOriginNextSibling = null;
            this.clearDropTargetHighlights();
        });

        container.addEventListener('dragover', e => {
            if (!this.dragging) return;

            // Reordering within the same field always works (unchanged from before); moving into a
            // *different* field is only ever offered between two fields both marked as a Block
            // collection (the page/menu's own top-level "blocks", or a container's own "slots") - every
            // other sortable collection in this bundle (medias, form fields, email blocks...) keeps its
            // original single-field-only behaviour untouched.
            const sameField = field === this.dragOriginField;
            if (!sameField && !(this.isBlockCollectionField(field) && this.isBlockCollectionField(this.dragOriginField))) {
                return;
            }

            // A container's own "slots" field is nested inside the page/menu's top-level "blocks" field
            // in the DOM (the container is itself one of its items) - without this, a dragover fired while
            // hovering the inner (slots) field would also bubble up and re-trigger the outer (blocks)
            // field's own listener right after, which would reparent the dragged row straight back out to
            // top-level on every single mouse move. Stopping it here lets only the innermost matching
            // field (the one the event actually targets) act.
            e.stopPropagation();
            e.preventDefault();
            const after = this.dragAfter(field, e.clientY);
            if (!after) container.appendChild(this.dragging);
            else after.parentElement.insertBefore(this.dragging, after);
        });
    }

    isSortable(field) {
        return (field.dataset.prototype || '').includes('[position]')
            || !!field.querySelector('[name$="[position]"]');
    }

    isBlockCollectionField(field) {
        return !!(field && field.dataset.blockCollection === '1');
    }

    // A container's own "slots" often start out empty (nothing rendered yet but EasyAdmin's own
    // "no items"/add-button placeholder, see empty_collection in EasyAdmin's form theme) - with no row
    // of its own, that empty items area has no visible size to aim for. Highlighting every OTHER eligible
    // Block-collection field's own items area the moment a compatible drag starts (sass/management/
    // _block-collection.scss gives it a dashed-border "drop zone" look) makes it obvious, empty or not.
    highlightDropTargets(originField) {
        this.element.querySelectorAll('[data-ea-collection-field]').forEach(field => {
            if (field === originField || !this.isBlockCollectionField(field)) return;
            const container = this.itemsContainer(field);
            if (container) container.classList.add('ui-drop-target');
        });
    }

    clearDropTargetHighlights() {
        this.element.querySelectorAll('.ui-drop-target').forEach(el => el.classList.remove('ui-drop-target'));
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

        // Extended to the whole header bar so grabbing isn't limited to this small icon. Only the toolbar itself (duplicate, delete, this handle) is excluded - EasyAdmin's own title/toggle button covers most of the bar's width, and it must stay draggable too, it just keeps toggling normally on a plain click since only an actual drag gesture (pointer movement) hijacks it instead of a click.
        const header = item.querySelector('.accordion-header');
        if (header) {
            header.style.cursor = 'grab';
            header.addEventListener('mousedown', e => {
                if (!e.target.closest('.ui-row-toolbar')) startDrag();
            });
            header.addEventListener('mouseup', endDrag);
        }
    }

    // Hides the (native EasyAdmin) delete button on a row carrying a checked ".ui-field-restricted" marker (see FormFieldType) - reorder stays available (the move handle is untouched), only removal is blocked. Purely a UX guard: the real enforcement is server-side, via the "restricted"/"type" fields both being disabled (see FormFieldType) and CollectionReconciler's caller skipping restricted entries on removal (see ContactFormCrudController).
    applyRestriction(item) {
        const marker = item.querySelector('.ui-field-restricted');
        if (!marker || !marker.checked) return;

        const deleteButton = item.querySelector('.field-collection-delete-button');
        if (deleteButton) deleteButton.style.display = 'none';
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

    // Fires only when a row was dropped into a *different* Block-collection field than it started in
    // (see the dragover guard above) - persists the move immediately server-side (see
    // BlockMoveController/Readme for why this can't just be a renamed form field like an ordinary
    // same-field reorder: a Block dragged across collections keeps its own database id, a plain form
    // resubmit would instead delete the original and create an empty new one, losing any attached media).
    // "ownerType"/"ownerId"/the CSRF token are carried by the outermost Block-collection field on the
    // page (the page/menu's own top-level "blocks") - read via the nearest [data-block-owner-type]
    // ancestor-or-self, since a container's own "slots" field (nested inside it) doesn't repeat them.
    moveAcrossFields(item, finalField, originContainer, originNextSibling) {
        const root = finalField.closest('[data-block-owner-type]');
        const blockIdInput = item.querySelector('[name$="[id]"]');
        const blockId = blockIdInput ? blockIdInput.value : '';

        // A block that isn't saved yet (still being drafted in this same open form) has no id to
        // relocate against - stays out of this mechanism, same as a container with no id (see
        // BlockType::addSlotsSubForm(), which then never marks that field as a Block collection at all)
        if (!blockId || !root) {
            this.revertToOrigin(item, originContainer, originNextSibling);
            return;
        }

        const body = new URLSearchParams({
            blockId,
            ownerType: root.dataset.blockOwnerType || '',
            ownerId: root.dataset.blockOwnerId || '',
            targetBlockId: finalField.dataset.blockContainerId || '',
        });

        fetch(root.dataset.blockMoveUrl, {
            method: 'POST',
            headers: { 'X-CSRF-Token': root.dataset.blockMoveCsrfToken || '' },
            body,
        }).then(response => {
            if (response.ok) {
                // Reloads rather than leaving the moved row where it was dropped: the rest of this same
                // edit form was built against the pre-move entity graph (fixed indices) - saving it as-is
                // afterward could misalign against the now-changed collection it left behind. The
                // success flash set by BlockMoveController survives the reload like it would a redirect.
                window.location.reload();
            } else {
                this.revertToOrigin(item, originContainer, originNextSibling);
                window.alert(root.dataset.blockMoveFailedLabel || '');
            }
        }).catch(() => {
            this.revertToOrigin(item, originContainer, originNextSibling);
            window.alert(root.dataset.blockMoveFailedLabel || '');
        });
    }

    revertToOrigin(item, originContainer, originNextSibling) {
        if (originNextSibling && originNextSibling.parentElement === originContainer) {
            originContainer.insertBefore(item, originNextSibling);
        } else {
            originContainer.appendChild(item);
        }
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

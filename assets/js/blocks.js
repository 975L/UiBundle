// ===== Trix editor: convert textarea[data-trix] to Trix rich-text editors =====

function initTrixEditors(root) {
    (root || document).querySelectorAll('textarea[data-trix]:not([data-trix-init])').forEach(function (textarea) {
        if (!textarea.id) return;
        textarea.dataset.trixInit = '1';
        textarea.classList.add('ea-text-editor-content', 'd-none');

        var wrapper = document.createElement('div');
        wrapper.className = 'ea-text-editor-wrapper';
        var editor = document.createElement('trix-editor');
        editor.setAttribute('input', textarea.id);
        editor.className = 'trix-content';
        wrapper.appendChild(editor);
        textarea.insertAdjacentElement('afterend', wrapper);
    });
}

// ===== Sortable drag-and-drop (shared with sortable.js) =====

const UI_GRIP = '<svg width="10" height="16" fill="currentColor" viewBox="0 0 10 16">'
    + '<circle cx="3" cy="3" r="1.5"/><circle cx="7" cy="3" r="1.5"/>'
    + '<circle cx="3" cy="8" r="1.5"/><circle cx="7" cy="8" r="1.5"/>'
    + '<circle cx="3" cy="13" r="1.5"/><circle cx="7" cy="13" r="1.5"/>'
    + '</svg>';

function uiIsSortable(field) {
    return (field.dataset.prototype || '').includes('[position]')
        || !!field.querySelector('[name$="[position]"]');
}

function uiItemsContainer(field) {
    return field.querySelector('.form-widget-compound') || field.querySelector('.ea-form-collection-items');
}

function uiAddHandle(item) {
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
    btn.addEventListener('mouseup',   () => item.removeAttribute('draggable'));
    header.prepend(btn);
}

function uiDragAfter(field, y) {
    const items = [...field.querySelectorAll('.field-collection-item:not(.ui-dragging)')]
        .filter(item => item.closest('[data-ea-collection-field]') === field);
    return items.reduce((closest, child) => {
        const box    = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset, element: child };
        }
        return closest;
    }, { offset: -Infinity }).element;
}

function uiUpdatePositions(field) {
    [...field.querySelectorAll('.field-collection-item')]
        .filter(item => item.closest('[data-ea-collection-field]') === field)
        .forEach((item, i) => {
            const pos = item.querySelector('[name$="[position]"]');
            if (pos) pos.value = i;
        });
}

function uiInitField(field) {
    if (field.dataset.uiSortable || !uiIsSortable(field)) return;
    field.dataset.uiSortable = '1';

    const container = uiItemsContainer(field);
    if (!container) return;

    field.querySelectorAll('.field-collection-item').forEach(uiAddHandle);

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
        uiUpdatePositions(field);
        dragging = null;
    });

    container.addEventListener('dragover', e => {
        e.preventDefault();
        if (!dragging) return;
        const after = uiDragAfter(field, e.clientY);
        if (!after) dragging.parentElement.appendChild(dragging);
        else after.parentElement.insertBefore(dragging, after);
    });
}

function uiInitAll() {
    document.querySelectorAll('[data-ea-collection-field]').forEach(uiInitField);
}

// ===== Setup =====

window.addEventListener('DOMContentLoaded', () => { initTrixEditors(); uiInitAll(); });

document.addEventListener('ea.collection.item-added', () => initTrixEditors());
document.addEventListener('ea.collection.item-added', e => {
    const newElement = e.detail && e.detail.newElement;
    if (newElement) {
        uiAddHandle(newElement);
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
    uiInitAll();
});

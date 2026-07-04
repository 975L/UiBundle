/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
function initTrixEditors() {
    document.querySelectorAll('textarea[data-trix]:not([data-trix-init])').forEach(function (textarea) {
        if (!textarea.id) return;
        textarea.dataset.trixInit = '1';
        textarea.classList.add('ea-text-editor-content', 'd-none');

        const wrapper = document.createElement('div');
        wrapper.className = 'ea-text-editor-wrapper';
        const editor = document.createElement('trix-editor');
        editor.setAttribute('input', textarea.id);
        editor.className = 'trix-content';
        wrapper.appendChild(editor);
        textarea.insertAdjacentElement('afterend', wrapper);
    });
}

window.addEventListener('DOMContentLoaded', initTrixEditors);
document.addEventListener('turbo:load', initTrixEditors);
document.addEventListener('c975l:block-data-loaded', initTrixEditors);

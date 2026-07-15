/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["frame", "range"];

    connect() {
        this.dragging = false;
        this.frameTarget.addEventListener("pointerdown", this.startDrag.bind(this));
        this.frameTarget.addEventListener("pointermove", this.drag.bind(this));
        this.frameTarget.addEventListener("pointerup", this.stopDrag.bind(this));
        this.frameTarget.addEventListener("pointercancel", this.stopDrag.bind(this));
    }

    // Native <input type="range"> already drives --image-compare-position via the "update" action
    // (keyboard/screen-reader friendly out of the box) - dragging directly on the frame just forwards
    // the pointer position to that same range input, so both stay in sync through a single code path
    startDrag(e) {
        // Without this, starting the drag on top of an <img> triggers the browser's native
        // image drag-and-drop instead of our pointer-based slider (Firefox ignores draggable="false" on pointerdown)
        e.preventDefault();
        this.dragging = true;
        this.frameTarget.setPointerCapture(e.pointerId);
        this.setPositionFromPointer(e);
    }

    drag(e) {
        if (this.dragging) {
            this.setPositionFromPointer(e);
        }
    }

    stopDrag(e) {
        this.dragging = false;
        if (this.frameTarget.hasPointerCapture(e.pointerId)) {
            this.frameTarget.releasePointerCapture(e.pointerId);
        }
    }

    setPositionFromPointer(e) {
        const rect = this.frameTarget.getBoundingClientRect();
        const percent = Math.round(Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100)));
        this.rangeTarget.value = percent;
        this.update();
    }

    // Fired on the range input's own "input" event too (see data-action), so keyboard/screen-reader
    // driven changes update the visual split exactly like a pointer drag would
    update() {
        const percent = this.rangeTarget.value;
        this.element.style.setProperty("--image-compare-position", `${percent}%`);
        this.rangeTarget.setAttribute("aria-valuetext", `${percent}%`);
    }
}

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import { Controller } from "@hotwired/stimulus";

// One floating "Edit" button shared by every editable block on the page (position:fixed, repositioned
// on hover via getBoundingClientRect). A wrapping <div> around each block can't host the button itself:
// ".block-editable"/".block-animation" are display:contents so they stay transparent to the parent's
// own grid/flex layout - see sass/_animations-media.scss and sass/_block-edit-overlay.scss.
export default class extends Controller {
    connect() {
        // Blocks.html.twig can render several ".blocks" collections on one page (page content, footer
        // menu...), each mounting this controller - only the first one actually builds the shared button.
        if (window.blockEditOverlayController) {
            return;
        }
        window.blockEditOverlayController = this;

        this.activeTarget = null;
        this.button = this.buildButton();
        document.body.append(this.button);

        this.onMouseOver = this.onMouseOver.bind(this);
        this.onMouseOut = this.onMouseOut.bind(this);
        this.onFocusIn = this.onFocusIn.bind(this);
        this.onFocusOut = this.onFocusOut.bind(this);
        this.onButtonMouseLeave = this.onButtonMouseLeave.bind(this);
        this.onReposition = this.onReposition.bind(this);

        document.addEventListener("mouseover", this.onMouseOver);
        document.addEventListener("mouseout", this.onMouseOut);
        document.addEventListener("focusin", this.onFocusIn);
        document.addEventListener("focusout", this.onFocusOut);
        this.button.addEventListener("mouseleave", this.onButtonMouseLeave);
        window.addEventListener("scroll", this.onReposition, true);
        window.addEventListener("resize", this.onReposition);
    }

    disconnect() {
        if (window.blockEditOverlayController !== this) {
            return;
        }
        window.blockEditOverlayController = null;

        document.removeEventListener("mouseover", this.onMouseOver);
        document.removeEventListener("mouseout", this.onMouseOut);
        document.removeEventListener("focusin", this.onFocusIn);
        document.removeEventListener("focusout", this.onFocusOut);
        window.removeEventListener("scroll", this.onReposition, true);
        window.removeEventListener("resize", this.onReposition);
        this.button.remove();
    }

    buildButton() {
        const button = document.createElement("a");
        button.className = "block-edit-overlay-btn";
        button.target = "_blank";
        button.rel = "noopener";
        button.tabIndex = -1;
        button.setAttribute("aria-hidden", "true");
        button.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';

        const label = document.createElement("span");
        label.textContent = this.element.dataset.editLabel || "Edit";
        button.append(label);

        return button;
    }

    onMouseOver(event) {
        const target = event.target.closest("[data-block-edit-url]");
        if (target) {
            this.show(target);
        }
    }

    onMouseOut(event) {
        const target = event.target.closest("[data-block-edit-url]");
        if (!target || target !== this.activeTarget) {
            return;
        }
        // Moving from the block onto the floating button itself must not hide it - the button
        // isn't a DOM descendant of the block (position:fixed, appended to <body>).
        const related = event.relatedTarget;
        if (related && (related === this.button || this.button.contains(related) || target.contains(related))) {
            return;
        }
        this.hide();
    }

    onButtonMouseLeave(event) {
        const related = event.relatedTarget;
        if (this.activeTarget && related && this.activeTarget.contains(related)) {
            return;
        }
        this.hide();
    }

    onFocusIn(event) {
        const target = event.target.closest("[data-block-edit-url]");
        if (target) {
            this.show(target);
        }
    }

    onFocusOut(event) {
        if (!this.activeTarget) {
            return;
        }
        const related = event.relatedTarget;
        if (related && (this.activeTarget.contains(related) || related === this.button)) {
            return;
        }
        this.hide();
    }

    onReposition() {
        if (this.activeTarget) {
            this.position(this.activeTarget);
        }
    }

    show(target) {
        this.activeTarget = target;
        this.button.href = target.dataset.blockEditUrl;
        this.button.removeAttribute("aria-hidden");
        this.button.tabIndex = 0;
        this.position(target);
        this.button.classList.add("is-visible");
    }

    hide() {
        this.activeTarget = null;
        this.button.classList.remove("is-visible");
        this.button.setAttribute("aria-hidden", "true");
        this.button.tabIndex = -1;
    }

    position(target) {
        // target (".block-editable"/".block-animation") is display:contents by design (see
        // sass/_block-edit-overlay.scss) - it generates no box, so its own getBoundingClientRect()
        // is always a zero rect. Measure the block's actual rendered root element instead, which
        // is what visually stands in for the wrapper in the page's layout.
        const rect = (target.firstElementChild || target).getBoundingClientRect();
        this.button.style.top = `${Math.max(rect.top, 0) + 8}px`;
        // left is the block's own right edge; the button's width varies with its translated label
        // ("Editer" vs "Edit" vs "Editar"), so it right-aligns itself via CSS transform:translateX(-100%)
        // instead of subtracting a fixed width here.
        this.button.style.left = `${rect.right - 8}px`;
    }
}

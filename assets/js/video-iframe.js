/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import { Controller } from "@hotwired/stimulus";

// Deliberately not coupled to any particular consent-banner implementation or bundle - reacts to
// an optional external contract instead: a `[data-controller~="cookieConsent"]` element present in
// the page, a `window.CookieConsent` global exposing vanilla-cookieconsent v3's API
// (https://cookieconsent.orestbida.com/), and its `cc:onConsent`/`cc:onChange` DOM events. c975l/site-bundle's
// `<twig:c975LSite:General:CookieConsent/>` is one such provider, but any consuming app's own banner
// satisfying the same contract works just as well - no composer dependency on it either way.
export default class extends Controller {
    static targets = ["placeholder"];
    static values = { src: String, width: String, height: String };

    connect() {
        this.onConsent = this.onConsent.bind(this);

        // No consent banner on this page - never block content on a site that doesn't use one
        if (!document.querySelector('[data-controller~="cookieConsent"]')) {
            this.renderIframe();
            return;
        }

        if (window.CookieConsent && window.CookieConsent.acceptedCategory("content")) {
            this.renderIframe();
            return;
        }

        // Consent not yet decided (or lib still loading) - wait for it. "cc:onConsent" fires on
        // every page load once the user's choice is known (not just the first time), so returning
        // visitors who already accepted still get the iframe without needing to click again
        window.addEventListener("cc:onConsent", this.onConsent);
        window.addEventListener("cc:onChange", this.onConsent);
    }

    disconnect() {
        window.removeEventListener("cc:onConsent", this.onConsent);
        window.removeEventListener("cc:onChange", this.onConsent);
    }

    accept() {
        // The banner element can render before its own script (which sets this global) finishes
        // loading - a click in that window must be a no-op, not a thrown error
        window.CookieConsent?.acceptCategory("content");
    }

    onConsent() {
        if (window.CookieConsent?.acceptedCategory("content")) {
            this.renderIframe();
        }
    }

    renderIframe() {
        window.removeEventListener("cc:onConsent", this.onConsent);
        window.removeEventListener("cc:onChange", this.onConsent);

        const iframe = document.createElement("iframe");
        iframe.src = this.srcValue;
        iframe.title = "Video player";
        iframe.frameBorder = "0";
        iframe.allowFullscreen = true;
        iframe.loading = "lazy";
        if (this.widthValue) {
            iframe.width = this.widthValue;
        }
        if (this.heightValue) {
            iframe.height = this.heightValue;
        }
        this.element.replaceChildren(iframe);
    }
}

<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Management;

use c975L\ConfigBundle\Management\MenuProviderInterface;

class MenuProvider implements MenuProviderInterface
{
    // getMenus() is empty below, so this section never actually renders for this provider - kept
    // identical to ConfigBundle's/SiteBundle's own value only so a future CRUD entry added here
    // merges into the same "Management" group instead of creating a duplicate one
    public function getMenuSection(): array
    {
        return [
            'label' => 'label.management',
            'translation_domain' => 'site',
        ];
    }

    public function getMenus(): array
    {
        return [];
    }

    // UiBundle's own EasyAdmin block gallery (BlockGalleryController) was removed entirely, not just
    // unlinked: its preview iframes needed inline scripts for interactivity (slider/image_compare),
    // which a hash/nonce-based CSP (e.g. nelmio_security's csp.hash config) can never authorize inside an
    // <iframe srcdoc> - nelmio secures a response by scanning its literal <script>/<style> elements, and
    // content trapped inside a srcdoc *attribute string* is invisible to that scan, so those scripts got
    // no valid nonce/hash and were permanently blocked. Not a bug in the gallery's own templates, a
    // structural incompatibility with that class of CSP tooling. The block fixture/showcase machinery it
    // used (BlockFixtureRegistry, GalleryShowcaseRegistry...) stays - it's what powers 975l.com's public
    // /vitrine-blocks, the c975L ecosystem's own canonical demo of every bundle's block kinds (rendered
    // inline in a normal page, no iframe, no CSP conflict). This link to it is part of the bundle itself,
    // not app-specific config (Laurent: "elle fait partie du système de management") - every app using
    // this bundle gets the same reference demo, a fixed 'url' (not a route name resolved per-app) since
    // it always points at that one canonical site regardless of which app's dashboard is showing it.
    public function getLinks(): array
    {
        return [
            'block_showcase' => [
                'label' => 'label.block_showcase',
                'translation_domain' => 'ui',
                'icon' => 'fas fa-shapes',
                'url' => 'https://975l.com/vitrine-blocks',
                'target' => '_blank',
            ],
        ];
    }
}

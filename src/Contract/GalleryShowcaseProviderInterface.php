<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Contract;

// Implement to add non-block content to the gallery (see BlockGalleryController) - for a component
// or Twig function whose visual styles are worth showcasing but that isn't a "ui.block" kind (e.g.
// SocialBundle's social_links icon styles or share_buttons() style, both driven by a singleton or
// called directly rather than through a per-page Block).
interface GalleryShowcaseProviderInterface
{
    // One entry per showcase: label => ['description' => string, 'kind' => ?string, 'category' => ?string,
    // 'wide' => ?bool, 'variants' => [variant label => already-rendered HTML]].
    //
    // "kind" is the real block kind this showcase stands in for - used to both join its category
    // (BlockRegistry::getCategory($kind)) and suppress that kind's own regular preview card, so it doesn't
    // also show up empty right next to this one. Use null when there's no real kind at all (e.g.
    // share_buttons() isn't a block kind).
    //
    // "category" overrides the category directly (no BlockRegistry lookup, no suppression) - use this for
    // a showcase with no real kind to stand in for, but that still belongs next to a related one (e.g.
    // share_buttons() has no kind, but reusing the same 'label.category_navigation' key social_links_display
    // is tagged with in services.yaml groups it there instead of the generic fallback). Takes precedence
    // over "kind" when both are set. Neither set falls back to a generic category.
    //
    // "wide" (default false): originally rendered this card wider than the gallery's old fixed-width
    // cards, for a component whose real styles only apply above a CSS breakpoint (e.g. share_buttons()
    // hides itself entirely below 768px). Since the gallery now renders every item full-width (see
    // block_gallery.html.twig), this flag is currently a no-op there - kept in the contract so a provider
    // that already sets it (e.g. SocialBundle's share_buttons()) doesn't break, and any other consumer of
    // GalleryShowcaseRegistry's data (e.g. a future non-EasyAdmin listing) can still honor it.
    public function getShowcases(): array;
}

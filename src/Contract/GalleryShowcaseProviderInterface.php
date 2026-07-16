<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Contract;

// Implement to add non-block content to a block showcase (see GalleryShowcaseRegistry, consumed by
// 975l.com's public /vitrine-blocks) - for a component or Twig function whose visual styles are worth
// showcasing but that isn't a "ui.block" kind (e.g. SocialBundle's social_links icon styles or
// share_buttons() style, both driven by a singleton or called directly rather than through a per-page
// Block).
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
    // "wide" (default false): originally rendered this card wider than a fixed-width card grid, for a
    // component whose real styles only apply above a CSS breakpoint (e.g. share_buttons() hides itself
    // entirely below 768px). Neither current consumer of this registry renders a fixed-width grid
    // anymore, so this is currently a no-op - kept in the contract so a provider that already sets it
    // (e.g. SocialBundle's share_buttons()) doesn't break, and any future fixed-width consumer can honor it.
    public function getShowcases(): array;
}

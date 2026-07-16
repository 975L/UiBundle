<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Contract;

// Implement to expose a queryable collection of another bundle's own entities (books, products,
// projects...) to the "collection" block, without that block ever depending on the owning bundle -
// same auto-discovery mechanism as BlockFixtureProviderInterface, no tag needed
interface CollectionSourceProviderInterface
{
    // One entry per exposed source, keyed by a unique source key (e.g. "site.collection.projects"):
    // [
    //     'label' => string,
    //     'items' => callable(?int $limit): iterable<CollectionItem>,
    //     'detail' => callable(string $slug): ?array, // optional - only if per-item detail views exist
    // ]
    // "detail" lets a Page holding this source's "collection" block also serve per-item detail URLs
    // (/pages/{page}/{slug}) with zero Page/Block rows persisted per item - see
    // PageController::resolveCollectionDetail(). Returns null if $slug doesn't resolve to an item, so
    // the caller can fall through to a 404. Otherwise, a plain array of template variables for whichever
    // template the Page's own "twig_content" block declares (its "templatePath" field) - by convention
    // always include a 'title' key, used for that URL's <title> instead of the parent Page's own.
    public function getSources(): array;
}

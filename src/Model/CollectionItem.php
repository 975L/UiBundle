<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Model;

// One entry of a CollectionSourceProviderInterface source - the "collection" block renders each as a
// never-persisted "card" Block (see Collection.html.twig). imageUrl is an already-resolved URL: each
// provider is responsible for its own image storage (a real attached Media, a Vich field on its own
// entity, anything) and hands back a plain string, never a Media/entity reference
final class CollectionItem
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $url = null,
        public readonly ?string $buttonLabel = null,
        public readonly ?string $buttonIcon = null
    ) {
    }
}

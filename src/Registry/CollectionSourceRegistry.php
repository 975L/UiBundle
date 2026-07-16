<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Registry;

use c975L\UiBundle\Contract\CollectionSourceProviderInterface;
use c975L\UiBundle\Model\CollectionItem;

class CollectionSourceRegistry
{
    private array $sources = [];

    // Called once per tagged provider by CollectionSourceProviderPass
    public function addProvider(CollectionSourceProviderInterface $provider): void
    {
        $this->sources = array_merge($this->sources, $provider->getSources());
    }

    public function has(string $source): bool
    {
        return isset($this->sources[$source]);
    }

    // source key => translated-ready label, for the "source" ChoiceType in CollectionType
    public function choices(): array
    {
        $choices = [];
        foreach ($this->sources as $key => $source) {
            $label = $source['label'];
            // Two providers sharing the same label would otherwise collide on this array's own key,
            // silently hiding whichever source got merged in first (see addProvider()) - disambiguate
            // instead of losing one of them
            if (isset($choices[$label])) {
                $label .= ' (' . $key . ')';
            }
            $choices[$label] = $key;
        }

        return $choices;
    }

    // @return CollectionItem[]
    public function items(string $source, ?int $limit): array
    {
        if (!isset($this->sources[$source])) {
            return [];
        }

        return ($this->sources[$source]['items'])($limit);
    }

    // Tolerant on purpose, like items(): a source with no "detail" capability, or an unknown
    // item slug, simply yields no detail view - the caller (PageController) falls through to a 404.
    // @return array<string, mixed>|null template variables, not rendered HTML - see
    // CollectionSourceProviderInterface's own docblock for the "title" convention
    public function detail(string $source, string $slug): ?array
    {
        return isset($this->sources[$source]['detail']) ? ($this->sources[$source]['detail'])($slug) : null;
    }
}

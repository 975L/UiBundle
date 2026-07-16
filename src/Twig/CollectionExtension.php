<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Twig;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Registry\CollectionSourceRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CollectionExtension extends AbstractExtension
{
    public function __construct(
        private CollectionSourceRegistry $sourceRegistry,
        private BlockExtension $blockExtension
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('collection_render_items', [$this, 'renderItems']),
        ];
    }

    // Renders each item as a never-persisted "card" Block (see Card.html.twig's "imageUrl" fallback) -
    // literally the same render_block() pipeline as a real, editor-placed card, just fed with data
    // built from the source's own CollectionItem instead of a stored Block::$data
    // @return string[] one rendered HTML fragment per item
    public function renderItems(string $source, ?int $limit): array
    {
        $rendered = [];
        foreach ($this->sourceRegistry->items($source, $limit) as $item) {
            $block = (new Block())->setKind('card')->setData([
                'title'       => $item->title,
                'content'     => $item->description,
                'url'         => $item->url,
                'imageUrl'    => $item->imageUrl,
                'buttonLabel' => $item->buttonLabel,
                'buttonIcon'  => $item->buttonIcon,
            ]);
            $rendered[] = $this->blockExtension->renderBlock($block);
        }

        return $rendered;
    }
}

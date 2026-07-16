<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Service;

use c975L\UiBundle\Contract\GalleryShowcaseProviderInterface;
use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Twig\BlockExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

// Shows "collection" in a block showcase (see GalleryShowcaseRegistry) - it has no
// BlockFixtureProviderInterface fixture because its items come from a real, cross-bundle
// CollectionSourceProviderInterface source (see CollectionExtension), which a fixture can't fabricate -
// same reasoning as SiteBundle's own GalleryShowcaseProvider for "articles_slider"/"menu_link".
// Rendered here instead against the exact same components with made-up sample items.
class GalleryShowcaseProvider implements GalleryShowcaseProviderInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
        private readonly BlockExtension $blockExtension,
        private readonly BlockFixtureMediaAttacher $mediaAttacher,
    ) {
    }

    public function getShowcases(): array
    {
        return [
            $this->translator->trans('label.gallery_showcase_collection', [], 'ui') => [
                'description' => $this->translator->trans('label.gallery_showcase_collection_description', [], 'ui'),
                'kind' => 'collection',
                'variants' => ['' => $this->collectionVariant()],
            ],
        ];
    }

    // Each fake item goes through the exact same "card" Block/render_block() pipeline as a real
    // collection item (see CollectionExtension::renderItems()) - only the source data is made up.
    private function collectionVariant(): string
    {
        $projects = [
            ['Papa Câlin', "Des histoires inventées à partir des idées d'enfants."],
            ['EIPT', 'École informatique pour tous, de la primaire aux seniors.'],
            ['Éditions Lolant', 'Le catalogue des livres publiés par la maison.'],
        ];

        $items = [];
        foreach ($projects as [$title, $text]) {
            $media = $this->mediaAttacher->nextPlaceholderImage();
            $block = (new Block())->setKind('card')->setData([
                'title'    => $title,
                'content'  => $text,
                'url'      => 'https://975l.com',
                'imageUrl' => null,
            ]);
            $block->addMedia($media);
            $items[] = $this->blockExtension->renderBlock($block);
        }

        return $this->twig->render('@c975LUi/components/Collection/Grid.html.twig', [
            'eyebrow'   => 'Réalisations',
            'title'     => 'Nos derniers projets',
            'linkLabel' => 'Tout voir',
            'linkUrl'   => '#',
            'items'     => $items,
        ]);
    }
}

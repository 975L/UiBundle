<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Controller\Management;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Entity\Media;
use c975L\UiBundle\Registry\BlockFixtureRegistry;
use c975L\UiBundle\Registry\BlockRegistry;
use c975L\UiBundle\Registry\GalleryShowcaseRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class BlockGalleryController extends AbstractController
{
    // EasyAdmin prefixes this with the Dashboard's own route name, giving management_ui_block_gallery
    // (see BlockShortcutController::CLEAR_CACHE_ROUTE for the same convention) - used by
    // c975L\UiBundle\Management\MenuProvider to link to this page from the EasyAdmin sidebar
    public const GALLERY_ROUTE = 'management_ui_block_gallery';

    // Static assets shipped by this bundle (see public/images, public/videos and public/audio), used as
    // stand-ins wherever a block's fixture needs media - no real upload/Media persistence needed
    public const PLACEHOLDER_IMAGE = 'bundles/c975lui/images/gallery-placeholder.svg';
    public const PLACEHOLDER_VIDEO = 'bundles/c975lui/videos/gallery-placeholder.mp4';
    public const PLACEHOLDER_AUDIO = 'bundles/c975lui/audio/gallery-placeholder.mp3';

    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly BlockFixtureRegistry $fixtures,
        private readonly GalleryShowcaseRegistry $showcases,
        private readonly TranslatorInterface $translator,
    ) {
    }

    // Renders every pickable block kind with sample data, grouped like the real kind-picker - lets an
    // editor see what each kind looks like before choosing one, without creating/filling a real block first.
    // Showcases (see GalleryShowcaseProviderInterface) join the same grouping via their "kind"'s own
    // category, or a generic fallback category when they have none (e.g. share_buttons() isn't a block
    // kind at all) - a showcase's "kind" also suppresses that kind's own regular preview card, so a kind
    // with no fixture of its own doesn't show up twice (once empty, once via its showcase).
    #[AdminRoute(path: '/ui/block/gallery', name: 'ui_block_gallery', options: ['methods' => ['GET']])]
    public function gallery(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        return $this->render('@c975LUi/management/block_gallery.html.twig', [
            'previews' => $this->buildPreviews(),
        ]);
    }

    // Merges every pickable block kind and every showcase into one category => key => preview structure.
    // Split out from gallery() so the merge/suppression logic can be unit-tested without a full request.
    private function buildPreviews(): array
    {
        $showcases = $this->showcases->all();
        $replacedKinds = array_filter(array_column($showcases, 'kind'));

        // Not reusing groupedByCategory(): it returns a composed "label (description)" choice string,
        // not the plain label needed here since the description is displayed separately
        $previews = [];
        foreach ($this->registry->all() as $kind => $config) {
            if (!$config['pickable'] || in_array($kind, $replacedKinds, true)) {
                continue;
            }

            $category = $this->registry->getCategory($kind);
            $previews[$category][$kind] = [
                'label' => $this->registry->getLabel($kind),
                'description' => $this->registry->getDescription($kind),
                // variantLabel => ['type' => 'block', 'content' => Block] - empty when the kind has no fixture yet
                'variants' => $this->buildBlockVariants($kind),
            ];
        }

        $otherCategory = $this->translator->trans('label.block_gallery_other_components', [], 'ui');
        foreach ($showcases as $label => $showcase) {
            $category = ($showcase['category'] ?? null)
                ?? (null !== ($showcase['kind'] ?? null) ? $this->registry->getCategory($showcase['kind']) : null)
                ?? $otherCategory;
            $previews[$category][$label] = [
                'label' => $label,
                'description' => $showcase['description'] ?? '',
                // Some components only style themselves above a CSS breakpoint (e.g. share_buttons()
                // hides below 768px, mobile has its own native share sheet) - "wide" renders this card's
                // previews in a wider box so that breakpoint is actually reached, instead of silently
                // rendering unstyled/invisible content
                'wide' => $showcase['wide'] ?? false,
                'variants' => array_map(
                    static fn (string $html): array => ['type' => 'html', 'content' => $html],
                    $showcase['variants']
                ),
            ];
        }

        ksort($previews, SORT_FLAG_CASE | SORT_STRING);
        foreach ($previews as &$items) {
            uasort($items, fn (array $a, array $b) => strcasecmp($a['label'], $b['label']));
        }
        unset($items);

        return $previews;
    }

    // One in-memory, never-persisted Block per registered variant (see BlockFixtureProviderInterface) -
    // each passed to render_block() exactly like a real block would be. Empty array when the kind has
    // no fixture yet, so the gallery falls back to its "no example yet" card.
    private function buildBlockVariants(string $kind): array
    {
        $variants = [];
        foreach ($this->fixtures->get($kind) as $variantLabel => $data) {
            $block = (new Block())->setKind($kind)->setData($data);

            // portfolio_grid needs several distinctly-captioned project cards to look like a real grid,
            // not just extra copies of the generic placeholder (see placeholderPortfolioProjects())
            if ('portfolio_grid' === $kind) {
                foreach ($this->placeholderPortfolioProjects() as $project) {
                    $block->addMedia($project);
                }
            } else {
                foreach ($this->registry->getMediaTypes($kind) as $mediaType) {
                    if (str_starts_with($mediaType, 'image/')) {
                        // "slider" needs several images to look like one; every other image-typed kind
                        // only ever reads its first media (see e.g. blocks/Image.html.twig). The slider
                        // also mixes in one video slide below to showcase its mixed-media support.
                        $count = 'slider' === $kind ? 2 : 1;
                        for ($i = 0; $i < $count; ++$i) {
                            $block->addMedia($this->placeholderImage());
                        }
                        if ('slider' !== $kind) {
                            break;
                        }
                    }

                    if ('slider' === $kind && str_starts_with($mediaType, 'video/')) {
                        $block->addMedia($this->placeholderVideo());
                    }

                    if (str_starts_with($mediaType, 'audio/')) {
                        $block->addMedia($this->placeholderAudio());
                        break;
                    }
                }
            }

            $variants[$variantLabel] = ['type' => 'block', 'content' => $block];
        }

        return $variants;
    }

    // @return Media[]
    private function placeholderPortfolioProjects(): array
    {
        $projects = [
            ['Papa Câlin', "Des histoires inventées à partir des idées d'enfants."],
            ['EIPT', 'École informatique pour tous, de la primaire aux seniors.'],
            ['Éditions Lolant', 'Le catalogue des livres publiés par la maison.'],
        ];

        return array_map(
            static fn (array $project): Media => (new Media())
                ->setFilename(self::PLACEHOLDER_IMAGE)
                ->setAlt($project[0])
                ->setLabel($project[0])
                ->setDescription($project[1])
                ->setUrl('https://975l.com'),
            $projects
        );
    }

    private function placeholderImage(): Media
    {
        return (new Media())
            ->setFilename(self::PLACEHOLDER_IMAGE)
            ->setAlt('Image d\'exemple')
            ->setWidth('800')
            ->setHeight('450');
    }

    private function placeholderVideo(): Media
    {
        return (new Media())
            ->setFilename(self::PLACEHOLDER_VIDEO)
            ->setMimeType('video/mp4')
            ->setAlt('Vidéo d\'exemple');
    }

    private function placeholderAudio(): Media
    {
        return (new Media())
            ->setFilename(self::PLACEHOLDER_AUDIO)
            ->setMimeType('audio/mpeg')
            ->setAlt('Audio d\'exemple');
    }
}

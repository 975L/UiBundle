<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Controller\Management;

use c975L\UiBundle\Controller\Management\BlockGalleryController;
use c975L\UiBundle\Registry\BlockFixtureRegistry;
use c975L\UiBundle\Registry\BlockRegistry;
use c975L\UiBundle\Registry\GalleryShowcaseRegistry;
use c975L\UiBundle\Service\BlockFixtureMediaAttacher;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BlockGalleryControllerTest extends TestCase
{
    private function invokeBuildPreviews(BlockGalleryController $controller): array
    {
        return (new \ReflectionMethod($controller, 'buildPreviews'))->invoke($controller);
    }

    // The gallery is meant for editors deciding which kind to pick, not restricted to super admins -
    // regression guard for the role this controller checks in gallery()
    public function testRoleNeededIsEditor(): void
    {
        $this->assertSame(
            'ROLE_EDITOR',
            (new \ReflectionClass(BlockGalleryController::class))->getConstant('ROLE_NEEDED')
        );
    }

    private function createRegistry(array $kinds): BlockRegistry
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('all')->willReturn($kinds);
        $registry->method('getCategory')->willReturnCallback(
            static fn (string $kind) => $kinds[$kind]['category']
        );
        $registry->method('getLabel')->willReturnCallback(static fn (string $kind) => ucfirst($kind));
        $registry->method('getDescription')->willReturn('');
        $registry->method('getMediaTypes')->willReturnCallback(
            static fn (string $kind) => $kinds[$kind]['mediaTypes'] ?? []
        );

        return $registry;
    }

    // Regression test: a kind with no fixture of its own used to show an empty "no example yet" card
    // right next to its showcase's real content - reported by Laurent as two cards per block
    public function testShowcaseWithAKindSuppressesThatKindsOwnRegularPreview(): void
    {
        $registry = $this->createRegistry([
            'menu_link' => ['pickable' => true, 'category' => 'Navigation'],
        ]);
        $fixtures = $this->createStub(BlockFixtureRegistry::class);
        $fixtures->method('get')->willReturn([]);
        $showcases = $this->createStub(GalleryShowcaseRegistry::class);
        $showcases->method('all')->willReturn([
            'Lien de menu' => ['description' => '', 'kind' => 'menu_link', 'variants' => ['' => '<a>html</a>']],
        ]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => $id);

        $controller = new BlockGalleryController($registry, $fixtures, $showcases, new BlockFixtureMediaAttacher($registry), $translator);
        $previews = $this->invokeBuildPreviews($controller);

        $this->assertCount(1, $previews['Navigation'], 'menu_link should appear exactly once, via its showcase');
        $this->assertArrayHasKey('Lien de menu', $previews['Navigation']);
        $this->assertArrayNotHasKey('menu_link', $previews['Navigation']);
    }

    // A showcase with no "kind" (e.g. share_buttons()) falls back to the generic category, not a real one
    public function testShowcaseWithoutAKindFallsBackToTheGenericCategory(): void
    {
        $registry = $this->createRegistry([]);
        $fixtures = $this->createStub(BlockFixtureRegistry::class);
        $fixtures->method('get')->willReturn([]);
        $showcases = $this->createStub(GalleryShowcaseRegistry::class);
        $showcases->method('all')->willReturn([
            'Boutons de partage' => ['description' => '', 'kind' => null, 'variants' => ['Distinct' => '<div>html</div>']],
        ]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => $id);

        $controller = new BlockGalleryController($registry, $fixtures, $showcases, new BlockFixtureMediaAttacher($registry), $translator);
        $previews = $this->invokeBuildPreviews($controller);

        $this->assertArrayHasKey('label.block_gallery_other_components', $previews);
        $this->assertArrayHasKey('Boutons de partage', $previews['label.block_gallery_other_components']);
    }

    // A showcase can name a category directly (no real kind to derive it from, nothing to suppress) -
    // e.g. share_buttons() reusing "social_links_display"'s own category instead of the generic fallback
    public function testShowcaseWithAnExplicitCategoryJoinsItInsteadOfTheGenericFallback(): void
    {
        $registry = $this->createRegistry([]);
        $fixtures = $this->createStub(BlockFixtureRegistry::class);
        $fixtures->method('get')->willReturn([]);
        $showcases = $this->createStub(GalleryShowcaseRegistry::class);
        $showcases->method('all')->willReturn([
            'Boutons de partage' => ['description' => '', 'kind' => null, 'category' => 'Navigation', 'variants' => ['' => '<div>html</div>']],
        ]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => $id);

        $controller = new BlockGalleryController($registry, $fixtures, $showcases, new BlockFixtureMediaAttacher($registry), $translator);
        $previews = $this->invokeBuildPreviews($controller);

        $this->assertArrayHasKey('Navigation', $previews);
        $this->assertArrayHasKey('Boutons de partage', $previews['Navigation']);
        $this->assertArrayNotHasKey('label.block_gallery_other_components', $previews);
    }

    // A kind with no showcase at all still gets its own regular preview, unaffected by the suppression logic
    public function testKindWithoutAShowcaseKeepsItsOwnRegularPreview(): void
    {
        $registry = $this->createRegistry([
            'alert' => ['pickable' => true, 'category' => 'Elements'],
        ]);
        $fixtures = $this->createStub(BlockFixtureRegistry::class);
        $fixtures->method('get')->willReturn(['' => ['type' => 'info']]);
        $showcases = $this->createStub(GalleryShowcaseRegistry::class);
        $showcases->method('all')->willReturn([]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => $id);

        $controller = new BlockGalleryController($registry, $fixtures, $showcases, new BlockFixtureMediaAttacher($registry), $translator);
        $previews = $this->invokeBuildPreviews($controller);

        $this->assertArrayHasKey('alert', $previews['Elements']);
    }

    // A "wide" showcase (e.g. share_buttons(), whose CSS only applies above a 768px breakpoint) carries
    // that flag through to the merged preview item - currently unused by the (now full-width) template
    // itself, but kept for any other consumer of this data (see GalleryShowcaseProviderInterface)
    public function testWideShowcaseCarriesTheFlagThroughToThePreview(): void
    {
        $registry = $this->createRegistry([]);
        $fixtures = $this->createStub(BlockFixtureRegistry::class);
        $fixtures->method('get')->willReturn([]);
        $showcases = $this->createStub(GalleryShowcaseRegistry::class);
        $showcases->method('all')->willReturn([
            'Boutons de partage' => ['description' => '', 'kind' => null, 'wide' => true, 'variants' => ['' => '<div>html</div>']],
        ]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => $id);

        $controller = new BlockGalleryController($registry, $fixtures, $showcases, new BlockFixtureMediaAttacher($registry), $translator);
        $previews = $this->invokeBuildPreviews($controller);

        $this->assertTrue($previews['label.block_gallery_other_components']['Boutons de partage']['wide']);
    }

    // A kind tagged with an "audio/*" mediaType gets a placeholder audio Media attached automatically,
    // the same generic mechanism already used for "image/*"
    public function testAudioMediaTypeGetsThePlaceholderAudioAttached(): void
    {
        $registry = $this->createRegistry([
            'audio' => ['pickable' => true, 'category' => 'Media', 'mediaTypes' => ['audio/*']],
        ]);
        $fixtures = $this->createStub(BlockFixtureRegistry::class);
        $fixtures->method('get')->willReturn(['' => ['type' => 'audio/mpeg']]);
        $showcases = $this->createStub(GalleryShowcaseRegistry::class);
        $showcases->method('all')->willReturn([]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => $id);

        $controller = new BlockGalleryController($registry, $fixtures, $showcases, new BlockFixtureMediaAttacher($registry), $translator);
        $previews = $this->invokeBuildPreviews($controller);

        $block = $previews['Media']['audio']['variants']['']['content'];
        $this->assertSame(BlockFixtureMediaAttacher::PLACEHOLDER_AUDIO, $block->getMedia()->first()->getFilename());
    }

    // portfolio_grid bypasses the generic per-mediaType placeholder mechanism above: it gets several
    // distinctly-captioned project cards (see placeholderPortfolioProjects()) instead of N copies of
    // the same placeholder image
    public function testPortfolioGridKindGetsSeveralDistinctlyCaptionedPlaceholderProjects(): void
    {
        $registry = $this->createRegistry([
            'portfolio_grid' => ['pickable' => true, 'category' => 'Page sections', 'mediaTypes' => ['image/*']],
        ]);
        $fixtures = $this->createStub(BlockFixtureRegistry::class);
        $fixtures->method('get')->willReturn(['' => ['title' => 'Réalisations']]);
        $showcases = $this->createStub(GalleryShowcaseRegistry::class);
        $showcases->method('all')->willReturn([]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => $id);

        $controller = new BlockGalleryController($registry, $fixtures, $showcases, new BlockFixtureMediaAttacher($registry), $translator);
        $previews = $this->invokeBuildPreviews($controller);

        $block = $previews['Page sections']['portfolio_grid']['variants']['']['content'];
        $medias = $block->getMedia();
        $this->assertCount(3, $medias, 'portfolio_grid should get several distinct project cards, not a single placeholder');
        $this->assertSame('Papa Câlin', $medias->first()->getLabel());
        $this->assertNotNull($medias->first()->getDescription());
        $this->assertNotNull($medias->first()->getUrl());
    }
}

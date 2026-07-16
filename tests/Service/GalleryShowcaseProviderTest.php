<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Service;

use c975L\UiBundle\Entity\Media;
use c975L\UiBundle\Service\BlockFixtureMediaAttacher;
use c975L\UiBundle\Service\GalleryShowcaseProvider;
use c975L\UiBundle\Twig\BlockExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class GalleryShowcaseProviderTest extends TestCase
{
    private function createProvider(): GalleryShowcaseProvider
    {
        // TemplateWrapper is final and can't be doubled - use a real Environment (ArrayLoader stubs
        // the one named template the provider renders) so render() also works for real
        $twig = new Environment(new ArrayLoader([
            '@c975LUi/components/Collection/Grid.html.twig' => '<!-- collection grid: {{ items|length }} items -->',
        ]));

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => $id);

        $blockExtension = $this->createStub(BlockExtension::class);
        $blockExtension->method('renderBlock')->willReturn('<!-- card -->');

        $mediaAttacher = $this->createStub(BlockFixtureMediaAttacher::class);
        $mediaAttacher->method('nextPlaceholderImage')->willReturnCallback(static fn () => new Media());

        return new GalleryShowcaseProvider($twig, $translator, $blockExtension, $mediaAttacher);
    }

    public function testGetShowcasesReturnsTheCollectionSection(): void
    {
        $showcases = $this->createProvider()->getShowcases();

        $this->assertSame(['label.gallery_showcase_collection'], array_keys($showcases));
    }

    // Single-variant showcase (no meaningful style choice to compare, unlike alert/button)
    public function testShowcaseHasASingleUnlabelledVariant(): void
    {
        $showcases = $this->createProvider()->getShowcases();

        $this->assertSame([''], array_keys($showcases['label.gallery_showcase_collection']['variants']));
    }

    // Stands in for the "collection" block kind - the gallery suppresses its own regular (empty)
    // preview card once "kind" is set here, so it doesn't show up twice
    public function testShowcaseStandsInForTheCollectionBlockKind(): void
    {
        $showcases = $this->createProvider()->getShowcases();

        $this->assertSame('collection', $showcases['label.gallery_showcase_collection']['kind']);
    }

    public function testVariantRendersThreeItemsThroughTheGridComponent(): void
    {
        $showcases = $this->createProvider()->getShowcases();

        $this->assertSame(
            '<!-- collection grid: 3 items -->',
            $showcases['label.gallery_showcase_collection']['variants']['']
        );
    }
}

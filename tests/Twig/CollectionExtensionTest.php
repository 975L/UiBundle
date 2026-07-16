<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Twig;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Model\CollectionItem;
use c975L\UiBundle\Registry\CollectionSourceRegistry;
use c975L\UiBundle\Twig\BlockExtension;
use c975L\UiBundle\Twig\CollectionExtension;
use PHPUnit\Framework\TestCase;

class CollectionExtensionTest extends TestCase
{
    // Each CollectionItem becomes a never-persisted "card" Block, rendered through the exact same
    // render_block() pipeline as a real, editor-placed card
    public function testRenderItemsBuildsACardBlockPerItemAndRendersItThroughBlockExtension(): void
    {
        $item = new CollectionItem('Project A', 'A short text', '/uploads/project-a.webp', '/projets/a');

        $sourceRegistry = $this->createStub(CollectionSourceRegistry::class);
        $sourceRegistry->method('items')->willReturn([$item]);

        $renderedBlock = null;
        $blockExtension = $this->createMock(BlockExtension::class);
        $blockExtension->expects($this->once())
            ->method('renderBlock')
            ->willReturnCallback(function (Block $block) use (&$renderedBlock) {
                $renderedBlock = $block;

                return '<div class="card">Project A</div>';
            });

        $extension = new CollectionExtension($sourceRegistry, $blockExtension);
        $result = $extension->renderItems('site.collection.projects', 6);

        $this->assertSame(['<div class="card">Project A</div>'], $result);
        $this->assertSame('card', $renderedBlock->getKind());
        $this->assertSame([
            'title'       => 'Project A',
            'content'     => 'A short text',
            'url'         => '/projets/a',
            'imageUrl'    => '/uploads/project-a.webp',
            'buttonLabel' => null,
            'buttonIcon'  => null,
        ], $renderedBlock->getData());
    }

    public function testRenderItemsReturnsEmptyArrayWhenSourceHasNoItems(): void
    {
        $sourceRegistry = $this->createStub(CollectionSourceRegistry::class);
        $sourceRegistry->method('items')->willReturn([]);

        $extension = new CollectionExtension($sourceRegistry, $this->createStub(BlockExtension::class));

        $this->assertSame([], $extension->renderItems('site.collection.projects', null));
    }

    public function testGetFunctionsRegistersCollectionFunctions(): void
    {
        $extension = new CollectionExtension(
            $this->createStub(CollectionSourceRegistry::class),
            $this->createStub(BlockExtension::class)
        );
        $names = array_map(fn ($f) => $f->getName(), $extension->getFunctions());

        $this->assertSame(['collection_render_items'], $names);
    }
}

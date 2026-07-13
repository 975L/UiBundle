<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\DependencyInjection\Compiler;

use c975L\UiBundle\DependencyInjection\Compiler\BlockRegistryPass;
use c975L\UiBundle\Registry\BlockRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BlockRegistryPassTest extends TestCase
{
    public function testProcessDoesNothingWhenRegistryIsNotRegistered(): void
    {
        $container = new ContainerBuilder();

        (new BlockRegistryPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testProcessRegistersBlockWithDeclaredAttributesAndDefaults(): void
    {
        $container = new ContainerBuilder();
        $container->register(BlockRegistry::class);
        $container->register('block.article')->addTag('ui.block', [
            'kind' => 'article',
            'label' => 'label.article',
            'form' => 'App\\Form\\ArticleType',
            'template' => 'article.html.twig',
        ]);

        (new BlockRegistryPass())->process($container);

        $calls = $container->getDefinition(BlockRegistry::class)->getMethodCalls();
        $this->assertCount(1, $calls);
        $this->assertSame('register', $calls[0][0]);
        $this->assertSame(
            [
                'article',
                'label.article',
                'App\\Form\\ArticleType',
                'article.html.twig',
                'label.category_general',
                [],
                'ui',
                '',
                true,
                0,
                true,
                [],
                false,
                false,
            ],
            $calls[0][1]
        );
    }

    // media_required defaults to false and can be explicitly enabled, same boolean-flag parsing as pickable/cacheable
    public function testProcessParsesMediaRequiredFlag(): void
    {
        $container = new ContainerBuilder();
        $container->register(BlockRegistry::class);
        $container->register('block.banner_title')->addTag('ui.block', [
            'kind' => 'banner_title',
            'label' => 'label.banner_title',
            'form' => 'App\\Form\\BannerTitleType',
            'template' => 'banner_title.html.twig',
            'media_required' => 'true',
        ]);

        (new BlockRegistryPass())->process($container);

        $calls = $container->getDefinition(BlockRegistry::class)->getMethodCalls();
        $this->assertTrue($calls[0][1][12]);
    }

    // media_multi_upload defaults to false and can be explicitly enabled, same boolean-flag parsing as media_required
    public function testProcessParsesMediaMultiUploadFlag(): void
    {
        $container = new ContainerBuilder();
        $container->register(BlockRegistry::class);
        $container->register('block.slider')->addTag('ui.block', [
            'kind' => 'slider',
            'label' => 'label.slider',
            'form' => 'App\\Form\\SliderType',
            'template' => 'slider.html.twig',
            'media_multi_upload' => 'true',
        ]);

        (new BlockRegistryPass())->process($container);

        $calls = $container->getDefinition(BlockRegistry::class)->getMethodCalls();
        $this->assertTrue($calls[0][1][13]);
    }

    // media_types is a comma-separated string in the tag and must be split/trimmed into an array
    public function testProcessSplitsAndTrimsMediaTypes(): void
    {
        $container = new ContainerBuilder();
        $container->register(BlockRegistry::class);
        $container->register('block.slider')->addTag('ui.block', [
            'kind' => 'slider',
            'label' => 'label.slider',
            'form' => 'App\\Form\\SliderType',
            'template' => 'slider.html.twig',
            'media_types' => 'image/jpeg, image/png,image/webp',
        ]);

        (new BlockRegistryPass())->process($container);

        $calls = $container->getDefinition(BlockRegistry::class)->getMethodCalls();
        $this->assertSame(['image/jpeg', 'image/png', 'image/webp'], $calls[0][1][5]);
    }

    // pickable/cacheable default to true unless explicitly disabled
    public function testProcessParsesPickableAndCacheableFlags(): void
    {
        $container = new ContainerBuilder();
        $container->register(BlockRegistry::class);
        $container->register('block.social_links')->addTag('ui.block', [
            'kind' => 'social_links',
            'label' => 'label.social_links',
            'form' => 'App\\Form\\SocialLinksType',
            'template' => 'social_links.html.twig',
            'pickable' => 'false',
            'cacheable' => 'false',
        ]);

        (new BlockRegistryPass())->process($container);

        $calls = $container->getDefinition(BlockRegistry::class)->getMethodCalls();
        $this->assertFalse($calls[0][1][8]);
        $this->assertFalse($calls[0][1][10]);
    }

    // contexts is a comma-separated string in the tag and must be split/trimmed into an array, same as media_types
    public function testProcessSplitsAndTrimsContexts(): void
    {
        $container = new ContainerBuilder();
        $container->register(BlockRegistry::class);
        $container->register('block.link')->addTag('ui.block', [
            'kind' => 'link',
            'label' => 'label.link',
            'form' => 'App\\Form\\LinkType',
            'template' => 'link.html.twig',
            'contexts' => 'menu, sidebar',
        ]);

        (new BlockRegistryPass())->process($container);

        $calls = $container->getDefinition(BlockRegistry::class)->getMethodCalls();
        $this->assertSame(['menu', 'sidebar'], $calls[0][1][11]);
    }

    public function testProcessThrowsWhenARequiredAttributeIsMissing(): void
    {
        $container = new ContainerBuilder();
        $container->register(BlockRegistry::class);
        $container->register('block.broken')->addTag('ui.block', [
            'kind' => 'broken',
            'label' => 'label.broken',
            'form' => 'App\\Form\\BrokenType',
            // "template" missing
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('block.broken');

        (new BlockRegistryPass())->process($container);
    }
}

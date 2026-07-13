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
use c975L\UiBundle\Registry\BlockRegistry;
use c975L\UiBundle\Service\BlockCacheInvalidator;
use c975L\UiBundle\Twig\BlockExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Twig\Environment;

class BlockExtensionTest extends TestCase
{
    private function createBlock(string $kind, ?int $id = 1): Block
    {
        $block = new Block();
        $block->setKind($kind);
        $block->setData(['title' => 'Hello']);
        if (null !== $id) {
            (new \ReflectionProperty(Block::class, 'id'))->setValue($block, $id);
        }

        return $block;
    }

    // Non-cacheable kinds (e.g. embedding a form with its own CSRF token) must render fresh every time,
    // bypassing the cache pool entirely
    public function testRenderBlockRendersDirectlyWithoutCachingWhenKindIsNotCacheable(): void
    {
        $block = $this->createBlock('contact_form');

        $registry = $this->createMock(BlockRegistry::class);
        $registry->expects($this->once())->method('isCacheable')->with('contact_form')->willReturn(false);
        $registry->expects($this->once())->method('getTemplate')->with('contact_form')->willReturn('contact.html.twig');

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('contact.html.twig', ['block' => $block, 'title' => 'Hello'])
            ->willReturn('<p>rendered</p>');

        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects($this->never())->method('get');

        $extension = new BlockExtension($registry, $twig, $cache, new RequestStack());

        $this->assertSame('<p>rendered</p>', $extension->renderBlock($block));
    }

    // A never-persisted block (e.g. BlockGalleryController's in-memory previews) has no id - caching it
    // would collapse onto the same key as every other unpersisted block of a cacheable kind, silently
    // serving one block's rendered HTML for every other one
    public function testRenderBlockRendersDirectlyWithoutCachingWhenBlockHasNoId(): void
    {
        $block = $this->createBlock('article', null);

        $registry = $this->createMock(BlockRegistry::class);
        $registry->method('isCacheable')->willReturn(true);
        $registry->expects($this->once())->method('getTemplate')->with('article')->willReturn('article.html.twig');

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('article.html.twig', ['block' => $block, 'title' => 'Hello'])
            ->willReturn('<article>fresh</article>');

        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects($this->never())->method('get');

        $extension = new BlockExtension($registry, $twig, $cache, new RequestStack());

        $this->assertSame('<article>fresh</article>', $extension->renderBlock($block));
    }

    // Cacheable kinds go through the cache pool, keyed by block id and current locale
    public function testRenderBlockUsesCacheForCacheableKind(): void
    {
        $block = $this->createBlock('article', 42);

        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('isCacheable')->willReturn(true);
        $registry->method('getTemplate')->willReturn('article.html.twig');

        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('<article>cached content</article>');

        $requestStack = new RequestStack();
        $request = Request::create('/');
        $request->setLocale('en');
        $requestStack->push($request);

        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->once())->method('tag')->with(['block_42', BlockCacheInvalidator::CACHE_TAG_ALL]);

        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('block_render_42_en', $this->isCallable())
            ->willReturnCallback(function (string $key, callable $callback) use ($item) {
                return $callback($item);
            });

        $extension = new BlockExtension($registry, $twig, $cache, $requestStack);

        $this->assertSame('<article>cached content</article>', $extension->renderBlock($block));
    }

    // Without a current request (e.g. CLI/message consumer context), the cache key falls back to "fr"
    public function testRenderBlockFallsBackToFrenchLocaleWhenNoCurrentRequest(): void
    {
        $block = $this->createBlock('article', 7);

        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('isCacheable')->willReturn(true);
        $registry->method('getTemplate')->willReturn('article.html.twig');

        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('content');

        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('block_render_7_fr', $this->anything())
            ->willReturn('content');

        $extension = new BlockExtension($registry, $twig, $cache, new RequestStack());

        $extension->renderBlock($block);
    }

    public function testGetFunctionsRegistersRenderBlockAsHtmlSafe(): void
    {
        $extension = new BlockExtension(
            $this->createStub(BlockRegistry::class),
            $this->createStub(Environment::class),
            $this->createStub(TagAwareCacheInterface::class),
            new RequestStack()
        );
        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('render_block', $functions[0]->getName());
        $this->assertSame(['html'], $functions[0]->getSafe(new \Twig\Node\Node()));
    }
}

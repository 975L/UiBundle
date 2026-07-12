<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Twig;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Registry\BlockRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Twig\Extension\AbstractExtension;
use Twig\Environment;
use Twig\TwigFunction;


class BlockExtension extends AbstractExtension
{
    public function __construct(
        private BlockRegistry $registry,
        private Environment $twig,
        private TagAwareCacheInterface $cache,
        private RequestStack $requestStack
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_block', [$this, 'renderBlock'], ['is_safe' => ['html']]),
        ];
    }

    public function renderBlock(Block $block): string
    {
        $kind = $block->getKind();

        if (!$this->registry->isCacheable($kind)) {
            return $this->doRender($block);
        }

        // Locale is part of the key: some templates (e.g. legal_model) render different
        // content per app.request.locale, not just per Block::$data
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'fr';

        return $this->cache->get(
            sprintf('block_render_%d_%s', $block->getId(), $locale),
            function (ItemInterface $item) use ($block): string {
                $item->expiresAfter(null);
                $item->tag('block_' . $block->getId());

                return $this->doRender($block);
            }
        );
    }

    private function doRender(Block $block): string
    {
        return $this->twig->render(
            $this->registry->getTemplate($block->getKind()),
            ['block' => $block] + $block->getData()
        );
    }
}
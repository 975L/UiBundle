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
use Twig\Extension\AbstractExtension;
use Twig\Environment;
use Twig\TwigFunction;


class BlockExtension extends AbstractExtension
{
    public function __construct(
        private BlockRegistry $registry,
        private Environment $twig
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_block', [$this, 'renderBlock'], ['is_safe' => ['html']]),
        ];
    }

    public function renderBlock(Block $block): string
    {
        return $this->twig->render(
            $this->registry->getTemplate($block->getKind()),
            ['block' => $block] + $block->getData()
        );
    }
}
<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Entity\Trait;

use c975L\UiBundle\Entity\Block;
use Doctrine\Common\Collections\Collection;

// Provides $blocks collection methods for entities that own blocks (see Readme)
trait HasBlocksTrait
{
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(Block $block): static
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
            // Set the owning-side FK (e.g. setPage, setProduct…) so Doctrine writes the correct FK column
            $parts = explode('\\', get_class($this));
            $setter = 'set' . end($parts);
            if (method_exists($block, $setter)) {
                $block->{$setter}($this);
            }
        }

        return $this;
    }

    public function removeBlock(Block $block): static
    {
        $this->blocks->removeElement($block);

        return $this;
    }

    public function reorderBlocks(): void
    {
        $position = 0;
        foreach ($this->blocks as $block) {
            $block->setPosition($position++);
        }
    }
}

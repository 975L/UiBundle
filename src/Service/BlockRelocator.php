<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Service;

use c975L\UiBundle\Contract\HasBlocksInterface;
use c975L\UiBundle\Entity\Block;

// Moves an already-persisted Block to a different collection (the top-level "blocks" of a
// HasBlocksInterface owner, or a container Block's own "slots"), keeping its id (and cascaded Medias)
// stable - see BlockMoveController for the validation (ownership, cycles, nesting context) surrounding
// this. Never calls HasBlocksInterface::removeBlock(): that queues the block for deletion regardless of
// it being re-attached elsewhere in the same flush (see BlockRemovalListener) - detachBlock() is the
// move-safe equivalent. Doesn't flush - the caller controls the transaction.
class BlockRelocator
{
    public function relocate(Block $block, HasBlocksInterface $owner, ?Block $targetContainer): void
    {
        $currentParent = $block->getParentBlock();
        if (null !== $currentParent) {
            $currentParent->removeSlot($block);
            $currentParent->reorderSlots();
        } else {
            $owner->detachBlock($block);
            $owner->reorderBlocks();
        }

        if (null !== $targetContainer) {
            $targetContainer->addSlot($block);
            $block->setPosition($targetContainer->getSlots()->count() - 1);
        } else {
            $owner->addBlock($block);
            $block->setPosition($owner->getBlocks()->count() - 1);
        }
    }
}

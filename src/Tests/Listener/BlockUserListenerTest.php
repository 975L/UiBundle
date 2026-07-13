<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Listener;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Entity\Media;
use c975L\UiBundle\Listener\BlockUserListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

// App\Entity\User (the type BlockUserListener actually assigns) belongs to the consuming
// application, not to this standalone bundle checkout - so only the branches reachable without
// constructing one are covered here (no logged-in App\Entity\User available to test the happy path)
class BlockUserListenerTest extends TestCase
{
    private function createArgs(object $entity): PrePersistEventArgs
    {
        return new PrePersistEventArgs($entity, $this->createStub(ObjectManager::class));
    }

    public function testPrePersistIgnoresEntitiesThatAreNotBlockOrMedia(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('getUser');

        (new BlockUserListener($security))->prePersist($this->createArgs(new \stdClass()));

        $this->addToAssertionCount(1);
    }

    public function testPrePersistLeavesUserNullWhenNobodyIsLoggedInForMedia(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $media = new Media();

        (new BlockUserListener($security))->prePersist($this->createArgs($media));

        $this->assertNull($media->getUser());
    }

    public function testPrePersistLeavesUserNullWhenNobodyIsLoggedInForBlock(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $block = new Block();

        (new BlockUserListener($security))->prePersist($this->createArgs($block));

        $this->assertNull($block->getUser());
    }
}

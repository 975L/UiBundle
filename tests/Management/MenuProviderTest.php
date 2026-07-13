<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Tests\Management;

use c975L\UiBundle\Controller\Management\BlockGalleryController;
use c975L\UiBundle\Management\MenuProvider;
use PHPUnit\Framework\TestCase;

class MenuProviderTest extends TestCase
{
    // Shared with ConfigBundle's and SiteBundle's own MenuProvider so all three merge into one section
    public function testGetMenuSectionMatchesTheSharedManagementSection(): void
    {
        $provider = new MenuProvider();

        $this->assertSame(['label' => 'label.management', 'translation_domain' => 'site'], $provider->getMenuSection());
    }

    // UiBundle's own CRUD entries (Media Library) stay declared from SiteBundle - see its MenuProvider
    public function testGetMenusReturnsNoCrudEntries(): void
    {
        $provider = new MenuProvider();

        $this->assertSame([], $provider->getMenus());
    }

    public function testGetLinksReturnsTheBlockGalleryLink(): void
    {
        $provider = new MenuProvider();

        $links = $provider->getLinks();

        $this->assertCount(1, $links);
        $this->assertSame('label.block_gallery', $links['block_gallery']['label']);
        $this->assertSame('ui', $links['block_gallery']['translation_domain']);
        $this->assertSame(BlockGalleryController::GALLERY_ROUTE, $links['block_gallery']['name']);
    }
}

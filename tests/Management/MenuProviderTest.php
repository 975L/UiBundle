<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Tests\Management;

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

    // The old self-hosted EasyAdmin block gallery was removed (its preview iframes needed inline
    // scripts, which a hash/nonce-based CSP like nelmio_security can never authorize inside an <iframe
    // srcdoc> - see MenuProvider::getLinks()'s comment). This link replaces it: a fixed 'url' (not a
    // route name), since it always points at 975l.com's own /vitrine-blocks - the c975L ecosystem's
    // canonical demo of every bundle's block kinds - regardless of which app's dashboard shows it.
    public function testGetLinksReturnsTheBlockShowcaseLink(): void
    {
        $provider = new MenuProvider();

        $links = $provider->getLinks();

        $this->assertCount(1, $links);
        $this->assertSame('label.block_showcase', $links['block_showcase']['label']);
        $this->assertSame('ui', $links['block_showcase']['translation_domain']);
        $this->assertSame('https://975l.com/vitrine-blocks', $links['block_showcase']['url']);
        $this->assertSame('_blank', $links['block_showcase']['target']);
    }
}

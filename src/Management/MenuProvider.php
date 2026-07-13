<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Management;

use c975L\ConfigBundle\Management\MenuProviderInterface;
use c975L\UiBundle\Controller\Management\BlockGalleryController;

// UiBundle's own CRUD entries (e.g. Media Library) stay declared from SiteBundle - see
// SiteBundle\Management\MenuProvider - but the block gallery is a plain route link, not a CRUD
// controller, and blocks are this bundle's own concern, so it's registered directly here (same
// direct-dependency-on-ConfigBundle precedent as UiShortcutProvider).
class MenuProvider implements MenuProviderInterface
{
    // getMenus() is empty below, so this section never actually renders for this provider - kept
    // identical to ConfigBundle's/SiteBundle's own value only so a future CRUD entry added here
    // merges into the same "Management" group instead of creating a duplicate one
    public function getMenuSection(): array
    {
        return [
            'label' => 'label.management',
            'translation_domain' => 'site',
        ];
    }

    public function getMenus(): array
    {
        return [];
    }

    // Unlike getMenus(), every provider's getLinks() is merged into a single shared "Links" sidebar
    // section (see MenuBuilder::getMenuItems()) - that's where this shows up, not under "Management"
    public function getLinks(): array
    {
        return [
            'block_gallery' => [
                'label' => 'label.block_gallery',
                'translation_domain' => 'ui',
                'icon' => 'fas fa-shapes',
                'name' => BlockGalleryController::GALLERY_ROUTE,
            ],
        ];
    }
}

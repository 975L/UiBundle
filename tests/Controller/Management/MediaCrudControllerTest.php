<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Controller\Management;

use c975L\UiBundle\Controller\Management\MediaCrudController;
use c975L\UiBundle\Registry\MediaUsageRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use PHPUnit\Framework\TestCase;

class MediaCrudControllerTest extends TestCase
{
    // Creating a Media with no Block (e.g. for a bundle showcase) is reserved to super admins -
    // regular admins keep adding media the normal way, through a Block's own form
    public function testConfigureActionsRestrictsNewToSuperAdmin(): void
    {
        $controller = new MediaCrudController($this->createStub(MediaUsageRegistry::class));

        $actions = $controller->configureActions(
            Actions::new()
                ->add(Crud::PAGE_INDEX, Action::EDIT)
                ->add(Crud::PAGE_INDEX, Action::DELETE)
        );

        $permissions = $actions->getAsDto(null)->getActionPermissions();
        $this->assertSame('ROLE_SUPER_ADMIN', $permissions[Action::NEW]);
    }
}

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
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaCrudControllerTest extends TestCase
{
    // Creating a Media with no Block (e.g. for a bundle showcase) is reserved to super admins - regular admins keep adding media the normal way, through a Block's own form
    public function testConfigureActionsRestrictsNewToSuperAdmin(): void
    {
        // Known issue: local vendor/c975l/config-bundle install is broken (doesn't contain real ConfigBundle source), so EasyAdminActionHelper can't autoload here - skip until bundles are committed/pushed and composer can pull the real package again
        if (!class_exists(\c975L\ConfigBundle\Management\EasyAdminActionHelper::class)) {
            self::markTestSkipped('c975L\ConfigBundle\Management\EasyAdminActionHelper not available (vendor/c975l/config-bundle install is broken)');
        }

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $controller = new MediaCrudController($this->createStub(MediaUsageRegistry::class), $translator);

        // A real EasyAdmin runtime pre-populates default actions (EDIT, DELETE...) before calling configureActions() - update() below assumes EDIT/DELETE already exist on PAGE_INDEX (DETAIL is added by the controller itself, not pre-populated here)
        $actions = $controller->configureActions(
            Actions::new()
                ->add(Crud::PAGE_INDEX, Action::EDIT)
                ->add(Crud::PAGE_INDEX, Action::DELETE)
        );

        $permissions = $actions->getAsDto(null)->getActionPermissions();
        $this->assertSame('ROLE_SUPER_ADMIN', $permissions[Action::NEW]);
    }
}

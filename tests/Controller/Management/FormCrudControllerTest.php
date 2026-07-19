<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Controller\Management;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\UiBundle\Controller\Management\FormCrudController;
use c975L\UiBundle\Entity\Form;
use c975L\UiBundle\Registry\FormActionRegistry;
use c975L\UiBundle\Service\FormFieldNamer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class FormCrudControllerTest extends TestCase
{
    private function createController(): FormCrudController
    {
        $configService = $this->createStub(ConfigServiceInterface::class);
        $configService->method('get')->willReturn('ROLE_ADMIN');

        return new FormCrudController(
            $configService,
            $this->createStub(FormFieldNamer::class),
            $this->createStub(FormActionRegistry::class),
            new AdminContextProvider(new RequestStack()),
        );
    }

    public function testGetEntityFqcnReturnsForm(): void
    {
        $this->assertSame(Form::class, FormCrudController::getEntityFqcn());
    }

    public function testConfigureActionsGrantsEveryActionToTheAdminRole(): void
    {
        $controller = $this->createController();

        // A real EasyAdmin runtime pre-populates default actions before calling configureActions()
        $actions = $controller->configureActions(
            Actions::new()
                ->add(Crud::PAGE_INDEX, Action::EDIT)
                ->add(Crud::PAGE_INDEX, Action::DELETE)
        );

        $permissions = $actions->getAsDto(null)->getActionPermissions();
        $this->assertSame('ROLE_ADMIN', $permissions[Action::INDEX]);
        $this->assertSame('ROLE_ADMIN', $permissions[Action::NEW]);
        $this->assertSame('ROLE_ADMIN', $permissions[Action::EDIT]);
        $this->assertSame('ROLE_ADMIN', $permissions[Action::DELETE]);
        $this->assertSame('ROLE_ADMIN', $permissions[Action::DETAIL]);
    }

    public function testConfigureActionsHidesDeleteForARestrictedForm(): void
    {
        $controller = $this->createController();

        $actions = $controller->configureActions(
            Actions::new()
                ->add(Crud::PAGE_INDEX, Action::EDIT)
                ->add(Crud::PAGE_INDEX, Action::DELETE)
        )->getAsDto(null);

        $deleteAction = $actions->getActions()[Crud::PAGE_INDEX][Action::DELETE];

        $this->assertNotNull($deleteAction);
        // No public getter for the display callable - read the private property directly
        $reflection = new \ReflectionProperty($deleteAction, 'displayCallable');
        $displayCallable = $reflection->getValue($deleteAction);

        $this->assertFalse($displayCallable((new Form())->setRestricted(true)));
        $this->assertTrue($displayCallable((new Form())->setRestricted(false)));
    }
}

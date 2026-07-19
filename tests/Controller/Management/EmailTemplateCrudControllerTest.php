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
use c975L\UiBundle\Controller\Management\EmailTemplateCrudController;
use c975L\UiBundle\Controller\Management\FormFieldTemplateCrudController;
use c975L\UiBundle\Entity\EmailTemplate;
use c975L\UiBundle\Service\EmailTemplateRenderer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailTemplateCrudControllerTest extends TestCase
{
    private function createController(?AdminUrlGeneratorInterface $adminUrlGenerator = null): EmailTemplateCrudController
    {
        $configService = $this->createStub(ConfigServiceInterface::class);
        $configService->method('get')->willReturn('ROLE_ADMIN');

        return new EmailTemplateCrudController(
            $configService,
            new AdminContextProvider(new RequestStack()),
            $this->createStub(EmailTemplateRenderer::class),
            $adminUrlGenerator ?? $this->createStub(AdminUrlGeneratorInterface::class),
        );
    }

    public function testGetEntityFqcnReturnsEmailTemplate(): void
    {
        $this->assertSame(EmailTemplate::class, EmailTemplateCrudController::getEntityFqcn());
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
        $this->assertSame('ROLE_ADMIN', $permissions['formFieldTemplates']);
    }

    public function testConfigureActionsHidesDeleteForARestrictedTemplate(): void
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

        $this->assertFalse($displayCallable((new EmailTemplate())->setRestricted(true)));
        $this->assertTrue($displayCallable((new EmailTemplate())->setRestricted(false)));
    }

    // The index page's own global button must point at FormFieldTemplateCrudController, not a sidebar menu entry (see ChangeLog)
    public function testConfigureActionsAddsAGlobalButtonLinkingToFormFieldTemplates(): void
    {
        $urlGenerator = $this->createMock(AdminUrlGeneratorInterface::class);
        $urlGenerator->method('unsetAll')->willReturnSelf();
        $urlGenerator->expects($this->once())->method('setController')->with(FormFieldTemplateCrudController::class)->willReturnSelf();
        $urlGenerator->method('generateUrl')->willReturn('/management/form-field-template');

        $controller = $this->createController($urlGenerator);

        $actions = $controller->configureActions(
            Actions::new()->add(Crud::PAGE_INDEX, Action::EDIT)->add(Crud::PAGE_INDEX, Action::DELETE)
        )->getAsDto(null);

        $action = $actions->getActions()[Crud::PAGE_INDEX]['formFieldTemplates'];

        $this->assertNotNull($action);
        $this->assertSame('/management/form-field-template', $action->getUrl());
    }
}

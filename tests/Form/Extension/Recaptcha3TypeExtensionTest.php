<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Form\Extension;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\UiBundle\Form\Extension\Recaptcha3TypeExtension;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class Recaptcha3TypeExtensionTest extends TestCase
{
    public function testGetExtendedTypesTargetsRecaptcha3Type(): void
    {
        $this->assertSame([Recaptcha3Type::class], Recaptcha3TypeExtension::getExtendedTypes());
    }

    public function testBuildViewSetsSiteKeyWhenConfigured(): void
    {
        $configService = $this->createStub(ConfigServiceInterface::class);
        $configService->method('hasParameter')->willReturnCallback(static fn (string $parameter) => 'recaptcha3-site-key' === $parameter);
        $configService->method('get')->willReturnCallback(static fn (string $parameter) => 'recaptcha3-site-key' === $parameter ? 'site-key-value' : null);

        $view = new FormView();
        (new Recaptcha3TypeExtension($configService))->buildView($view, $this->createStub(FormInterface::class), []);

        $this->assertSame('site-key-value', $view->vars['site_key']);
    }

    public function testBuildViewLeavesSiteKeyUnsetWhenConfigServiceHasNoParameter(): void
    {
        $configService = $this->createStub(ConfigServiceInterface::class);
        $configService->method('hasParameter')->willReturn(false);

        $view = new FormView();
        (new Recaptcha3TypeExtension($configService))->buildView($view, $this->createStub(FormInterface::class), []);

        $this->assertArrayNotHasKey('site_key', $view->vars);
    }

    public function testBuildViewLeavesSiteKeyUnsetWhenValueIsEmpty(): void
    {
        $configService = $this->createStub(ConfigServiceInterface::class);
        $configService->method('hasParameter')->willReturn(true);
        $configService->method('get')->willReturn('');

        $view = new FormView();
        (new Recaptcha3TypeExtension($configService))->buildView($view, $this->createStub(FormInterface::class), []);

        $this->assertArrayNotHasKey('site_key', $view->vars);
    }
}

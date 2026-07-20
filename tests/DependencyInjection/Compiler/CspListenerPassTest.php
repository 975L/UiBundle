<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\DependencyInjection\Compiler;

use c975L\UiBundle\DependencyInjection\Compiler\CspListenerPass;
use Nelmio\SecurityBundle\EventListener\ContentSecurityPolicyListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CspListenerPassTest extends TestCase
{
    public function testProcessDoesNothingWhenNelmioSecurityBundleIsNotRegistered(): void
    {
        $container = new ContainerBuilder();

        (new CspListenerPass())->process($container);

        $this->assertFalse($container->hasAlias(ContentSecurityPolicyListener::class));
    }

    public function testProcessAliasesTheListenerWhenBundleIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->register('nelmio_security.csp_listener');

        (new CspListenerPass())->process($container);

        $this->assertTrue($container->hasAlias(ContentSecurityPolicyListener::class));
        $this->assertSame('nelmio_security.csp_listener', (string) $container->getAlias(ContentSecurityPolicyListener::class));
    }
}

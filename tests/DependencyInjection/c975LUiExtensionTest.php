<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\DependencyInjection;

use c975L\UiBundle\DependencyInjection\c975LUiExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// load() is currently a no-op (services are registered via c975LUiBundle::loadExtension() importing
// config/services.yaml directly) - this only guards against it throwing or mutating the container
class c975LUiExtensionTest extends TestCase
{
    public function testLoadDoesNotThrowAndLeavesContainerUntouched(): void
    {
        $container = new ContainerBuilder();
        $definitionsBefore = array_keys($container->getDefinitions());
        $parametersBefore = $container->getParameterBag()->all();

        $extension = new c975LUiExtension();
        $extension->load([], $container);

        $this->assertSame($definitionsBefore, array_keys($container->getDefinitions()));
        $this->assertSame($parametersBefore, $container->getParameterBag()->all());
    }

    public function testGetAliasIsDerivedFromClassName(): void
    {
        $extension = new c975LUiExtension();

        $this->assertSame('c975_l_ui', $extension->getAlias());
    }
}

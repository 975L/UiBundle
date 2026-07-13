<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\DependencyInjection\Compiler;

use c975L\UiBundle\Contract\GalleryShowcaseProviderInterface;
use c975L\UiBundle\Registry\GalleryShowcaseRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GalleryShowcaseProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(GalleryShowcaseRegistry::class)) {
            return;
        }

        $registry = $container->getDefinition(GalleryShowcaseRegistry::class);

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (!$class) {
                continue;
            }

            try {
                // Some vendor services reference classes whose interfaces come from require-dev-only
                // packages, not installed in prod (--no-dev) - see WhatsNewProviderPass for the same guard
                if (is_subclass_of($class, GalleryShowcaseProviderInterface::class)) {
                    $registry->addMethodCall('addProvider', [new Reference($id)]);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
    }
}

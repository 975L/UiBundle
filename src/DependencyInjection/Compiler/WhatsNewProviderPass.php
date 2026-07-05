<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\DependencyInjection\Compiler;

use c975L\UiBundle\Contract\BundleWhatsNewProviderInterface;
use c975L\UiBundle\Registry\WhatsNewRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WhatsNewProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(WhatsNewRegistry::class)) {
            return;
        }

        $registry = $container->getDefinition(WhatsNewRegistry::class);

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (!$class) {
                continue;
            }

            try {
                // Some vendor services (e.g. Symfony's translation extractor visitors)
                // reference classes whose interfaces come from require-dev-only packages
                // (e.g. nikic/php-parser), which aren't installed in prod (--no-dev)
                if (is_subclass_of($class, BundleWhatsNewProviderInterface::class)) {
                    $registry->addMethodCall('addProvider', [new Reference($id)]);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
    }
}

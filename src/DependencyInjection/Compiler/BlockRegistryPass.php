<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\DependencyInjection\Compiler;

use c975L\UiBundle\Registry\BlockRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BlockRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(BlockRegistry::class)) {
            return;
        }

        $registry = $container->getDefinition(BlockRegistry::class);

        // Gets all services tagged with "ui.block" and registers them in the BlockRegistry
        foreach ($container->findTaggedServiceIds('ui.block') as $id => $tags) {
            foreach ($tags as $tag) {
                $this->validateTag($tag, $id);

                $mediaTypes = [];
                if (!empty($tag['media_types'])) {
                    $mediaTypes = array_map('trim', explode(',', (string) $tag['media_types']));
                }

                $registry->addMethodCall('register', [
                    $tag['kind'],
                    $tag['label'],
                    $tag['form'],
                    $tag['template'],
                    $tag['category'] ?? 'label.category_general',
                    $mediaTypes,
                    $tag['translation_domain'] ?? 'ui',
                    $tag['description'] ?? '',
                ]);
            }
        }
    }

    private function validateTag(array $tag, string $serviceId): void
    {
        foreach (['kind', 'label', 'form', 'template'] as $required) {
            if (empty($tag[$required])) {
                throw new \InvalidArgumentException(sprintf(
                    'The tag "ui.block" of the service "%s" is incomplete. Missing attribute: "%s".',
                    $serviceId,
                    $required
                ));
            }
        }
    }
}
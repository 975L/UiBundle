<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle;

use c975L\UiBundle\DependencyInjection\Compiler\BlockRegistryPass;
use c975L\UiBundle\Namer\UiMediaNamer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class c975LUiBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new BlockRegistryPass());
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets' => '@c975l/ui-bundle'
                ],
            ],
        ]);

        $container->prependExtensionConfig('twig', [
            'form_themes' => ['@c975LUi/form/icon_picker_theme.html.twig'],
        ]);

        if ($container->hasExtension('vich_uploader')) {
            $container->prependExtensionConfig('vich_uploader', [
                'mappings' => [
                    'block_media' => [
                        'uri_prefix' => '',
                        'upload_destination' => '%kernel.project_dir%/public/medias/site',
                        'namer' => UiMediaNamer::class,
                        'inject_on_load'   => false,
                        'delete_on_update' => true,
                        'delete_on_remove' => true,
                    ],
                ],
            ]);
        }
    }

    public function loadExtension(array $config, ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void
    {
        $containerConfigurator->import('../config/services.yaml');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}

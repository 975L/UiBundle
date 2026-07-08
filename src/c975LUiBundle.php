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
use c975L\UiBundle\DependencyInjection\Compiler\MediaUsageProviderPass;
use c975L\UiBundle\DependencyInjection\Compiler\ScriptAdminRegistryPass;
use c975L\UiBundle\DependencyInjection\Compiler\ScriptRegistryPass;
use c975L\UiBundle\DependencyInjection\Compiler\StylesheetManagementRegistryPass;
use c975L\UiBundle\DependencyInjection\Compiler\StylesheetRegistryPass;
use c975L\UiBundle\DependencyInjection\Compiler\WhatsNewProviderPass;
use c975L\UiBundle\Namer\UiMediaNamer;
use c975L\UiBundle\Storage\NestedFileSystemStorage;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class c975LUiBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new BlockRegistryPass());
        $container->addCompilerPass(new StylesheetRegistryPass());
        $container->addCompilerPass(new StylesheetManagementRegistryPass());
        $container->addCompilerPass(new ScriptRegistryPass());
        $container->addCompilerPass(new ScriptAdminRegistryPass());
        $container->addCompilerPass(new WhatsNewProviderPass());
        $container->addCompilerPass(new MediaUsageProviderPass());
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
            'form_themes' => [
                '@c975LUi/form/block_theme.html.twig',
                '@c975LUi/form/icon_picker_theme.html.twig',
            ],
        ]);

        if ($container->hasExtension('vich_uploader')) {
            $container->prependExtensionConfig('vich_uploader', [
                // Lets namers (see UiMediaNamer/getVichMediaPath) return a path with subdirectories
                // (e.g. "medias/site/block-article-42-xxx.webp") that is both the value stored in
                // "filename" and the file's real location on disk - Vich's own storage silently
                // flattens such paths on upload (see NestedFileSystemStorage for why).
                'storage' => '@' . NestedFileSystemStorage::class,
                'mappings' => [
                    'block_media' => [
                        'uri_prefix' => '',
                        'upload_destination' => '%kernel.project_dir%/public',
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

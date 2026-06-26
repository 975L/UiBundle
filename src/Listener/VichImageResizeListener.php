<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Listener;

use SplFileInfo;
use Imagine\Image\Box;
use Imagine\Gd\Imagine;
use Vich\UploaderBundle\Event\Event;
use c975L\UiBundle\Contract\VichPrivateFileInterface;
use c975L\UiBundle\Contract\VichImageResizableInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsEventListener(event: 'vich_uploader.post_upload', method: 'onPostUpload')]
class VichImageResizeListener
{
    private Filesystem $filesystem;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function onPostUpload(Event $event): void
    {
        $entity = $event->getObject();
        $mapping = $event->getMapping();
        $filename = $mapping->getFileName($entity);
        $absolutePath = $this->parameterBag->get('kernel.project_dir') . '/public/' . $filename;

        if (!$this->filesystem->exists($absolutePath)) {
            return;
        }

        if ($entity instanceof VichImageResizableInterface) {
            $extension = $entity->getFile()->getExtension();

            if (in_array($extension, ['jpg', 'png', 'gif', 'webp'])) {
                $this->processImage($entity, $absolutePath);
            }

            return;
        }

        if ($entity instanceof VichPrivateFileInterface) {
            $this->moveFileToPrivate($entity, $filename, $absolutePath);
        }
    }

    private function processImage(VichImageResizableInterface $entity, string $absolutePath): void
    {
        $width = $entity->getImageWidth();
        $imagine = new Imagine();
        $media = $imagine->open($absolutePath);
        $size = $media->getSize();
        $height = (int) ($size->getHeight() * $width / $size->getWidth());

        $media
            ->resize(new Box($width, $height))
            ->save($absolutePath, [
                'format' => 'webp',
                'webp_quality' => 90,
            ]);

        if (method_exists($entity, 'setSize')) {
            $entity->setSize((new SplFileInfo($absolutePath))->getSize());
        }
    }

    private function moveFileToPrivate(VichPrivateFileInterface $entity, string $filename, string $publicPath): void
    {
        $privatePath = $this->parameterBag->get('kernel.project_dir') . '/' . $entity->getPrivateDirectory() . '/' . $filename;

        $this->filesystem->mkdir(dirname($privatePath), 0755);
        $this->filesystem->copy($publicPath, $privatePath, true);
        $this->filesystem->remove($publicPath);
    }
}

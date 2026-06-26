<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Namer;

use RuntimeException;
use c975L\UiBundle\Contract\VichMediaNamableInterface;
use Symfony\Component\Filesystem\Filesystem;
use Vich\UploaderBundle\Naming\NamerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class UiMediaNamer implements NamerInterface
{
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function name($entity, PropertyMapping $mapping): string
    {
        if (!$entity instanceof VichMediaNamableInterface) {
            throw new RuntimeException(sprintf('Entity "%s" must implement VichMediaNamableInterface.', get_class($entity)));
        }

        $filePath = $entity->getFile()->getPathname();
        if (!$this->filesystem->exists($filePath)) {
            throw new RuntimeException('File not found: ' . htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8'));
        }

        $file = $mapping->getFile($entity);
        $extension = $this->determineExtension($file);

        return $entity->getVichMediaPath() . '-' . uniqid() . '.' . $extension;
    }

    private function determineExtension(File $file): string
    {
        $mimeType = $file->getMimeType();
        $extension = $file->getExtension();

        if ('image/jpeg' === $mimeType || 'image/jpg' === $mimeType) {
            $extension = 'jpg';
        } elseif ('image/png' === $mimeType) {
            $extension = 'png';
        } elseif ('image/gif' === $mimeType) {
            $extension = 'gif';
        } elseif ('image/webp' === $mimeType) {
            $extension = 'webp';
        }

        if (in_array($extension, ['jpg', 'png', 'gif', 'webp'])) {
            return 'webp';
        }

        return $extension ?: $file->getClientOriginalExtension();
    }
}

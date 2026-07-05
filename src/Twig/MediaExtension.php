<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Twig;

use c975L\UiBundle\Entity\Media;
use c975L\UiBundle\Repository\MediaRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MediaExtension extends AbstractExtension
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_media', [$this, 'getSiteMedia']),
        ];
    }

    public function getSiteMedia(string $role): ?Media
    {
        return $this->mediaRepository->findOneByRole($role);
    }
}

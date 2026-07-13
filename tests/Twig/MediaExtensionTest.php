<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Twig;

use c975L\UiBundle\Entity\Media;
use c975L\UiBundle\Repository\MediaRepository;
use c975L\UiBundle\Twig\MediaExtension;
use PHPUnit\Framework\TestCase;

class MediaExtensionTest extends TestCase
{
    public function testGetSiteMediaDelegatesToRepositoryFindOneByRole(): void
    {
        $media = new Media();
        $repository = $this->createMock(MediaRepository::class);
        $repository->expects($this->once())->method('findOneByRole')->with('logo')->willReturn($media);

        $extension = new MediaExtension($repository);

        $this->assertSame($media, $extension->getSiteMedia('logo'));
    }

    public function testGetSiteMediaReturnsNullWhenRepositoryFindsNothing(): void
    {
        $repository = $this->createStub(MediaRepository::class);
        $repository->method('findOneByRole')->willReturn(null);

        $extension = new MediaExtension($repository);

        $this->assertNull($extension->getSiteMedia('missing-role'));
    }

    public function testGetRandomSiteMediaDelegatesToRepositoryFindRandomByRole(): void
    {
        $media = new Media();
        $repository = $this->createMock(MediaRepository::class);
        $repository->expects($this->once())->method('findRandomByRole')->with('error-image')->willReturn($media);

        $extension = new MediaExtension($repository);

        $this->assertSame($media, $extension->getRandomSiteMedia('error-image'));
    }

    public function testGetFunctionsRegistersSiteMediaFunctions(): void
    {
        $extension = new MediaExtension($this->createStub(MediaRepository::class));
        $names = array_map(fn ($function) => $function->getName(), $extension->getFunctions());

        $this->assertSame(['site_media', 'site_random_media'], $names);
    }
}

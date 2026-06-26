<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Service;

interface IconServiceInterface
{
    // This method returns an array of icons, where the keys are the icon names and the values are the paths to the icon files.
    public function getIcons(): array;
}

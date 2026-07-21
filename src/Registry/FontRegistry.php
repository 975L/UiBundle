<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Registry;

use c975L\UiBundle\Contract\FontProviderInterface;

class FontRegistry
{
    /** @var FontProviderInterface[] */
    private array $providers = [];

    public function addProvider(FontProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    // Only one app-wide font source is expected - the first registered provider wins; an empty array means
    // no provider is installed (e.g. an app with no SiteBundle), letting the caller fall back on its own
    public function getFonts(): array
    {
        return [] === $this->providers ? [] : $this->providers[0]->getFonts();
    }
}

<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Registry;

use c975L\UiBundle\Contract\EmailLayoutProviderInterface;

class EmailLayoutRegistry
{
    /** @var EmailLayoutProviderInterface[] */
    private array $providers = [];

    public function addProvider(EmailLayoutProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    // Only one app-wide branded layout is expected - the first registered provider wins; null means
    // no provider is installed (e.g. an app with no SiteBundle), letting the caller fall back on its own
    public function wrap(string $bodyHtml): ?string
    {
        return [] === $this->providers ? null : $this->providers[0]->wrap($bodyHtml);
    }
}

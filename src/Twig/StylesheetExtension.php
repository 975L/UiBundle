<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Twig;

use c975L\UiBundle\Registry\StylesheetRegistry;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StylesheetExtension extends AbstractExtension
{
    public function __construct(
        private StylesheetRegistry $registry,
        private Packages $packages,
        private RequestStack $requestStack,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('bundle_stylesheets', [$this, 'getBundleStylesheets']),
        ];
    }

    /** @return string[] */
    public function getBundleStylesheets(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request ? $request->getSchemeAndHttpHost() : '';

        return array_map(
            fn(string $path) => str_starts_with($path, 'http')
                ? $path
                : $baseUrl . $this->packages->getUrl($path),
            $this->registry->all()
        );
    }
}

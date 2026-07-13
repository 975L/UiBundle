<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Twig;

use c975L\UiBundle\Registry\StylesheetRegistry;
use c975L\UiBundle\Twig\StylesheetExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class StylesheetExtensionTest extends TestCase
{
    private function createRequestStack(?Request $request): RequestStack
    {
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        return $requestStack;
    }

    // Relative paths are resolved through Packages::getUrl() and prefixed with the current request's
    // scheme+host, so stylesheets keep working behind a CDN/asset-mapper versioned URL
    public function testGetBundleStylesheetsPrefixesRelativePathWithRequestBaseUrl(): void
    {
        $registry = $this->createStub(StylesheetRegistry::class);
        $registry->method('all')->willReturn(['css/styles.min.css']);

        $packages = $this->createMock(Packages::class);
        $packages->expects($this->once())->method('getUrl')->with('css/styles.min.css')->willReturn('/build/css/styles-abc123.min.css');

        $request = Request::create('https://example.com/page');

        $extension = new StylesheetExtension($registry, $packages, $this->createRequestStack($request));

        $this->assertSame(
            ['https://example.com/build/css/styles-abc123.min.css'],
            $extension->getBundleStylesheets()
        );
    }

    // Absolute URLs (CDN resources) are passed through untouched, never run through Packages::getUrl()
    public function testGetBundleStylesheetsKeepsAbsoluteHttpUrlUnchanged(): void
    {
        $registry = $this->createStub(StylesheetRegistry::class);
        $registry->method('all')->willReturn(['https://cdn.example.com/lib.css']);

        $packages = $this->createMock(Packages::class);
        $packages->expects($this->never())->method('getUrl');

        $extension = new StylesheetExtension(
            $registry,
            $packages,
            $this->createRequestStack(Request::create('https://example.com/'))
        );

        $this->assertSame(['https://cdn.example.com/lib.css'], $extension->getBundleStylesheets());
    }

    // Without a current request (e.g. CLI context), the base URL is simply empty
    public function testGetBundleStylesheetsUsesEmptyBaseUrlWhenNoCurrentRequest(): void
    {
        $registry = $this->createStub(StylesheetRegistry::class);
        $registry->method('all')->willReturn(['css/styles.min.css']);

        $packages = $this->createStub(Packages::class);
        $packages->method('getUrl')->willReturn('/css/styles.min.css');

        $extension = new StylesheetExtension($registry, $packages, $this->createRequestStack(null));

        $this->assertSame(['/css/styles.min.css'], $extension->getBundleStylesheets());
    }

    public function testGetFunctionsRegistersBundleStylesheetsFunction(): void
    {
        $extension = new StylesheetExtension(
            $this->createStub(StylesheetRegistry::class),
            $this->createStub(Packages::class),
            $this->createRequestStack(null)
        );
        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('bundle_stylesheets', $functions[0]->getName());
    }
}

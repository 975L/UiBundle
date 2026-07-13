<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Registry;

use c975L\UiBundle\Registry\BlockRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BlockRegistryTest extends TestCase
{
    // Translator stub that echoes the key back, optionally with a locale-ish marker, so assertions
    // can check both which key was looked up and which domain it was resolved in
    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            fn (string $key, array $params, ?string $domain = null) => $key . '[' . $domain . ']'
        );

        return $translator;
    }

    public function testGetThrowsForUnknownKind(): void
    {
        $registry = new BlockRegistry($this->createTranslator());

        $this->expectException(\InvalidArgumentException::class);
        $registry->get('unknown');
    }

    public function testHasReflectsRegisteredKinds(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');

        $this->assertTrue($registry->has('article'));
        $this->assertFalse($registry->has('missing'));
    }

    public function testGetLabelTranslatesUsingDeclaredDomain(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig', translationDomain: 'custom');

        $this->assertSame('label.article[custom]', $registry->getLabel('article'));
    }

    public function testGetDescriptionReturnsEmptyStringWhenNoneDeclared(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');

        $this->assertSame('', $registry->getDescription('article'));
    }

    public function testGetDescriptionTranslatesWhenDeclared(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register(
            'article',
            'label.article',
            ArticleFormStub::class,
            'article.html.twig',
            description: 'label.article_description'
        );

        $this->assertSame('label.article_description[ui]', $registry->getDescription('article'));
    }

    public function testGetFormClassGetTemplateAndGetMediaTypes(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register(
            'video',
            'label.video',
            ArticleFormStub::class,
            'video.html.twig',
            mediaTypes: ['video']
        );

        $this->assertSame(ArticleFormStub::class, $registry->getFormClass('video'));
        $this->assertSame('video.html.twig', $registry->getTemplate('video'));
        $this->assertSame(['video'], $registry->getMediaTypes('video'));
        $this->assertTrue($registry->hasMediaTypes('video'));
    }

    public function testHasMediaTypesIsFalseWhenNoneDeclared(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');

        $this->assertFalse($registry->hasMediaTypes('article'));
    }

    public function testIsMediaRequiredDefaultsToFalse(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');

        $this->assertFalse($registry->isMediaRequired('article'));
    }

    public function testIsMediaRequiredCanBeDeclaredTrue(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register(
            'banner_title',
            'label.banner_title',
            ArticleFormStub::class,
            'banner_title.html.twig',
            mediaTypes: ['image/*'],
            mediaRequired: true
        );

        $this->assertTrue($registry->isMediaRequired('banner_title'));
    }

    public function testAllowsMultiUploadDefaultsToFalse(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');

        $this->assertFalse($registry->allowsMultiUpload('article'));
    }

    public function testAllowsMultiUploadCanBeDeclaredTrue(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register(
            'slider',
            'label.slider',
            ArticleFormStub::class,
            'slider.html.twig',
            mediaTypes: ['image/*', 'video/*'],
            multiUpload: true
        );

        $this->assertTrue($registry->allowsMultiUpload('slider'));
    }

    public function testIsCacheableDefaultsToTrue(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');

        $this->assertTrue($registry->isCacheable('article'));
    }

    public function testIsCacheableCanBeDeclaredFalse(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('contact_form', 'label.contact_form', ArticleFormStub::class, 'contact.html.twig', cacheable: false);

        $this->assertFalse($registry->isCacheable('contact_form'));
    }

    public function testAllReturnsEveryRegisteredBlockConfig(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');
        $registry->register('video', 'label.video', ArticleFormStub::class, 'video.html.twig');

        $this->assertSame(['article', 'video'], array_keys($registry->all()));
    }

    // Non-pickable kinds (singleton blocks with their own dedicated admin entry) must never appear
    public function testGroupedByCategoryExcludesNonPickableKinds(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');
        $registry->register('social_links', 'label.social_links', ArticleFormStub::class, 'social.html.twig', pickable: false);

        $grouped = $registry->groupedByCategory();

        $categories = array_merge(...array_values($grouped));
        $this->assertArrayHasKey('label.article[ui]', $categories);
        $this->assertArrayNotHasKey('label.social_links[ui]', $categories);
    }

    // Within a category, entries are ordered by priority (highest first), then alphabetically by label
    public function testGroupedByCategoryOrdersByPriorityThenLabel(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('zebra', 'label.zebra', ArticleFormStub::class, 'zebra.html.twig', category: 'cat', priority: 0);
        $registry->register('apple', 'label.apple', ArticleFormStub::class, 'apple.html.twig', category: 'cat', priority: 0);
        $registry->register('important', 'label.important', ArticleFormStub::class, 'important.html.twig', category: 'cat', priority: 10);

        $grouped = $registry->groupedByCategory();
        $kinds = array_values($grouped['cat[ui]']);

        $this->assertSame(['important', 'apple', 'zebra'], $kinds);
    }

    // Categories themselves are sorted alphabetically (case-insensitive)
    public function testGroupedByCategorySortsCategoriesAlphabetically(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('a', 'label.a', ArticleFormStub::class, 'a.html.twig', category: 'zeta');
        $registry->register('b', 'label.b', ArticleFormStub::class, 'b.html.twig', category: 'alpha');

        $grouped = $registry->groupedByCategory();

        $this->assertSame(['alpha[ui]', 'zeta[ui]'], array_keys($grouped));
    }

    // The choice label appends the translated description in parentheses when one was declared
    public function testGroupedByCategoryAppendsDescriptionToChoiceLabel(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register(
            'article',
            'label.article',
            ArticleFormStub::class,
            'article.html.twig',
            description: 'label.article_description'
        );

        $grouped = $registry->groupedByCategory();
        $label = array_key_first($grouped['label.category_general[ui]']);

        $this->assertSame('label.article[ui] (label.article_description[ui])', $label);
    }

    // groupedByCategory() is memoized: registering a new kind after the first call must not appear
    public function testGroupedByCategoryResultIsCached(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');
        $first = $registry->groupedByCategory();

        $registry->register('video', 'label.video', ArticleFormStub::class, 'video.html.twig');
        $second = $registry->groupedByCategory();

        $this->assertSame($first, $second);
    }

    // A kind declared with no "contexts" at all (the default) is available regardless of which
    // context is asked for - e.g. legal_model, usable both on a Page and (in theory) a Menu
    public function testGroupedByCategoryIncludesUncontextualizedKindsInAnyContext(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('article', 'label.article', ArticleFormStub::class, 'article.html.twig');

        $forPage = array_merge(...array_values($registry->groupedByCategory('page')));
        $forMenu = array_merge(...array_values($registry->groupedByCategory('menu')));

        $this->assertArrayHasKey('label.article[ui]', $forPage);
        $this->assertArrayHasKey('label.article[ui]', $forMenu);
    }

    // A kind restricted to one or more contexts (e.g. SiteBundle's "menu_link", contexts: ['menu'])
    // only appears when that matching context is asked for, not in unrelated ones
    public function testGroupedByCategoryExcludesKindsRestrictedToOtherContexts(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('menu_link', 'label.menu_link', ArticleFormStub::class, 'menu_link.html.twig', contexts: ['menu']);

        $forPage = array_merge(...array_values($registry->groupedByCategory('page')));
        $forMenu = array_merge(...array_values($registry->groupedByCategory('menu')));

        $this->assertArrayNotHasKey('label.menu_link[ui]', $forPage);
        $this->assertArrayHasKey('label.menu_link[ui]', $forMenu);
    }

    // Calling groupedByCategory() with no context at all (the pre-existing call signature) skips the
    // contexts filter entirely, so callers that haven't started passing a context yet see everything
    public function testGroupedByCategoryWithoutContextIgnoresContextsRestriction(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('menu_link', 'label.menu_link', ArticleFormStub::class, 'menu_link.html.twig', contexts: ['menu']);

        $grouped = array_merge(...array_values($registry->groupedByCategory()));

        $this->assertArrayHasKey('label.menu_link[ui]', $grouped);
    }

    // Each distinct $context is cached separately - a lookup for "menu" must not leak into "page"'s cache
    public function testGroupedByCategoryCachesPerContext(): void
    {
        $registry = new BlockRegistry($this->createTranslator());
        $registry->register('menu_link', 'label.menu_link', ArticleFormStub::class, 'menu_link.html.twig', contexts: ['menu']);

        $registry->groupedByCategory('menu');
        $forPage = array_merge(...array_values($registry->groupedByCategory('page')));

        $this->assertArrayNotHasKey('label.menu_link[ui]', $forPage);
    }
}

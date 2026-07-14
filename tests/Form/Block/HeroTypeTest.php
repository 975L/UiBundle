<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Form\Block;

use c975L\UiBundle\Form\Block\HeroType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HeroTypeTest extends TestCase
{
    private function buildAddedFields(): array
    {
        $added = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use (&$added, $builder) {
            $added[$name] = $options;

            return $builder;
        });

        (new HeroType())->buildForm($builder, []);

        return $added;
    }

    public function testBuildFormAddsExpectedFields(): void
    {
        $added = $this->buildAddedFields();

        foreach (['badge', 'title', 'subtitle', 'primaryLabel', 'primaryUrl', 'secondaryLabel', 'secondaryUrl', 'statValue', 'statLabel'] as $field) {
            $this->assertArrayHasKey($field, $added, "\"$field\" should be added to the Hero form");
        }
    }

    public function testOnlyPrimaryCtaAndTitleAreRequired(): void
    {
        $added = $this->buildAddedFields();

        $this->assertFalse($added['badge']['required']);
        $this->assertFalse($added['subtitle']['required']);
        $this->assertFalse($added['secondaryLabel']['required']);
        $this->assertFalse($added['secondaryUrl']['required']);
        $this->assertFalse($added['statValue']['required']);
        $this->assertFalse($added['statLabel']['required']);
    }

    public function testConfigureOptionsDefaultsToNullDataClassAndUiTranslationDomain(): void
    {
        $type = new HeroType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertNull($options['data_class']);
        $this->assertSame('ui', $options['translation_domain']);
    }
}

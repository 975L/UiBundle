<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Form\Block;

use c975L\UiBundle\Entity\Form;
use c975L\UiBundle\Form\Block\FormPickerType;
use c975L\UiBundle\Repository\FormRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class FormPickerTypeTest extends TestCase
{
    private function buildForm(string $name): Form
    {
        $form = new Form();
        $form->setName($name);

        return $form;
    }

    public function testChoicesAreBuiltFromEveryFormName(): void
    {
        $repository = $this->createStub(FormRepository::class);
        $repository->method('findBy')->willReturn([
            $this->buildForm('contact'),
            $this->buildForm('newsletter'),
        ]);

        $added = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use (&$added, $builder) {
            $added[$name] = ['type' => $type, 'options' => $options];

            return $builder;
        });

        (new FormPickerType($repository))->buildForm($builder, []);

        $this->assertSame(ChoiceType::class, $added['name']['type']);
        $this->assertSame(['contact' => 'contact', 'newsletter' => 'newsletter'], $added['name']['options']['choices']);
    }

    public function testChoicesAreEmptyWhenNoFormExists(): void
    {
        $repository = $this->createStub(FormRepository::class);
        $repository->method('findBy')->willReturn([]);

        $added = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use (&$added, $builder) {
            $added[$name] = ['type' => $type, 'options' => $options];

            return $builder;
        });

        (new FormPickerType($repository))->buildForm($builder, []);

        $this->assertSame([], $added['name']['options']['choices']);
    }
}

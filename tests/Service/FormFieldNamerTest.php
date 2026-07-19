<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Service;

use c975L\UiBundle\Entity\Form;
use c975L\UiBundle\Entity\FormField;
use c975L\UiBundle\Service\FormFieldNamer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class FormFieldNamerTest extends TestCase
{
    private function createNamer(): FormFieldNamer
    {
        return new FormFieldNamer(new AsciiSlugger());
    }

    private function names(Form $form): array
    {
        return array_map(static fn (FormField $field): string => $field->getName(), $form->getFields()->toArray());
    }

    public function testNameFieldsSlugifiesEachFieldLabel(): void
    {
        $form = (new Form())
            ->addField((new FormField())->setLabel('Full Name'))
            ->addField((new FormField())->setLabel('Email'));

        $this->createNamer()->nameFields($form);

        $this->assertSame(['full-name', 'email'], $this->names($form));
    }

    // Two fields whose labels slugify to the same base must not collide on the same submitted key
    public function testNameFieldsDedupesCollidingSlugs(): void
    {
        $form = (new Form())
            ->addField((new FormField())->setLabel('Email'))
            ->addField((new FormField())->setLabel('Email'))
            ->addField((new FormField())->setLabel('Email'));

        $this->createNamer()->nameFields($form);

        $this->assertSame(['email', 'email-2', 'email-3'], $this->names($form));
    }
}

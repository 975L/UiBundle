<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Form;

use c975L\UiBundle\Registry\FontRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Generic font-family picker, not tied to ConfigBundle - any bundle/app form can `add('font', FontChoiceType::class)`
// to offer a select built from whatever FontRegistry knows (see FontProviderInterface/FontProviderPass). Its
// 'choices' default is lazily computed from the registry but stays a normal ChoiceType option, so a caller
// needing to keep a stale/no-longer-declared value selectable (e.g. ConfigCrudController) can still pass its
// own merged 'choices' via setFormTypeOptions()
class FontChoiceType extends AbstractType
{
    public function __construct(private readonly FontRegistry $fontRegistry) {}

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'font_choice';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('choices', function (Options $options): array {
            $fonts = $this->fontRegistry->getFonts();

            return array_combine($fonts, $fonts);
        });
        $resolver->setDefault('placeholder', true);
    }
}

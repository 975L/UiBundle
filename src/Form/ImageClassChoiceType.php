<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Closed list of classes available in sass/_images.scss, shared by every block form embedding an image
class ImageClassChoiceType extends AbstractType
{
    public const CHOICES = [
        'label.css_class_rounded' => 'img-rounded',
        'label.css_class_thumbnail' => 'img-thumbnail',
        'label.css_class_circle' => 'img-circle',
        'label.css_class_square' => 'img-square',
        'label.css_class_shadow' => 'img-shadow',
    ];

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'label.css_classes',
            'help' => 'label.css_classes_help',
            'choices' => self::CHOICES,
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'translation_domain' => 'ui',
        ]);
    }
}

<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Form\Block;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alt', TextType::class, [
                'label' => 'label.alt_text',
                'required' => false,
            ])
            ->add('label', TextType::class, [
                'label' => 'label.caption',
                'required' => false,
            ])
            ->add('width', TextType::class, [
                'label' => 'label.width',
                'help' => 'label.width_help',
                'required' => false,
            ])
            ->add('height', TextType::class, [
                'label' => 'label.height',
                'help' => 'label.height_help',
                'required' => false,
            ])
            ->add('class', TextType::class, [
                'label' => 'label.css_classes',
                'help' => 'label.css_classes_help',
                'required' => false,
            ])
            ->add('above', CheckboxType::class, [
                'label' => 'label.caption_above',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => null,
            'translation_domain' => 'ui',
        ]);
    }
}
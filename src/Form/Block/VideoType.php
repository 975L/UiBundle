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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('src', TextType::class, [
                'label' => 'label.video_src',
            ])
            ->add('type', ChoiceType::class, [
                'label'   => 'label.format',
                'choices' => [
                    'MP4'  => 'video/mp4',
                    'WebM' => 'video/webm',
                    'OGG'  => 'video/ogg',
                ],
            ])
            ->add('poster', TextType::class, [
                'label'    => 'label.poster',
                'required' => false,
            ])
            ->add('autoplay', CheckboxType::class, [
                'label'    => 'label.autoplay',
                'required' => false,
            ])
            ->add('muted', CheckboxType::class, [
                'label'    => 'label.muted',
                'required' => false,
            ])
            ->add('loop', CheckboxType::class, [
                'label'    => 'label.loop',
                'required' => false,
            ])
            ->add('width', TextType::class, [
                'label'    => 'label.width',
                'required' => false,
            ])
            ->add('height', TextType::class, [
                'label'    => 'label.height',
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

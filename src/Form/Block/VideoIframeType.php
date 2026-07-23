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
use c975L\UiBundle\Form\BlockClassChoiceType;
use c975L\UiBundle\Form\TrixEditorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VideoIframeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('src', UrlType::class, [
                'label' => 'label.video_url',
            ])
            ->add('title', TextType::class, [
                'label'    => 'label.title',
                'required' => false,
            ])
            ->add('description', TrixEditorType::class, [
                'label'    => 'label.description',
                'required' => false,
            ])
            ->add('width', TextType::class, [
                'label'    => 'label.width',
                'required' => false,
            ])
            ->add('height', TextType::class, [
                'label'    => 'label.height',
                'required' => false,
            ])
            ->add('class', BlockClassChoiceType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => null,
            'translation_domain' => 'ui',
        ]);
    }
}

<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Form;

use c975L\UiBundle\Entity\Media;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MediaUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isImage = null !== $options['accept'] && str_starts_with($options['accept'], 'image');
        $isSlider = 'slider' === $options['context'];
        $builder
            ->add('file', $isImage ? VichImageType::class : VichFileType::class, [
                'label' => false,
                'required' => false,
                'allow_delete' => true,
                'download_label' => false,
                'delete_label_translation_domain' => 'messages',
                'attr' => array_filter(['accept' => $options['accept']]),
            ])
            ->add('position', HiddenType::class, [
                'attr' => ['class' => 'ui-sort-position'],
            ]);

        // Per-image display metadata, only relevant when the uploaded file is an image
        if ($isImage) {
            $builder
                ->add('alt', TextType::class, [
                    'label' => 'label.alt_text',
                    'required' => false,
                ])
                ->add('cssClasses', ImageClassChoiceType::class);

            // Caption/positioning fields make sense for a standalone Image block, not for a slide
            // inside a Slider (no in-page position to control, no "above the caption" layout)
            if (!$isSlider) {
                $builder
                    ->add('label', TextType::class, [
                        'label' => 'label.caption',
                        'help' => 'label.caption_help',
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
                    ->add('above', CheckboxType::class, [
                        'label' => 'label.caption_above',
                        'required' => false,
                    ]);
            }

            // Per-slide copyright display, only relevant for Slider slides
            if ($isSlider) {
                $builder
                    ->add('credits', TextType::class, [
                        'label' => 'label.credits',
                        'help' => 'label.credits_help',
                        'required' => false,
                    ])
                    ->add('rightsReserved', CheckboxType::class, [
                        'label' => 'label.rights_reserved',
                        'required' => false,
                    ]);
            }
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (PreSetDataEvent $event): void {
                $media = $event->getData();

                // Unmapped, only used server-side to reconcile submitted entries against existing rows by
                // ID (see BlockType's PRE_SUBMIT listener) - positional/identity diffing is unreliable once
                // nested dynamic sub-forms are involved. Must be added here with "data" set directly:
                // setting it via setData() after a static add() gets overwritten by the default mapper for
                // unmapped fields, which falls back to the field's original (empty) "data" option.
                $event->getForm()->add('id', HiddenType::class, [
                    'mapped' => false,
                    'required' => false,
                    'data' => $media instanceof Media ? $media->getId() : null,
                ]);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'accept' => null,
            'context' => null,
        ]);

        $resolver->setAllowedTypes('accept', ['null', 'string']);
        $resolver->setAllowedTypes('context', ['null', 'string']);
    }
}

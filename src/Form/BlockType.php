<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Form;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Form\MediaUploadType;
use c975L\UiBundle\Registry\BlockRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// Check Readme for usage instructions
class BlockType extends AbstractType
{
    public function __construct(
        private BlockRegistry $registry,
        private UrlGeneratorInterface $router
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kind', ChoiceType::class, [
                'label' => 'label.block_kind',
                'choices' => $this->registry->groupedByCategory(),
                'placeholder' => 'label.choose_block_kind',
                'attr' => [
                    'data-controller'            => 'block',
                    'data-block-kind-url-value'  => $this->router->generate('ui_block_data_form'),
                    'data-action'                => 'change->block#loadData',
                ],
                'row_attr' => ['data-kind-row' => ''],
            ])
            ->add('position', HiddenType::class, [
                'attr' => ['class' => 'ui-sort-position'],
            ]);

        // Load the sub-form `data` dynamically according to the block kind
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (PreSetDataEvent $event): void {
                $block = $event->getData();
                if (null === $block) {
                    return;
                }

                $kind = is_object($block) ? $block->getKind() : ($block['kind'] ?? null);
                if ($kind && $this->registry->has($kind)) {
                    $this->addDataSubForm($event->getForm(), $kind);
                    if ($this->registry->hasMediaTypes($kind)) {
                        $this->addMediaSubForm($event->getForm(), $kind);
                    }
                }
            }
        );

        // Re-add the sub-form `data` BEFORE Symfony maps submitted values (PRE_SUBMIT), so the correct FormType is in place when the mapping happens.
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $submitted = $event->getData();
                $kind = $submitted['kind'] ?? null;
                if ($kind && $this->registry->has($kind)) {
                    $this->addDataSubForm($event->getForm(), $kind);
                    if ($this->registry->hasMediaTypes($kind)) {
                        $this->addMediaSubForm($event->getForm(), $kind);
                    }
                }
            }
        );
    }

    private function addDataSubForm(FormInterface $form, string $kind): void
    {
        $form->add('data', $this->registry->getFormClass($kind), [
            'label' => false,
            'row_attr' => ['class' => 'block-data-form'],
        ]);
    }

    private function addMediaSubForm(FormInterface $form, string $kind): void
    {
        $accept = implode(',', $this->registry->getMediaTypes($kind));

        $form->add('medias', CollectionType::class, [
            'label' => 'label.media',
            'help' => 'label.media_help',
            'entry_type' => MediaUploadType::class,
            'entry_options' => ['accept' => $accept],
            'allow_add'  => true,
            'allow_delete' => true,
            'by_reference' => false,
            'prototype' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'label' => false,
            'translation_domain' => 'ui'
        ]);
    }
}
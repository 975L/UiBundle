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
use c975L\UiBundle\Entity\Media;
use c975L\UiBundle\Form\AnimationChoiceType;
use c975L\UiBundle\Form\MediaUploadType;
use c975L\UiBundle\Form\Util\CollectionReconciler;
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
                'choice_translation_domain' => false,
                'placeholder' => 'label.choose_block_kind',
                'attr' => [
                    'data-controller'            => 'block',
                    'data-block-kind-url-value'  => $this->router->generate('ui_block_data_form'),
                    'data-action'                => 'change->block#loadData',
                    'data-ea-widget'             => 'ea-autocomplete',
                ],
                'row_attr' => ['data-kind-row' => ''],
            ])
            ->add('animation', AnimationChoiceType::class)
            ->add('position', HiddenType::class, [
                'attr' => ['class' => 'ui-sort-position'],
            ]);

        // Load the sub-form `data` dynamically according to the block kind
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (PreSetDataEvent $event): void {
                $block = $event->getData();

                // Unmapped, only used server-side to reconcile submitted entries against existing rows by
                // ID (see PageCrudController::createEditFormBuilder) - positional/identity diffing is
                // unreliable once nested dynamic sub-forms are involved. Must be added here with "data" set
                // directly: setting it via setData() after a static add() gets overwritten by the default
                // mapper for unmapped fields, which falls back to the field's original (empty) "data" option.
                $event->getForm()->add('id', HiddenType::class, [
                    'mapped' => false,
                    'required' => false,
                    'data' => $block instanceof Block ? $block->getId() : null,
                ]);

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
                        $block = $event->getForm()->getData();
                        if ($block instanceof Block) {
                            CollectionReconciler::pruneRemoved(
                                $block->getMedias(),
                                $submitted['medias'] ?? [],
                                static fn (Media $media) => $block->removeMedia($media)
                            );

                            // A deleted media can leave a malformed remnant behind in the submission (its
                            // "delete"/css-class checkboxes resubmitted under the old array key, with no id
                            // and no actual file) - left as-is, CollectionType treats it as a genuine new
                            // entry and binds it to null data, which breaks Vich's own conditional "delete"
                            // checkbox (VichFileType only adds it when the bound object is non-null) and
                            // fails validation with "This form should not contain extra fields".
                            $submitted['medias'] = CollectionReconciler::dropOrphaned(
                                $submitted['medias'] ?? [],
                                $block->getMedias(),
                                static fn (array $entry): bool => !empty($entry['file']['file'] ?? null)
                            );
                        }

                        // Removing the very last media also leaves nothing submitted at all under "medias"
                        // (an HTML form can't represent an empty array, only an absent key), which has to
                        // be normalized to [] below or Symfony skips add/remove handling for the field.
                        if (!isset($submitted['medias'])) {
                            $submitted['medias'] = [];
                        }
                        $event->setData($submitted);
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
            'entry_options' => ['accept' => $accept, 'context' => $kind],
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
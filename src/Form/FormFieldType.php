<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Form;

use c975L\UiBundle\Entity\FormField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Entry type for the "fields" CollectionField of a Form (see ContactFormBundle's ContactFormCrudController for a concrete usage), sortable via the admin-wide ea-sortable.js (targets any ".field-collection-item" row with a "position" input, nothing block-specific needed here) - unlike UiBundle's BlockType, every field type (text/textarea/email/checkbox) shares the exact same shape, so there is no per-kind dynamic sub-form to load
class FormFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'label.field_label',
            ])
            ->add('placeholder', TextType::class, [
                'label' => 'label.field_placeholder',
                'required' => false,
            ])
            ->add('required', CheckboxType::class, [
                'label' => 'label.field_required',
                'required' => false,
            ])
            ->add('position', HiddenType::class, [
                'attr' => ['class' => 'ui-sort-position'],
            ])
            // Always disabled: never editable through this form, only ever set by a seeding command (see a future FormDefaultsImporter) - a disabled field is never bound from submitted data (real server-side enforcement, not just hidden by CSS), rendered so ea-sortable.js can still read its current value client-side to hide that row's delete button (see assets/js/ea-sortable.js)
            ->add('restricted', CheckboxType::class, [
                'label' => false,
                'required' => false,
                'disabled' => true,
                'attr' => ['class' => 'ui-field-restricted d-none'],
            ])
        ;

        // "type" is added here, not statically above, so its "disabled" option can depend on this row's own data - like "restricted" itself, a disabled field ignores whatever is submitted and keeps its current value, so a restricted field can never be reclassified even by a tampered request, only reordered/relabelled
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (PreSetDataEvent $event): void {
                $field = $event->getData();
                $restricted = $field instanceof FormField && $field->isRestricted();

                $event->getForm()->add('type', ChoiceType::class, [
                    'label' => 'label.field_type',
                    'choices' => [
                        'label.field_type_text' => FormField::TYPE_TEXT,
                        'label.field_type_textarea' => FormField::TYPE_TEXTAREA,
                        'label.field_type_email' => FormField::TYPE_EMAIL,
                        'label.field_type_checkbox' => FormField::TYPE_CHECKBOX,
                    ],
                    'disabled' => $restricted,
                ]);

                // Unmapped, only used server-side to reconcile submitted entries against existing rows by ID (see CollectionReconciler, used the same way by BlockType/PageCrudController)
                $event->getForm()->add('id', HiddenType::class, [
                    'mapped' => false,
                    'required' => false,
                    'data' => $field instanceof FormField ? $field->getId() : null,
                ]);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormField::class,
            'label' => false,
            'translation_domain' => 'ui',
        ]);
    }
}

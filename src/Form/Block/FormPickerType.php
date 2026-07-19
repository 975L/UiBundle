<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Form\Block;

use c975L\UiBundle\Entity\Form;
use c975L\UiBundle\Repository\FormRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Data FormType for the "form" Block kind (see UiBundle config/services.yaml "ui.block.form") - only choice is which c975L\UiBundle\Entity\Form to embed, picked by name; rendering/processing itself is FormController's job
class FormPickerType extends AbstractType
{
    public function __construct(private readonly FormRepository $formRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $names = array_map(
            static fn (Form $form): string => (string) $form->getName(),
            $this->formRepository->findBy([], ['name' => 'ASC'])
        );

        $builder->add('name', ChoiceType::class, [
            'label' => 'label.form_picker_name',
            'choices' => array_combine($names, $names),
            'placeholder' => 'label.choose_form',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'label' => false,
            'translation_domain' => 'ui',
        ]);
    }
}

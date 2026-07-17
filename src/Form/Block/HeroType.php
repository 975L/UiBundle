<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Form\Block;

use c975L\UiBundle\Form\TrixEditorType;
use c975L\UiBundle\Service\BlockAnchorSlugger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HeroType extends AbstractType
{
    use HasAnchorFieldTrait;

    public function __construct(private readonly BlockAnchorSlugger $anchorSlugger)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addAnchorField($builder, $this->anchorSlugger);

        $builder
            ->add('badge', TextType::class, [
                'label' => 'label.badge',
                'required' => false,
            ])
            // TrixEditorType (not a plain TextType) so an editor can emphasize a word (<em>) the same
            // way the reference design highlights it in red - see blocks/Hero.html.twig / _hero__title em
            ->add('title', TrixEditorType::class, [
                'label' => 'label.title',
            ])
            ->add('subtitle', TextType::class, [
                'label' => 'label.subtitle',
                'required' => false,
            ])
            ->add('primaryLabel', TextType::class, [
                'label' => 'label.primary_label',
            ])
            ->add('primaryUrl', TextType::class, [
                'label' => 'label.primary_url',
            ])
            ->add('secondaryLabel', TextType::class, [
                'label' => 'label.secondary_label',
                'required' => false,
            ])
            ->add('secondaryUrl', TextType::class, [
                'label' => 'label.secondary_url',
                'required' => false,
            ])
            ->add('statValue', TextType::class, [
                'label' => 'label.stat_value',
                'required' => false,
            ])
            ->add('statLabel', TextType::class, [
                'label' => 'label.stat_label',
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

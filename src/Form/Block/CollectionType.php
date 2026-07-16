<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Form\Block;

use c975L\UiBundle\Registry\CollectionSourceRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Each item comes from the chosen source, rendered as a never-persisted "card" Block - see
// Collection.html.twig. No item is entered here: source/limit only pick which external collection
// to pull from and how many of its items to show
class CollectionType extends AbstractType
{
    public function __construct(private readonly CollectionSourceRegistry $sourceRegistry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->sourceRegistry->choices();

        $builder
            ->add('source', ChoiceType::class, [
                'label'   => 'label.source',
                'choices' => $choices,
                // No bundle implementing CollectionSourceProviderInterface yet (fresh install) means
                // an empty select with nothing to pick - a disabled placeholder explains why instead
                // of leaving the editor facing a blank dropdown with no clue what's wrong
                'placeholder' => [] === $choices ? 'label.no_collection_source_available' : null,
            ])
            ->add('limit', IntegerType::class, [
                'label'    => 'label.limit',
                'help'     => 'label.collection_limit_help',
                'required' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'label.title',
                'required' => false,
            ])
            // Slug of a real Page (site_page) that renders this source's per-item detail views - its
            // own blocks are rendered as-is (a "collectionItem" Twig global carries the current item's
            // data, picked up by whichever block on it needs it, e.g. a "twig_content" block's own
            // templatePath) - see PageController::resolveCollectionDetail() and SiteBundle's README
            // ("Item detail pages", under "Collection entries"). Never rendered by this block itself.
            ->add('detailPage', TextType::class, [
                'label'    => 'label.detail_page',
                'help'     => 'label.detail_page_help',
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

    // Without this, the default block prefix derived from the class name ("CollectionType" -> "collection")
    // collides with Symfony's own CollectionType (used by PageCrudController's "blocks" field), making
    // EasyAdmin's collection_row/collection_widget form theme blocks wrongly apply here and blow up on
    // "allow_add"/"allow_delete" - vars only a real CollectionType field populates
    public function getBlockPrefix(): string
    {
        return 'block_collection';
    }
}

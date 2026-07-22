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
use Symfony\Component\OptionsResolver\OptionsResolver;

// The "data" sub-form of the "flex_column" kind - deliberately empty: a column has no content of its
// own, it's purely a grouping of its own "slots" (a real Block relation, added by
// BlockType::addSlotsSubForm(), not part of this form) - see "flex_columns" (the row) for the eyebrow/title
class FlexColumnType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => null,
            'translation_domain' => 'ui',
        ]);
    }
}

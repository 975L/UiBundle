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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MediaUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isImage = null !== $options['accept'] && str_starts_with($options['accept'], 'image');
        $builder
            ->add('file', $isImage ? VichImageType::class : VichFileType::class, [
                'label' => false,
                'required' => false,
                'allow_delete' => true,
                'download_label' => false,
                'attr' => array_filter(['accept' => $options['accept']]),
            ])
            ->add('position', HiddenType::class, [
                'attr' => ['class' => 'ui-sort-position'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'accept' => null,
        ]);

        $resolver->setAllowedTypes('accept', ['null', 'string']);
    }
}

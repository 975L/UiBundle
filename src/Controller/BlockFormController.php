<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Controller;

use c975L\UiBundle\Form\MediaUploadType;
use c975L\UiBundle\Registry\BlockRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlockFormController extends AbstractController
{
    public function __construct(
        private BlockRegistry $registry,
        private FormFactoryInterface $formFactory
    ) {}

    #[Route('/ui/block/data-form', name: 'ui_block_data_form', methods: ['GET'])]
    public function dataForm(Request $request): Response
    {
        $kind = (string) $request->query->get('k', '');
        if (!$kind || !$this->registry->has($kind)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $builder = $this->formFactory
            ->createNamedBuilder('_block_', FormType::class)
            ->add('data', $this->registry->getFormClass($kind), ['label' => false]);

        if ($this->registry->hasMediaTypes($kind)) {
            $builder->add('medias', CollectionType::class, [
                'label'         => 'label.media',
                'entry_type'    => MediaUploadType::class,
                'entry_options' => ['accept' => implode(',', $this->registry->getMediaTypes($kind))],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
                'prototype'     => true,
            ]);
        }

        return $this->render('@c975LUi/form/block.html.twig', [
            'form' => $builder->getForm()->createView(),
        ]);
    }
}

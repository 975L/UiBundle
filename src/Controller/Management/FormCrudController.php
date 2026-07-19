<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Controller\Management;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\UiBundle\Entity\Form;
use c975L\UiBundle\Entity\FormField;
use c975L\UiBundle\Form\FormFieldType;
use c975L\UiBundle\Form\Util\CollectionReconciler;
use c975L\UiBundle\Registry\FormActionRegistry;
use c975L\UiBundle\Service\FormFieldNamer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

// Generic "manage any Form" admin screen - unlike a bundle owning its own dedicated CrudController scoped to one hardcoded name (e.g. ContactFormBundle's former ContactFormCrudController), this one lists/creates/edits every c975L\UiBundle\Entity\Form. A seeded, restricted Form (see Form::$restricted) keeps its "name" locked and can't be deleted from here - same spirit as FormField::$restricted for individual fields
class FormCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ConfigServiceInterface $configService,
        private readonly FormFieldNamer $formFieldNamer,
        private readonly FormActionRegistry $actionRegistry,
        private readonly AdminContextProvider $adminContextProvider,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Form::class;
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof Form) {
            $this->formFieldNamer->nameFields($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof Form) {
            $this->formFieldNamer->nameFields($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    // Removing the very last field also leaves nothing submitted at all for "fields" (an HTML form can't represent an empty array, only an absent key), which has to be normalized to [] below or Symfony skips add/remove handling entirely for the whole field - same reconciliation as ContactFormCrudController/PageCrudController used for their own collections
    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        $formBuilder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $form = $event->getForm()->getData();
            if ($form instanceof Form) {
                // A restricted field (see FormField::isRestricted()) is never removed, even from a tampered request
                CollectionReconciler::pruneRemoved(
                    $form->getFields(),
                    $data['fields'] ?? [],
                    static function (FormField $field) use ($form): void {
                        if (!$field->isRestricted()) {
                            $form->removeField($field);
                        }
                    }
                );
            }

            if (!isset($data['fields'])) {
                $data['fields'] = [];
                $event->setData($data);
            }
        });

        return $formBuilder;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->adminContextProvider->getContext()?->getEntity()?->getInstance();
        $isRestricted = $entity instanceof Form && $entity->isRestricted();

        $actionKeys = $this->actionRegistry->getKeys();

        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('name')
                ->setFormTypeOption('disabled', $isRestricted),
            ChoiceField::new('action')
                ->setChoices(array_combine($actionKeys, $actionKeys))
                ->setFormTypeOption('required', false)
                ->setHelp('label.action_help'),
            TextareaField::new('actionConfigJson')
                ->setLabel('label.action_config')
                ->setFormTypeOption('required', false)
                ->setHelp('label.action_config_help')
                ->hideOnIndex(),
            BooleanField::new('restricted')
                ->setFormTypeOption('disabled', true)
                ->hideOnIndex(),
            CollectionField::new('fields')
                ->setLabel('label.fields')
                ->setEntryType(FormFieldType::class)
                ->allowAdd()
                ->allowDelete()
                ->setFormTypeOption('by_reference', false)
                ->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $role = $this->configService->get('site-role-admin');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setLabel(false)->setIcon('fas fa-pencil'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action
                ->setLabel(false)
                ->setIcon('fas fa-trash')
                ->displayIf(static fn (Form $form): bool => !$form->isRestricted()))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setLabel(false)->setIcon('fas fa-eye'))
            ->setPermission(Action::INDEX, $role)
            ->setPermission(Action::NEW, $role)
            ->setPermission(Action::EDIT, $role)
            ->setPermission(Action::DELETE, $role)
            ->setPermission(Action::DETAIL, $role)
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setEntityPermission($this->configService->get('site-role-admin'))
        ;
    }
}

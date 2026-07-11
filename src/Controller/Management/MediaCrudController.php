<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Controller\Management;

use c975L\UiBundle\Entity\Media;
use c975L\UiBundle\Form\ImageClassChoiceType;
use c975L\UiBundle\Registry\MediaUsageRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Vich\UploaderBundle\Form\Type\VichImageType;

use function Symfony\Component\Translation\t;

// Cross-bundle media library: browses every c975L\UiBundle\Entity\Media row regardless of how it is
// attached (Block, Page og-image, site-wide role...) and shows where each one is used, via
// MediaUsageRegistry (fed by any bundle implementing MediaUsageProviderInterface). Site-wide role
// graphics (favicon, logo, error-image...) stay read-only here - they keep being managed in
// SiteGraphicCrudController, which enforces the one-row-per-singleton-role rule and its own alerts.
class MediaCrudController extends AbstractCrudController
{
    // No ConfigBundle dependency here (ConfigBundle already depends on UiBundle, so UiBundle must stay
    // standalone) - apps wanting the same dynamic role as other c975L CRUDs can override this controller
    private const ROLE_NEEDED = 'ROLE_ADMIN';

    public function __construct(
        private readonly MediaUsageRegistry $mediaUsageRegistry,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Media::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular(t('label.media', [], 'ui'))
            ->setEntityLabelInPlural(t('label.media_library', [], 'ui'))
            ->setEntityPermission(self::ROLE_NEEDED)
            ->setDefaultSort(['id' => 'DESC'])
            ->overrideTemplate('crud/index', '@c975LUi/management/media_index.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Detail isn't added to PAGE_INDEX by default - needed here as the fallback default action
            // (entity.defaultActionUrl in media_index.html.twig) for role-set rows, since those hide
            // Edit/Delete below and would otherwise have no action to link their gallery thumbnail to
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::INDEX, self::ROLE_NEEDED)
            ->setPermission(Action::EDIT, self::ROLE_NEEDED)
            ->setPermission(Action::DELETE, self::ROLE_NEEDED)
            ->setPermission(Action::DETAIL, self::ROLE_NEEDED)
            // Site-wide graphics (role set) are only editable from SiteGraphicCrudController
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action->displayIf(
                static fn (Media $media): bool => null === $media->getRole()
            ))
            ->update(Crud::PAGE_INDEX, Action::DELETE, static fn (Action $action): Action => $action->displayIf(
                static fn (Media $media): bool => null === $media->getRole()
            ))
            ->disable(Action::NEW)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // Shown on the Edit page (NEW is disabled, so onlyOnForms() means Edit only here) - the
            // index is now a flat thumbnail gallery (see media_index.html.twig), not a table of fields
            Field::new('id')
                ->setLabel(t('label.used_in', [], 'ui'))
                ->formatValue(fn ($value, Media $media): array => $this->mediaUsageRegistry
                    ->getUsages([$media])[$media->getId()] ?? [])
                ->setTemplatePath('@c975LUi/management/media_usages.html.twig')
                ->onlyOnForms(),

            Field::new('file')
                ->setLabel(t('label.file', [], 'ui'))
                ->setFormType(VichImageType::class)
                ->setFormTypeOptions([
                    'required' => false,
                    'allow_delete' => true,
                    'download_uri' => true,
                    'asset_helper' => true,
                    // Without this, the "delete file" checkbox label falls back to whatever domain
                    // EasyAdmin resolves by default for nested form widgets, which doesn't carry this
                    // key here - same fix already applied in MediaUploadType for the Block forms
                    'delete_label_translation_domain' => 'messages',
                    'constraints' => [
                        new FileConstraint(maxSize: '2M'),
                    ],
                ])
                ->onlyOnForms(),

            TextField::new('alt')
                ->setLabel(t('label.alt_text', [], 'ui'))
                ->hideOnIndex(),

            TextField::new('label')
                ->setLabel(t('label.caption', [], 'ui'))
                ->hideOnIndex(),

            // Native ChoiceField (not Field/TextField): EasyAdmin's TextConfigurator throws on any
            // non-string value regardless of a custom setFormType(), and a plain Field::new() on this
            // json-typed column gets auto-promoted to ArrayField, whose default CollectionType options
            // (entry_type, allow_add...) collide with ImageClassChoiceType (a plain ChoiceType).
            // ChoiceField natively supports multi-valued/array-backed choices, so we replicate its
            // options here instead of reusing the form type directly. Left non-expanded (default) so it
            // renders as the same removable-tags autocomplete widget used for Serie and for block classes.
            ChoiceField::new('cssClasses')
                ->setLabel(t('label.css_classes', [], 'ui'))
                ->setTranslatableChoices(array_combine(
                    array_values(ImageClassChoiceType::CHOICES),
                    array_map(static fn (string $labelKey) => t($labelKey, [], 'ui'), array_keys(ImageClassChoiceType::CHOICES))
                ))
                ->allowMultipleChoices()
                ->onlyOnForms(),

            TextField::new('credits')
                ->setLabel(t('label.credits', [], 'ui'))
                ->hideOnIndex(),
        ];
    }
}

# UiBundle

Symfony bundle providing a dynamic block system for pages and content entities, managed through EasyAdmin with drag-and-drop reordering.

[![GitHub](https://img.shields.io/github/license/975L/UiBundle)](https://github.com/975L/UiBundle/blob/master/LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/c975l/ui-bundle)](https://packagist.org/packages/c975l/ui-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/c975l/ui-bundle)](https://packagist.org/packages/c975l/ui-bundle)

---

## Features

- Dynamic block system with per-kind forms and templates
- Media uploads per block via VichUploader (auto-configured)
- Drag-and-drop position ordering for blocks and media
- AJAX kind-switcher in EasyAdmin
- Extensible: register your own block kinds via a service tag
- Reusable drag-and-drop sortable script for any EasyAdmin `CollectionField`

---

## Requirements

- PHP >= 8.0
- Doctrine ORM
- EasyAdmin
- VichUploader Bundle

---

## Installation

### Download

```bash
composer require c975l/ui-bundle
```

### Run migrations

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Register Stimulus controllers

**Add one entry to `importmap.php`** (one-time, at installation):

```php
'@c975l/ui-bundle/controllers.js' => [
    'path' => './vendor/c975l/ui-bundle/assets/controllers.js',
],
```

**Add two lines to `assets/bootstrap.js`** (or `assets/stimulus_bootstrap.js`):

```js
import { startStimulusApp } from '@symfony/stimulus-bundle';
import { register as registerc975lUi } from '@c975l/ui-bundle/controllers.js';

const app = startStimulusApp();
registerc975lUi(app);
```

---

## Attaching blocks to an entity

Implement `HasBlocksInterface` and use `HasBlocksTrait` on any entity that should own blocks:

```php
use c975L\UiBundle\Contract\HasBlocksInterface;
use c975L\UiBundle\Entity\Trait\HasBlocksTrait;

class Page implements HasBlocksInterface
{
    use HasBlocksTrait;

    #[ORM\OneToMany(targetEntity: Block::class, mappedBy: 'page', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $blocks;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }
}
```

---

## EasyAdmin integration

Add a `CollectionField` using `BlockType` as entry type, and include the bundle's JS asset for the AJAX kind-switcher and drag-and-drop:

```php
use c975L\UiBundle\Form\BlockType;

class PageCrudController extends AbstractCrudController
{
    public function configureAssets(Assets $assets): Assets
    {
        return $assets->addJsFile('@c975l/ui-bundle/js/blocks.js');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // ...
            CollectionField::new('blocks')
                ->setLabel(t('label.blocks', [], 'ui'))
                ->setEntryType(BlockType::class)
                ->allowAdd()
                ->allowDelete()
                ->setFormTypeOption('by_reference', false)
                ->hideOnIndex(),
        ];
    }
}
```

---

## Drag-and-drop sortable for other collections

`sortable.js` adds drag-and-drop reordering to any EasyAdmin `CollectionField`. It is included when you load `blocks.js`, but can also be imported standalone in any other CRUD controller.

**Requirement:** each collection item must contain a hidden `position` field whose `name` ends with `[position]`. The script detects it automatically.

```php
public function configureAssets(Assets $assets): Assets
{
    return $assets->addJsFile('@c975l/ui-bundle/js/blocks.js');
}
```

Expose a hidden `position` field in your collection entry type and order the collection by position on the entity side — the grip handle and drag behaviour are added automatically.

---

## Registering a custom block kind

Declare a service with the `ui.block` tag in your bundle's `services.yaml`:

```yaml
services:
    ui.block.booking:
        class: stdClass
        tags:
            - name: ui.block
              kind: booking
              label: Booking
              icon: fa fa-calendar
              category: Reservations
              form: App\Form\Block\BookingType
              template: '@App/blocks/booking.html.twig'
```

Create the form type to define the `data` sub-fields, and the Twig template to render the block on the front end. The form data is stored as JSON in the `Block::$data` column.

---

If this project **helps you save development time**, consider sponsoring via the **Sponsor** button at the top of the GitHub page. Thank you!

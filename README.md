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
- Automatic CSS injection: bundles declare their stylesheets via a service tag, rendered by `bundle_stylesheets()` in Twig
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

### How it works

Blocks are linked to their owner via a **ManyToMany join table**. The `Block` entity itself has no FK back to any specific owner — this keeps UiBundle fully decoupled from your domain entities. Each owner entity defines its own join table, and the `BlockOrphanListener` (auto-registered by the bundle) removes detached blocks on flush.

### 1. Implement the interface and trait

```php
use c975L\UiBundle\Contract\HasBlocksInterface;
use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Entity\Trait\HasBlocksTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Page implements HasBlocksInterface
{
    use HasBlocksTrait;

    #[ORM\ManyToMany(targetEntity: Block::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'site_page_block')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $blocks;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }
}
```

Key points:

- Use `ManyToMany` (not `OneToMany`) — `Block` has no FK back to the owner.
- Name the join table explicitly (e.g. `site_page_block`) to avoid collisions.
- `cascade: ['persist', 'remove']` ensures blocks are saved and deleted with the owner.
- Do **not** add `orphanRemoval` — the `BlockOrphanListener` handles that automatically when you call `removeBlock()`.

### 2. Run migrations

After adding the mapping, generate and run the migration to create the join table:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### How block removal works

When you call `$page->removeBlock($block)`, the trait queues the block in a `pendingBlockRemovals` list instead of immediately removing it. The `BlockOrphanListener` (Doctrine `preFlush` listener) then calls `$em->remove($block)` for each queued block before the flush completes. This ensures blocks are properly deleted from the database even though the relationship is ManyToMany.

---

## EasyAdmin integration

Add a `CollectionField` using `BlockType` as entry type, and include the bundle's JS asset for the AJAX kind-switcher and drag-and-drop:

```php
use c975L\UiBundle\Form\BlockType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

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
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

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

## Automatic CSS injection

UiBundle provides a mechanism for bundles to declare their stylesheets automatically, without requiring manual `@import` or `<link>` additions in each application.

### How it works

1. Each bundle that provides CSS implements `BundleStylesheetProviderInterface` and registers itself with the `ui.stylesheet` service tag.
2. UiBundle collects all tagged providers at compile time (ordered by `priority`, highest first).
3. The `bundle_stylesheets()` Twig function returns the resolved list of URLs, ready for use in a layout template.

### Adding CSS from your bundle

**Create a provider class** in your bundle:

```php
use c975L\UiBundle\Contract\BundleStylesheetProviderInterface;

class StylesheetProvider implements BundleStylesheetProviderInterface
{
    public function getStylesheets(): array
    {
        return [
            'bundles/mybundle/css/styles.min.css',                          // local public asset
            'https://cdn.example.com/lib/styles.min.css',                   // CDN URL, passed through as-is
        ];
    }
}
```

**Register it with the tag** in `config/services.yaml`:

```yaml
services:
    MyBundle\Service\StylesheetProvider:
        tags:
            - { name: 'ui.stylesheet', priority: 10 }
```

The `priority` attribute is optional (default `0`). Higher priority providers are injected first — use a high value (e.g. `100`) for reset/base styles that must load before others.

### Using it in a layout template

Call `bundle_stylesheets()` in the `stylesheets` block of your layout:

```twig
{% block stylesheets %}
    {% for stylesheet in bundle_stylesheets() %}
        <link rel="stylesheet" href="{{ stylesheet }}">
    {% endfor %}
{% endblock %}
```

Local paths are resolved to absolute versioned URLs via Symfony's asset package. CDN URLs (starting with `http`) are returned as-is.

---

If this project **helps you save development time**, consider sponsoring via the **Sponsor** button at the top of the GitHub page. Thank you!

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
- [Ghostscript](https://www.ghostscript.com/) (`gs` binary) installed on the server — required for automatic PDF thumbnail generation (see [PDF thumbnails](#pdf-thumbnails)). Optional otherwise: without it, PDF uploads still work, but no `.webp` thumbnail is generated.

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

### Making these controllers available in EasyAdmin (blocks editor, sortable, kind-switcher)

Blocks are managed through EasyAdmin at `/management`, provided by `c975l/config-bundle`. Its dashboard does **not** load your site's main `app` AssetMapper entry — that would drag your front-end stylesheet (and unused front-end controllers) into the back-office and break EasyAdmin's own Bootstrap/AdminLTE styling. Instead, it loads a dedicated entry, `@c975l/ui-bundle/admin.js`.

`block`, `eaSortable` and the Trix editor integration are back-office-only, so they live in `controllers-admin.js` (separate from `controllers.js`, which only holds front-end controllers). The bundle ships a ready-to-use entrypoint for them — no file to create in your app.

**Add one entry to `importmap.php`** (one-time, at installation), pointing directly at the bundle's file:

```php
'@c975l/ui-bundle/admin.js' => [
    'path' => './vendor/c975l/ui-bundle/assets/admin.js',
    'entrypoint' => true,
],
```

That's it — `eaSortable`, `block`, and Trix are then available on every `/management` page.

---

## Attaching blocks to an entity

### How block attachment works

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

Add a `CollectionField` using `BlockType` as entry type. The AJAX kind-switcher and drag-and-drop are handled automatically by the Stimulus controllers registered via `@c975l/ui-bundle/controllers.js` — no manual `configureAssets` call is needed:

```php
use c975L\UiBundle\Form\BlockType;

class PageCrudController extends AbstractCrudController
{
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

Drag-and-drop reordering is handled automatically by the `eaSortable` Stimulus controller registered via `@c975l/ui-bundle/controllers.js`. No `configureAssets` call is needed.

**Requirement:** each collection item must contain a hidden `position` field whose `name` ends with `[position]`. The script detects it automatically.

Expose a hidden `position` field in your collection entry type and order the collection by position on the entity side — the grip handle and drag behaviour are added automatically.

---

## Built-in block kinds

The bundle ships the following kinds out of the box (see `config/services.yaml` for the exact service definitions):

| Kind | Category | Form type | Template |
| --- | --- | --- | --- |
| `alert` | Elements | `AlertType` | `blocks/Alert.html.twig` |
| `article` | Elements | `ArticleType` | `blocks/Article.html.twig` |
| `audio` | Media | `AudioType` | `blocks/Audio.html.twig` |
| `button` | Elements | `ButtonType` | `blocks/Button.html.twig` |
| `card` | Elements | `CardType` | `blocks/Card.html.twig` |
| `image` | Media | `ImageType` | `blocks/Image.html.twig` |
| `progress_bar` | Elements | `ProgressBarType` | `blocks/ProgressBar.html.twig` |
| `rich_snippet` | SEO | `RichSnippetType` | `blocks/RichSnippet.html.twig` |
| `slider` | Media | `SliderType` | `blocks/Slider.html.twig` |
| `text_readmore` | Text | `ReadmoreType` | `blocks/TextReadmore.html.twig` |
| `text_section` | Text | `TextSectionType` | `blocks/TextSection.html.twig` |
| `video` | Media | `VideoType` | `blocks/Video.html.twig` |
| `video_iframe` | Media | `VideoIframeType` | `blocks/VideoIframe.html.twig` |

> **Maintenance note:** update this table whenever a kind is added, renamed, or removed in `config/services.yaml`.

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
              description: A short reservation form  # optional, shown under the label in the kind picker
              category: Reservations
              form: App\Form\Block\BookingType
              template: '@App/blocks/booking.html.twig'
```

Create the form type to define the `data` sub-fields, and the Twig template to render the block on the front end. The form data is stored as JSON in the `Block::$data` column.

---

## Reusable Twig components

Block templates are thin adapters around a set of Symfony UX Twig components living in `templates/components/`, callable directly in your own templates as `<twig:c975LUi:Group:Name .../>`.

| Component | Purpose |
| --- | --- |
| `<twig:c975LUi:Alert:Alert>` | Bootstrap-style alert box |
| `<twig:c975LUi:Article:Article>` | Single article (title/content/media) |
| `<twig:c975LUi:Article:Articles>` | Loops `Article` over a collection |
| `<twig:c975LUi:Audio:Audio>` | HTML5 audio player |
| `<twig:c975LUi:Blocks:Block>` | Renders one `Block` entity via its registered kind template |
| `<twig:c975LUi:Blocks:Blocks>` | Loops `Block` over a collection |
| `<twig:c975LUi:Button:Button>` | Styled button/link |
| `<twig:c975LUi:Card:Card>` | Bootstrap card |
| `<twig:c975LUi:General:RichSnippet>` | JSON-LD structured data snippet |
| `<twig:c975LUi:Image:Icon>` | Small icon image |
| `<twig:c975LUi:Image:Image>` | Responsive image |
| `<twig:c975LUi:Image:Link>` | Image wrapped in a link |
| `<twig:c975LUi:Menu:Menu>` | Site navigation menu |
| `<twig:c975LUi:Menu:MenuItem>` | Single menu link with active-state detection |
| `<twig:c975LUi:Pagination:Pagination>` | Pagination links |
| `<twig:c975LUi:Progress:Bar>` | Progress bar |
| `<twig:c975LUi:Slider:Slider>` | Image/media slider |
| `<twig:c975LUi:Text:Readmore>` | Collapsible "read more" text block |
| `<twig:c975LUi:Text:Section>` | Text section with optional image |
| `<twig:c975LUi:Video:Iframe>` | Embedded video iframe (YouTube etc.) |
| `<twig:c975LUi:Video:Video>` | HTML5 video player |

Props match the Twig variables used inside each template — see `templates/components/<Group>/<Name>.html.twig` for the exact list.

> **Maintenance note:** update this table whenever a component is added, renamed, or removed in `templates/components/`.

---

## PDF thumbnails

When a `.pdf` file is uploaded through VichUploader on **any entity** (no interface required), the bundle automatically generates a `.webp` thumbnail of the first page next to it (`document.pdf` → `document.pdf.webp`), via Ghostscript + Imagine/GD.

- **Requires Ghostscript** (`gs`) installed on the server. If missing, the thumbnail generation silently fails — the PDF upload itself is unaffected.
- **Skipped for private files** — entities implementing `VichPrivateFileInterface` (e.g. a paid download in a shop) are not thumbnailed, since there's no public preview use case for them.
- **Thumbnail width** defaults to `400px`, or reuses `getImageWidth()` if the entity also implements `VichImageResizableInterface`.

No configuration needed — handled by `VichPdfThumbnailListener`, auto-registered like the rest of the bundle's services.

---

## Automatic CSS injection

UiBundle provides a mechanism for bundles to declare their stylesheets automatically, without requiring manual `@import` or `<link>` additions in each application.

### How CSS injection works

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
            'bundles/mybundle/css/styles.min.css', // local public asset
            'https://cdn.example.com/lib/styles.min.css', // CDN URL, passed through as-is
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

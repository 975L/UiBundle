# c975L UiBundle

A Symfony bundle providing a dynamic block system for pages and content entities. Each block has a `kind` (e.g. `slider`, `button`, `text_section`) with its own form and template, managed through EasyAdmin with drag-and-drop reordering.

[![GitHub](https://img.shields.io/github/license/975L/UiBundle)](https://github.com/975L/UiBundle/blob/master/LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/c975l/ui-bundle)](https://packagist.org/packages/c975l/ui-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/c975l/ui-bundle)](https://packagist.org/packages/c975l/ui-bundle)

## Features

- Dynamic block system with per-kind forms and templates
- Media uploads per block via VichUploader (auto-configured)
- Drag-and-drop position ordering for blocks and media
- AJAX kind-switcher in EasyAdmin
- Extensible: register your own block kinds via a service tag

## Installation

```bash
composer require c975l/ui-bundle
```

Run the database migration to create the `site_block` and `site_media` tables:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

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

## Reusable drag-and-drop sortable

`sortable.js` is a standalone, zero-dependency script that adds drag-and-drop reordering to any EasyAdmin `CollectionField`. It is included automatically when you load `blocks.js`, but can also be imported on its own in any other CRUD controller.

**Requirement:** each collection item must contain a hidden `position` field whose `name` ends with `[position]` (e.g. `product_images[0][position]`). The script detects it automatically and enables sorting only for those collections.

**Safe with nested collections:** when a collection item contains another sortable sub-collection (e.g. a Block containing Media), each level is handled independently without interfering with the other.

### Using in another bundle

Import `blocks.js` in your CRUD controller — the kind-selector section is inert if no `[data-block-kind-url-value]` element is present on the page:

```php
public function configureAssets(Assets $assets): Assets
{
    return $assets->addJsFile('@c975l/ui-bundle/js/blocks.js');
}
```

Then expose a hidden `position` field in your collection entry type:

```php
class ProductImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', HiddenType::class);
        // ...
    }
}
```

Order your collection by position on the entity side:

```php
#[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
#[ORM\OrderBy(['position' => 'ASC'])]
private Collection $images;
```

That is all — the grip handle and drag behaviour are added automatically on page load.

## Built-in block kinds

| Kind | Description |
| --- | --- |
| `alert` | Bootstrap alert box |
| `article` | Article with image and rich text |
| `audio` | Audio player |
| `button` | Call-to-action button |
| `card` | Bootstrap card |
| `image` | Single image with optional caption |
| `progress_bar` | Animated progress bar |
| `readmore` | Collapsible read-more section |
| `rich_snippet` | Structured data / JSON-LD snippet |
| `slider` | Image slider (multiple media) |
| `text_section` | Rich text section |
| `video` | Uploaded video file |
| `video_iframe` | Embedded video (YouTube, Vimeo…) |

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

Create the form type (`BookingType`) to define the `data` sub-fields, and the Twig template to render the block on the front end. The form data is stored as JSON in the `Block::$data` column.

## License

MIT — see [LICENSE](LICENSE).

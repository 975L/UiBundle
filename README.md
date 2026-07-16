# UiBundle

Symfony bundle providing a dynamic block system for pages and content entities, managed through EasyAdmin with drag-and-drop reordering.

[![GitHub](https://img.shields.io/github/license/975L/UiBundle)](https://github.com/975L/UiBundle/blob/master/LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/c975l/ui-bundle)](https://packagist.org/packages/c975l/ui-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/c975l/ui-bundle)](https://packagist.org/packages/c975l/ui-bundle)

---

## Features

- Dynamic block system with per-kind forms and templates
- Media uploads per block via VichUploader (auto-configured)
- Multi-file upload for kinds that opt in (`slider`, `article` out of the box) - select several files at once instead of adding them one by one
- Drag-and-drop position ordering for blocks and media
- One-click duplication of a block or a media row in EasyAdmin, including its files
- Live preview of a newly picked image in EasyAdmin, before saving
- Site-wide media roles (favicon, apple-touch-icon, og-image, logo) retrievable anywhere via `site_media()`
- Media Library in EasyAdmin: browse every `Media` regardless of how it's attached, and see where it's used
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
| `banner_title` | Media | `BannerTitleType` | `blocks/BannerTitle.html.twig` |
| `button` | Elements | `ButtonType` | `blocks/Button.html.twig` |
| `card` | Elements | `CardType` | `blocks/Card.html.twig` |
| `collection` | Page sections | `CollectionType` | `blocks/Collection.html.twig` |
| `cta_band` | Page sections | `CtaBandType` | `blocks/CtaBand.html.twig` |
| `document_download` | Elements | `DocumentDownloadType` | `blocks/DocumentDownload.html.twig` |
| `expertise_banner` | Page sections | `ExpertiseBannerType` | `blocks/ExpertiseBanner.html.twig` |
| `feature_bar` | Page sections | `FeatureBarType` | `blocks/FeatureBar.html.twig` |
| `hero` | Page sections | `HeroType` | `blocks/Hero.html.twig` |
| `image` | Media | `ImageType` | `blocks/Image.html.twig` |
| `image_compare` | Media | `ImageCompareType` | `blocks/ImageCompare.html.twig` |
| `portfolio_grid` | Page sections | `PortfolioGridType` | `blocks/PortfolioGrid.html.twig` |
| `process_steps` | Page sections | `ProcessStepsType` | `blocks/ProcessSteps.html.twig` |
| `progress_bar` | Elements | `ProgressBarType` | `blocks/ProgressBar.html.twig` |
| `rich_snippet` | SEO | `RichSnippetType` | `blocks/RichSnippet.html.twig` |
| `section_cards` | Page sections | `SectionCardsType` | `blocks/SectionCards.html.twig` |
| `slider` | Media | `SliderType` | `blocks/Slider.html.twig` |
| `text_readmore` | Text | `ReadmoreType` | `blocks/TextReadmore.html.twig` |
| `text_section` | Text | `TextSectionType` | `blocks/TextSection.html.twig` |
| `video` | Media | `VideoType` | `blocks/Video.html.twig` |
| `video_iframe` | Media | `VideoIframeType` | `blocks/VideoIframe.html.twig` |

> **Maintenance note:** update this table whenever a kind is added, renamed, or removed in `config/services.yaml`.

---

## Registering a custom block kind

Run `bin/console c975l:ui:block:create` (requires `symfony/maker-bundle` in `require-dev`) to scaffold the FormType, template and test below for a new kind - it prints the `services.yaml` snippet to add once it's done. The rest of this section describes what that snippet means and the manual steps if you'd rather write them yourself.

This command runs in a **consuming app** (it needs a booted kernel with `symfony/maker-bundle` installed) and generates into that app's own `App\` namespace - it's meant for a one-off kind specific to that app, not for adding to UiBundle's own built-in catalog above. A new built-in kind (living in `c975L\UiBundle\Form\Block\`, wired into UiBundle's own `config/services.yaml`, with translations in all 3 `translations/ui.*.xlf` and a fixture entry in `BlockFixtureProvider`) is still written by hand, matching the existing kinds' conventions.

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
              pickable: true  # required - see below
              priority: 80  # optional, defaults to 0 - see below
              cacheable: true  # required - see below
              contexts: booking  # optional, comma-separated, defaults to none - see below
```

Create the form type to define the `data` sub-fields, and the Twig template to render the block on the front end. The form data is stored as JSON in the `Block::$data` column.

`pickable` and `cacheable` don't have a functional default in practice - declare both explicitly on every kind, so the behavior is readable in `services.yaml` without having to check `BlockRegistryPass`'s fallback logic.

Set `pickable: false` for a **singleton** kind: one meant to be managed through its own dedicated EasyAdmin entry (see `c975L/SocialBundle`'s `SocialLinksCrudController` for an example) and rendered wherever needed via `BlockRepository::findOneByKind()`, rather than attached per-page. This hides it from the generic per-page block picker (`BlockRegistry::groupedByCategory()`), so editors can't accidentally create independent, separately-filled copies of it on individual pages. Regular, repeatable kinds (`card`, `text_section`, `contact_form`...) should use `true`.

`priority` controls the kind's position within its category in the picker (higher shows first, same convention as the `ui.stylesheet`/`ui.script` tags); kinds sharing the same priority (default `0`) fall back to alphabetical order.

The originating bundle of a kind is derived automatically from its `template`'s `@c975LXxx/...` Twig namespace (no tag attribute to fill in) and exposed via `BlockRegistry::getBundle()`/`groupedByBundle()` - the latter mirrors `groupedByCategory()` but groups by bundle instead, for building a showcase page per bundle rather than the kind-picker's functional grouping.

`contexts` restricts a kind to one or more named contexts (e.g. `menu`) instead of it being offered everywhere `BlockType` is used — useful for a kind that only makes sense on one entity, not on every `blocks` collection in the app (e.g. SiteBundle's `menu_link`, restricted to `contexts: menu` so it doesn't show up in a `Page`'s own block picker). Leave it unset (the default) for a kind meant to be usable anywhere, like the built-in kinds above. To make a `BlockType` field filter by context, pass the `context` form option:

```php
CollectionField::new('blocks')
    ->setEntryType(BlockType::class)
    ->setFormTypeOptions(['context' => 'menu'])
    // ...
```

A `CollectionField` that doesn't set `context` (the default, `null`) sees every pickable kind regardless of its declared `contexts` — existing integrations keep working unchanged until they opt in.

`media_required: true` rejects saving a block of that kind when it has no attached media at all (enforced by `RequiredMediaValidator` on the `Block` entity itself, not by the form) — use it for a kind whose media isn't optional decoration but the whole point of the block (e.g. `banner_title`'s background image). Defaults to `false`; only meaningful alongside `media_types`.

`media_multi_upload: true` adds a "select several files at once" input next to the usual one-file-per-row media collection, for a kind where editors routinely add many files at a time (e.g. `slider`, `article`) instead of clicking "Add" repeatedly. Each selected file becomes its own media entry, appended after the existing ones. Defaults to `false`; only meaningful alongside `media_types`.

---

## Block gallery

There is no EasyAdmin block gallery anymore - it was removed entirely, not just unlinked. Its preview variants needed inline scripts for interactivity (`slider`, `image_compare`...), and a hash/nonce-based CSP (e.g. `nelmio_security`'s `csp.hash` config) can never authorize a script trapped inside an `<iframe srcdoc="...">` attribute string - that class of CSP tooling scans a response's literal `<script>`/`<style>` elements, and content inside a `srcdoc` string is invisible to that scan. Not a bug in the gallery's own templates, a structural incompatibility.

The sidebar's "Links" section (see `c975L\UiBundle\Management\MenuProvider::getLinks()`) instead links out to <https://975l.com/vitrine-blocks>, the c975L ecosystem's own canonical showcase of every bundle's block kinds - rendered inline in a normal page, no iframe, no CSP conflict. This is a fixed link shipped by the bundle itself, the same for every consuming app.

The fixture/showcase machinery the old gallery used (`BlockFixtureProviderInterface`, `GalleryShowcaseProviderInterface`, `BlockFixtureMediaAttacher`...) wasn't removed - it's what powers that `/vitrine-blocks` page, and is available to any consuming app wanting to build its own equivalent showcase page (a plain controller/template, not an EasyAdmin/iframe one).

Kinds whose `media_types` (see above) start with `image/`, `video/` or `audio/` automatically get a placeholder attached (two images for `image_compare`, two images plus a video for `slider`, one otherwise) - no fixture provider needs to handle media itself. This is done by `c975L\UiBundle\Service\BlockFixtureMediaAttacher`, a public service that any consuming app can reuse for its own showcase page - see e.g. 975l.com's public `/vitrine-blocks`, which calls `attach(Block $block, string $kind)` the same way instead of maintaining its own app-specific media mapping. Images are drawn in rotation from a small pool of 5 (`BlockFixtureMediaAttacher::PLACEHOLDER_IMAGES`) rather than a single repeated one, so consecutive kinds on the same page don't all show the same photo - call `reset()` at the start of a request/loop building several blocks so the rotation restarts at the same photo every time. These photos are Laurent Marquet's own work, used here with his permission. The placeholder video (`public/videos/gallery-video.mp4`) is AI-generated - Video generated by Leonardo.Ai <https://leonardo.ai>. The placeholder audio clip (`public/audio/gallery-audio.mp3`) is also AI-generated - Music generated by Mubert <https://mubert.com/render>. A kind with no fixture at all should show a "no example yet" placeholder instead of crashing, same as the old gallery did.

### Providing sample data for your own kinds

Implement `BlockFixtureProviderInterface` (auto-discovered the same way as `BundleWhatsNewProviderInterface` - no tag needed, just register the service). Each kind maps to one or more named **variants** - use `''` as the only key when a single example is enough, or several labelled keys to show every visual style side by side (see `alert`'s info/success/warning/danger or `button`'s primary/secondary/success/danger/link in UiBundle's own `BlockFixtureProvider`):

```php
use c975L\UiBundle\Contract\BlockFixtureProviderInterface;

class BookingBlockFixtureProvider implements BlockFixtureProviderInterface
{
    public function getFixtures(): array
    {
        return [
            'booking' => [
                '' => ['title' => 'Réserver une table'],
            ],
        ];
    }
}
```

A kind with no registered fixture (empty array, or not returned at all) simply shows the "no example yet" placeholder - the gallery never breaks when a bundle hasn't caught up yet.

### Showcasing content that isn't a block kind

Some content is worth showing in the gallery but doesn't fit `BlockFixtureProviderInterface`, typically because rendering it through the real block/kind never reflects the fixture's own data anyway (e.g. SocialBundle's `social_links_display` always renders the site-wide singleton regardless of its own data, and `share_buttons()` isn't a block kind at all) or because the real template only renders something once resolved against live data this provider can't fabricate (e.g. SiteBundle's `articles_slider`/`menu_link`, resolved against a real `Page`/route). For these, implement `GalleryShowcaseProviderInterface` instead (same auto-discovery, no tag needed) and render the underlying component/Twig function directly with made-up sample data, bypassing whatever real lookup it would otherwise do:

```php
use c975L\UiBundle\Contract\GalleryShowcaseProviderInterface;
use Twig\Environment;

class BookingGalleryShowcaseProvider implements GalleryShowcaseProviderInterface
{
    public function __construct(private Environment $twig) {}

    public function getShowcases(): array
    {
        return [
            'Booking widget' => [
                'description' => 'Available layouts for the standalone booking widget.',
                // "kind" ties this to "booking"'s own category and suppresses its own regular preview
                // card (which would otherwise show up empty right next to this one) - use null if there's
                // no real block kind at all (e.g. share_buttons()). "category" overrides the category
                // directly instead (no suppression) - for a kind-less showcase that still belongs next
                // to a related one, e.g. reusing a sibling kind's own category key.
                'kind' => 'booking',
                'variants' => [
                    'Compact' => $this->twig->render('@App/booking/widget.html.twig', ['layout' => 'compact']),
                    'Full' => $this->twig->render('@App/booking/widget.html.twig', ['layout' => 'full']),
                ],
            ],
        ];
    }
}
```

Each variant is already-rendered HTML (a plain string) rather than a `Block` - the gallery wraps it in the same isolated `<iframe>` a block preview gets. `'wide' => true` originally rendered a showcase's card wider, for a component whose real styles only apply above a CSS breakpoint (e.g. `share_buttons()` hides itself entirely below 768px). The gallery now renders every item full-width by default, so this flag is currently a no-op there - it's kept in the interface so an existing provider that sets it (e.g. SocialBundle's `share_buttons()`) doesn't break. See UiBundle's own `BlockFixtureProvider`'s class comment, and SocialBundle's/SiteBundle's `GalleryShowcaseProvider` for real examples.

---

## Block render cache

`BlockExtension::renderBlock()` (called by the `render_block()` Twig function, itself used by the `<twig:c975LUi:Blocks:Block>` component) caches each block's rendered HTML in `cache.app` (via `TagAwareCacheInterface`), keyed by `block_render_{id}_{locale}` with an infinite TTL - no re-render, no DB round trip for the block's own data, on every subsequent hit across every visitor. `BlockCacheInvalidationListener` (`src/Listener/`) invalidates it automatically: it listens to `postUpdate`/`preRemove` on both `Block` and `Media` (an image/audio/video swap doesn't touch the parent `Block`'s own fields, so it has to be watched too) and calls `$cache->invalidateTags(['block_{id}'])`. This fires for *any* origin - EasyAdmin, an importer, another bundle - since it's a Doctrine listener on the entity class itself, not tied to a specific controller.

Locale is part of the cache key because a kind's template can render different content per `app.request.locale` even though `Block::$data` didn't change (e.g. SiteBundle's `legal_model`, which includes a different legal-text template per locale).

**Set `cacheable: false` on a kind whenever its rendered output isn't a pure function of `(Block::$id, Block::$data, locale)`** - i.e. whenever caching it under its own block id could serve stale or wrong-visitor content:

- **Embeds a per-request form** (CSRF token, session state): `ContactFormBundle`'s `contact_form` is the current example. A cached form would hand every visitor the same CSRF token.
- **Reads another Block's data**: SocialBundle's `social_links_display` is a data-less "pointer" kind that always renders the site-wide `social_links` singleton found via `BlockRepository::findOneByKind()`. Caching it under *its own* id would never see updates to the singleton it points at.
- **Queries unrelated entities live**: BookBundle's `book_series`/`book_books`/`book_to_be_published`/`book_serie_strips` list `Book`/`Serie`/`Strip` records via `BookBlockExtension`'s Twig functions - entities `BlockCacheInvalidationListener` doesn't watch, so a newly published book wouldn't ever invalidate the cache.

When in doubt, default to `cacheable: false`: the cost is one avoidable render per hit, not a correctness bug.

### Keeping a kind cacheable despite reading outside data

A kind that reads live data `BlockCacheInvalidationListener` doesn't watch would normally have to fall back to `cacheable: false` (see above) - implement `BlockCacheTagProviderInterface` instead (auto-discovered the same way as `BlockFixtureProviderInterface`, no tag needed) to keep it cacheable while adding your own extra cache tag(s) on top of the default `block_{id}`/`blocks_all` ones, then invalidate that tag yourself wherever the outside data actually changes:

```php
use c975L\UiBundle\Contract\BlockCacheTagProviderInterface;
use c975L\UiBundle\Entity\Block;

class ArticlesSliderCacheTagProvider implements BlockCacheTagProviderInterface
{
    public function getCacheTagResolvers(): array
    {
        return [
            'articles_slider' => fn (Block $block): array => ['articles_slider_' . $block->getData()['pageId']],
        ];
    }
}
```

SiteBundle's own `ArticlesSliderCacheTagProvider` is a real example: `articles_slider` resolves another `Page`'s own `article` blocks live at render time, so its listener tags the render with `articles_slider_{pageId}` and invalidates that tag whenever one of that page's articles changes.

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
| `<twig:c975LUi:Blocks:Blocks>` | Loops `Block` over a collection, auto-wraps consecutive `card` blocks in a `.cards` flex row |
| `<twig:c975LUi:Button:Button>` | Styled button/link |
| `<twig:c975LUi:Card:Card>` | Bootstrap card |
| `<twig:c975LUi:Card:Cards>` | Loops `Card` over an externally-supplied collection (no `Block` involved) |
| `<twig:c975LUi:Collection:Grid>` | Section title, followed by a grid of already-rendered items (see the `collection` block below) |
| `<twig:c975LUi:Cta:Band>` | Centered call-to-action panel (title/text/button) |
| `<twig:c975LUi:Expertise:Banner>` | Dark panel with text and a list of tags |
| `<twig:c975LUi:Feature:Bar>` | Row of short arguments (title + caption) |
| `<twig:c975LUi:General:RichSnippet>` | JSON-LD structured data snippet |
| `<twig:c975LUi:Hero:Hero>` | Header banner with title, subtitle, CTA buttons and image |
| `<twig:c975LUi:Image:Icon>` | Small icon image |
| `<twig:c975LUi:Image:Image>` | Responsive image |
| `<twig:c975LUi:Image:Link>` | Image wrapped in a link |
| `<twig:c975LUi:Pagination:Pagination>` | Pagination links |
| `<twig:c975LUi:Portfolio:Grid>` | Grid of project cards sourced from a block's own medias |
| `<twig:c975LUi:Process:Steps>` | Section title followed by numbered steps |
| `<twig:c975LUi:Progress:Bar>` | Progress bar |
| `<twig:c975LUi:Section:Cards>` | Section title followed by a grid of cards (icon/title/text) |
| `<twig:c975LUi:Slider:Slider>` | Image/media slider |
| `<twig:c975LUi:Text:Readmore>` | Collapsible "read more" text block |
| `<twig:c975LUi:Text:Section>` | Text section with optional image |
| `<twig:c975LUi:Video:Iframe>` | Embedded video iframe (YouTube etc.) |
| `<twig:c975LUi:Video:Video>` | HTML5 video player |

Props match the Twig variables used inside each template — see `templates/components/<Group>/<Name>.html.twig` for the exact list.

> **Maintenance note:** update this table whenever a component is added, renamed, or removed in `templates/components/`.

### Cards: a grid of teaser cards

Two independent ways to get a row of image + title + description + button cards, matching two
different sources:

**1. Entered by hand in EasyAdmin — several `card` blocks.** The `card` block kind carries its own
`vich_uploader`-managed image (`media_types: 'image/*'`, same mechanism as `article` — the media
belongs to that one block, no pairing with anything else) plus optional `url`/`target`/`buttonLabel`
fields. When a media and/or a `url` is set, `blocks/Card.html.twig` renders an image + button teaser
instead of the plain content box. There is no dedicated kind for **manually entered** cards: a
"collection of cards" editors fill in by hand is just several `card` blocks placed next to each other —
`<twig:c975LUi:Blocks:Blocks>` automatically wraps consecutive `card`-kind blocks in a `.cards` flex row
(see `templates/components/Blocks/Blocks.html.twig`), the same way several `article` blocks form a list
of articles. (For a grid pulled live from another bundle's own entities instead, see the `collection`
kind below.)

**2. Any bundle calling `<twig:c975LUi:Card:Cards>` directly — no `Block` entity involved.** The
component doesn't know or care where `items` comes from; each entry must expose:

| Key | Required | Notes |
| --- | --- | --- |
| `id` | no | HTML `id` of the `.card` element |
| `title` | no | Card header |
| `description` | no | Plain text, shown under the image |
| `image` | no | Full URL or path resolvable by `asset()` |
| `url` | no | Link target for the image and the button |
| `target` | no | `''` (default, same window) or `_blank` |
| `buttonLabel` | no | Defaults to `url` when empty |

```twig
{# e.g. from BookBundle, mapping its own query result #}
<twig:c975LUi:Card:Cards items="{{ books|map(book => {
    id: 'book-' ~ book.slug,
    title: book.title,
    description: book.summary,
    image: book.coverUrl,
    url: path('book_show', {slug: book.slug}),
}) }}" />
```

### Collection: a live grid sourced from another bundle

The `collection` kind lets an editor drop a section on a page that always shows the latest N items
from **another bundle's own entities** (books, products, projects...) — unlike `card`/`card`, no item
data is entered on the block itself, only which source to pull from (`source`), how many to show
(`limit`) and the surrounding section heading/link. Each item is resolved live at render time and
rendered through the exact same `card` block pipeline as a manually placed one, so it looks identical.
Not cacheable (`cacheable: false` in `config/services.yaml`) — its content depends on another bundle's
own entities, which `BlockCacheInvalidationListener` has no way to invalidate on.

A bundle exposes its entities to this block by implementing `CollectionSourceProviderInterface`
(auto-discovered the same way as `BlockFixtureProviderInterface` — no tag needed):

```php
use c975L\UiBundle\Contract\CollectionSourceProviderInterface;
use c975L\UiBundle\Model\CollectionItem;

class BookCollectionSourceProvider implements CollectionSourceProviderInterface
{
    public function __construct(private BookRepository $books) {}

    public function getSources(): array
    {
        return [
            'book.collection.books' => [
                'label' => 'Books',
                'items' => function (?int $limit): iterable {
                    foreach ($this->books->findLatest($limit) as $book) {
                        yield new CollectionItem(
                            title: $book->getTitle(),
                            description: $book->getSummary(),
                            imageUrl: $book->getCoverUrl(),
                            url: $book->getUrl(),
                        );
                    }
                },
            ],
        ];
    }
}
```

`imageUrl` is an already-resolved URL string, not a `Media`/entity reference — each provider is
responsible for resolving its own image storage before handing it back. A source removed since a
`collection` block was configured (e.g. its owning bundle uninstalled) doesn't break the page it's
still referenced from, it just renders an empty grid.

`CollectionItem` also takes `buttonLabel` (defaults to the raw `url` when empty) and `buttonIcon` (a
`c975L\UiBundle\Image\Icon` component `src`, e.g. an icon path from `social_link_icon()`) — both flow
straight into the transient `card` Block's own teaser button, so a collection item's call-to-action reads
the same as a manually placed `card`'s.

#### Item detail pages

A source can optionally expose a 3rd key, `detail`, alongside `label`/`items`:

```php
'items' => function (?int $limit): iterable { /* ... */ },
'detail' => fn (string $slug): ?array => $this->books->findOneBySlug($slug)?->toDetailData(),
```

`callable(string $slug): ?array` — given an item's own slug, return a plain array of template variables
(by convention always including `title`, used for that URL's `<title>`), or `null` if the slug doesn't
resolve to anything (the caller falls through to a 404). This lets every item in the source get its own
detail URL — `/pages/{page}/{slug}` for a `Page` carrying this `collection` block — with **no Page/Block
row persisted per item**: the data is rebuilt from the source on every request. Pair it with the
`collection` block's own `detailPage` field (the slug of a **real, separate `Page`** whose own blocks
render as this item's detail view, a `collectionItem` Twig global carrying the current item's data to
whichever of them needs it) — see SiteBundle's README ("Item detail pages", under "Collection entries")
for the full recipe and `PageController::resolveCollectionDetail()` for the resolution logic itself.

### Video:Iframe: consent-gated third-party embeds

`<twig:c975LUi:Video:Iframe>` (the `video_iframe` block) auto-rewrites YouTube URLs to
`youtube-nocookie.com`, and defers creating the real `<iframe>` client-side until cookie consent
is given — the block's own HTML never changes with consent state, so it stays cacheable.

It has **no composer dependency on any consent-banner bundle**. Instead it reacts to an optional,
documented contract, checked at connect time:

- a `[data-controller~="cookieConsent"]` element present somewhere on the page (if absent, it fails
  open and renders the iframe immediately — a site with no consent banner isn't blocked by this),
- a `window.CookieConsent` global exposing [`vanilla-cookieconsent`](https://cookieconsent.orestbida.com/)
  v3's API (`acceptedCategory('content')`, `acceptCategory('content')`),
- its `cc:onConsent`/`cc:onChange` DOM events, so the placeholder upgrades to the real iframe live,
  without a page reload, as soon as consent is given.

`c975l/site-bundle`'s `<twig:c975LSite:General:CookieConsent/>` is a ready-made provider of this
contract (see its own README) — but any consuming app's own banner satisfying the same contract
works just as well.

---

## PDF thumbnails

When a `.pdf` file is uploaded through VichUploader on **any entity** (no interface required), the bundle automatically generates a `.webp` thumbnail of the first page next to it (`document.pdf` → `document.webp` - the extension is replaced, not appended), via Ghostscript + Imagine/GD.

- **Requires Ghostscript** (`gs`) installed on the server. If missing, the thumbnail generation silently fails — the PDF upload itself is unaffected.
- **Skipped for private files** — entities implementing `VichPrivateFileInterface` (e.g. a paid download in a shop) are not thumbnailed, since there's no public preview use case for them.
- **Thumbnail width** defaults to `400px`, or reuses `getImageWidth()` if the entity also implements `VichImageResizableInterface`.

No configuration needed — handled by `VichPdfThumbnailListener`, auto-registered like the rest of the bundle's services.

---

## Site-wide media (favicon, logo, og-image)

A `Media` row isn't necessarily attached to a `Block` — it can instead hold one of a fixed set of site-wide graphics, identified by a `role`:

```php
use c975L\UiBundle\Entity\Media;

Media::ROLE_FAVICON;          // 'favicon'
Media::ROLE_APPLE_TOUCH_ICON; // 'apple-touch-icon'
Media::ROLE_OG_IMAGE;         // 'og-image'
Media::ROLE_LOGO;             // 'logo'
```

`role` is unique per value, so there is at most one `Media` for each. Create/replace one the same way as any other `Media` (e.g. from your own app's settings form or a fixture), setting `setRole(Media::ROLE_FAVICON)` — `UiMediaNamer` then stores it under a fixed, predictable filename at the root of `public/` instead of the usual per-block path.

Retrieve it anywhere in Twig with the `site_media()` function, which returns `null` if none was uploaded yet:

```twig
{% set favicon = site_media('favicon') %}
{% if favicon %}
    <link rel="icon" href="{{ vich_uploader_asset(favicon) }}">
{% endif %}
```

---

## Media Library

A `Media` row can be attached in several ways depending on the consuming bundle: to a `Block`, directly to another entity (e.g. a Page's og-image), or as a site-wide `role`. `MediaCrudController` provides a single EasyAdmin gallery browsing every `Media` regardless of how it's attached, with a click-through to edit its metadata (alt, caption, credits, CSS classes...). Site-wide role graphics stay read-only there — they keep being managed wherever the consuming bundle handles roles (e.g. `SiteGraphicCrudController` in c975L/SiteBundle). Creating a new `Media` directly from this library (with no `Block` yet) is reserved to `ROLE_SUPER_ADMIN` — regular admins keep adding media the normal way, through a block's own form.

UiBundle does **not** register a menu entry for it: `c975l/config-bundle` (which owns the menu registration mechanism, see below) already depends on `c975l/ui-bundle`, so the reverse would be a circular dependency. A bundle that depends on both - e.g. c975L/SiteBundle - should add an entry pointing to `MediaCrudController::class` in its own `MenuProviderInterface` implementation.

`Media::$url`/`Media::$description` back the per-project link and text of the `portfolio_grid` kind (see `MediaUploadType`'s `portfolio_grid` context) - a project card's title reuses the existing `$label` field.

### Declaring where a Media is used

UiBundle only knows about `Media`/`Block`; it has no visibility into which entity of a consuming bundle owns that Block, or holds a direct reference to a Media (like a Page's og-image). Each bundle that knows this can contribute that information:

1. Implement `MediaUsageProviderInterface` in your bundle - no tag needed, it's auto-discovered like `BundleWhatsNewProviderInterface`.
2. Return, for a given batch of already-loaded `Media` rows, the places they're used.

```php
use c975L\UiBundle\Contract\MediaUsageProviderInterface;
use c975L\UiBundle\Entity\Media;

class MyMediaUsageProvider implements MediaUsageProviderInterface
{
    public function getUsages(array $medias): array
    {
        // [mediaId => [['label' => string, 'url' => ?string], ...], ...]
        return [...];
    }
}
```

`url` can be `null` for a purely descriptive entry (no admin page to link to). Every registered provider's results are merged and shown in the "Used in" field of the Media Library's edit page.

---

## Automatic CSS injection

UiBundle provides a mechanism for bundles to declare their stylesheets automatically, without requiring manual `@import` or `<link>` additions in each application.

### How CSS injection works

1. Each bundle that provides CSS implements `BundleStylesheetProviderInterface` and registers itself with the `ui.stylesheet` service tag.
2. UiBundle collects all tagged providers at compile time (ordered by `priority`, highest first).
3. The `bundle_stylesheets()` Twig function returns the resolved list of URLs, ready for use in a layout template.

In `kernel.debug`, `bundle_stylesheets()` returns each bundle's stylesheet separately, for instant reload on every CSS edit. Outside debug (prod), it instead returns a single URL pointing to `public/bundles/build/site.css`, a concatenation of every registered local stylesheet built by `StylesheetCacheWarmer` (auto-registered, runs on `bin/console cache:warmup` / on first request after a cache clear - like any optional Symfony cache warmer). CDN stylesheets (absolute URLs) are excluded from that file and keep being linked on their own in both cases.

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

## Automatic CSS injection for EasyAdmin management pages

Same idea as above, but for CSS that should only load on the EasyAdmin dashboard (e.g. `/management`), not on the public site. Keep using plain `style="..."` attributes for one-off, low-value cases (a single `margin: 0` isn't worth an extra HTTP request) — reach for this mechanism when a management template accumulates real, reusable CSS (see `templates/management/media_index.html.twig` for an example).

### How it works

1. Each bundle that provides management-only CSS implements `BundleStylesheetManagementProviderInterface` and registers itself with the `ui.management_stylesheet` service tag.
2. UiBundle collects all tagged providers at compile time (ordered by `priority`, highest first) into `StylesheetManagementRegistry`.
3. The EasyAdmin `DashboardController::configureAssets()` method (in the app or in the bundle that owns the dashboard, e.g. `c975l/config-bundle`) injects `StylesheetManagementRegistry` and calls `addCssFile()` for each entry.

### Adding management CSS from your bundle

**Create a provider class** in your bundle:

```php
use c975L\UiBundle\Contract\BundleStylesheetManagementProviderInterface;

class StylesheetProvider implements BundleStylesheetManagementProviderInterface
{
    public function getManagementStylesheets(): array
    {
        return [
            'bundles/mybundle/css/management.min.css',
        ];
    }
}
```

**Register it with the tag** in `config/services.yaml`:

```yaml
services:
    MyBundle\Service\StylesheetProvider:
        tags:
            - { name: 'ui.management_stylesheet', priority: 10 }
```

The `priority` attribute is optional (default `0`). Higher priority providers are injected first — use a high value (e.g. `100`) for reset/base styles that must load before others.

### Consuming it in the dashboard controller

```php
use c975L\UiBundle\Registry\StylesheetManagementRegistry;

public function __construct(
    private readonly StylesheetManagementRegistry $stylesheetManagementRegistry,
) {}

public function configureAssets(): Assets
{
    $assets = Assets::new();

    foreach ($this->stylesheetManagementRegistry->all() as $stylesheet) {
        $assets->addCssFile($stylesheet);
    }

    return $assets;
}
```

Unlike the JS admin mechanism (`BundleScriptAdminProviderInterface`), no AssetMapper/importmap entry is needed — `addCssFile()` resolves plain public paths via Symfony's asset package, same as `getStylesheets()` above.

---

> [!TIP]
> If this project **helps you save development time**:
>
> - [**star** it on GitHub](https://github.com/975L/UiBundle) — helps others find it
> - [**open an issue**](https://github.com/975L/UiBundle/issues/new) to share how you use it — genuinely useful feedback
>
> And if you'd like to support the work directly, the **Sponsor** button at the top of the GitHub page is there for that. Thank you!

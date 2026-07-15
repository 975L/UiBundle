# ChangeLog

## v1.6

- Added `image_compare` block: draggable before/after image comparison slider (15/07/2026)
- Added `StylesheetCacheWarmer`: compiles registered stylesheets to `bundles/build/site.css`/`admin.css` outside `kernel.debug` (15/07/2026)
- Added `BlockRegistry::getBundle()`/`groupedByBundle()`, grouping block kinds by originating bundle (15/07/2026)
- Changed block gallery access role from `ROLE_SUPER_ADMIN` to `ROLE_EDITOR` (15/07/2026)
- Changed Media library `New` action to `ROLE_SUPER_ADMIN`, bumped max upload size to 100M (15/07/2026)
- Added `BlockUserListener::preUpdate()`, tracking the last editor instead of only the creator (15/07/2026)
- Fixed `Media::isOgImage()` false-positive on any role-less/block-less Media (15/07/2026)
- Fixed freeflow slider autoplay scrolling the whole page instead of just its own slide list (15/07/2026)
- Removed custom composer `vendor-dir`, back to standard `vendor/` (15/07/2026)
- Fixed the compiled `bundles/build/site.css` URL not busting caches across deploys, now versioned by the compiled file's own mtime (15/07/2026)
- Suppressed duplication between `BlockRegistry::groupedByCategory()`/`groupedByBundle()`, now sharing a private `groupBy()` helper (15/07/2026)
- Added `BlockIdGenerator`, shared by `SliderType`/`ImageCompareType` instead of each duplicating the same id-generation line (15/07/2026)
- Changed block gallery to a full-width single-column layout with a jump-to table of contents, instead of a grid of fixed-width cards (15/07/2026)
- Added `BlockFixtureMediaAttacher`, a shared service attaching placeholder media to fixture blocks - reused by any consuming app's own block showcase, not just the gallery (15/07/2026)
- Changed block gallery placeholder media to a rotating pool of real photos/video/audio, instead of a single generic image (15/07/2026)
- Changed slider `freeflow` gallery preview to 5 images instead of 3 (15/07/2026)
- Fixed `video_iframe` gallery preview autoplaying with sound, now muted (15/07/2026)
- Fixed `expertise_banner` text color turning black on its dark background for unclassed rich-text content (15/07/2026)

## v1.5.4

- Added test to trigger deprecations (14/07/2026)

## v1.5.3

- Suppressed DependencyInjection/Configuration.php as not needed (14/07/2026)

## v1.5.2

- Added `hero`, `feature_bar`, `section_cards`, `expertise_banner`, `process_steps`, `portfolio_grid`, `cta_band` page section blocks (14/07/2026)
- Added `Media::$url`/`Media::$description` columns, used by `portfolio_grid` (14/07/2026)

## v1.5.1

- Corrected slider default layout (13/07/2026)

## v1.5

- Added help text to the Media library CRUD index (13/07/2026)
- Fixed `render_block()` caching every never-persisted block (id `null`) under the same cache key (13/07/2026)
- Fixed `.btn-success`/`.btn-danger`/`.btn-link` styles (13/07/2026)
- Added block gallery (`/management/ui/block/gallery`), previewing every pickable block kind with sample data (13/07/2026)
- Added `BlockFixtureProviderInterface`: lets a bundle supply sample data for its own kinds in the block gallery (13/07/2026)
- Added `GalleryShowcaseProviderInterface`: lets a bundle show non-block content in the block gallery (13/07/2026)
- Added `GalleryShowcaseProviderInterface`'s `category` override, for a kind-less showcase that still belongs next to a related category (13/07/2026)
- Added `contexts` block tag flag, restricting a kind to specific `BlockType` usages (e.g. `menu`) (13/07/2026)
- Added multi upload files feature (13/07/2026)
- Added Block BannerTitle (13/07/2026)
- Added `media_required` block tag flag, rejecting a block with no attached media at save time (13/07/2026)
- Added ability to add videos to the slider (13/07/2026)
- Added freeflow parameter for Slider (13/07/2026)

## v1.4.13

- Corrected sass for slider withu unique slide (13/07/2026)
- Added Dashoard's shortcut (13/07/2026)

## v1.4.12

- Added invalidate cache for blocks (13/07/2026)

## v1.4.11

- Moved tests to the right place (13/07/2026)

## v1.4.10

- Corrected What's new date (12/07/2026)
- Added tests (13/07/2026)
- Modified media library functionalities (12/07/2026)

## v1.4.9

- Added pickable on services.yaml (12/07/2026)
- Added server cache on Blocks (12/07/2026)
- Added animations on blocks (12/07/2026) [DB-Migration]
- Re-numbered xlf files (12/07/2026)

## v1.4.8

- Updated What's new (11/07/2026)
- Updated iconPicker (11/07/2026)

## v1.4.7

- Corrected Favicon/AplleTouchIcon to disallow svg format (10/07/2026)
- Added isPickable on Blocks (10/07/2026)
- Modified iconPicker (11/07/2026)

## v1.4.6

- Modified Block Image (09/07/2026)
- Added icon-picker.js (09/07/2026)
- Renamed css classes img-xxx to .width-xxx (09/07/2026)
- Re-ordered xlf files (10/07/2026)
- Added hook to article (10/07/2026)
- Added trix tools to center text (10/07/2026)
- Added media to Card Block (10/07/2026)
- Added resize/namig for favicon.ico and apple-touch-icon.png (10/07/2026)

## v1.4.5

- Added automatic CSS injection for EasyAdmin management pages (08/07/2026)
- Added possibility to upload user defined error images (08/07/2026)

## v1.4.4.1

- Fixed CompilerPass (05/07/2026)

## v1.4.4

- Added a cross-bundle Media Library in EasyAdmin: browse every `Media` regardless of how it's attached (Block, Page og-image, site-wide role...), see where it's used via a new `MediaUsageProviderInterface` extension point, and edit its metadata (05/07/2026)
- Restyled the Slider navigation dots (ring style with an `active` state) and fixed slide transitions extending the page's scrollable area by clipping `.slider` overflow (05/07/2026)

## v1.4.3

- Added duplication of Media (04/07/2026)
- Added duplication of Block (04/07/2026)
- Added a What's new file that will appear on main dashboard + menu (04/07/2026)
- Added live preview of a newly picked image in EasyAdmin, before saving (05/07/2026)
- Added site-wide media roles (favicon, apple-touch-icon, og-image, logo) on `Media`, retrievable via the `site_media()` Twig function (05/07/2026) [Needs db update]
- Added touch gestures (swipe to navigate, press-and-hold to pause) to the Slider block (05/07/2026)
- Moved Menu/MenuItem + sass related to c975L/SiteBundle (05/07/2026)

## v1.4.2

- Added auto-scroll/focus to the newly added block row in the "blocks" collection (04/07/2026)
- Added an optional translated `description`, shown under the label in the block kind picker (04/07/2026)
- Added BoolExtension to manage boolean values in twig (04/07/2026)
- Added description for Blocks (04/07/2026)
- Corrected deletion of physical file when deleting media in Block (04/07/2026)

## v1.4.1

- Fixed media files (image, etc.) not being saved when picking a block kind with media on a form not yet multipart (04/07/2026)
- Fixed Slider display for title and text (04/07/2026)
- Added possibility to use prop message for Alert (04/07/2026)

## v1.4

- Taken sass from c975L/SiteBundle (01/07/2026)
- Separated Blocks system from components as to be used in other bundles (01/07/2026)
- Corrected bug when adding more than one block (02/07/2026)
- Added kind of autoload for js controllers (02/07/2026)
- Added field on Media entity (02/07/2026) [Needs db update]
- Restored per-slide `credits`/`rightsReserved` on Slider (02/07/2026)
- Added a display `ratio` choice (free or fixed) on the Slider block (02/07/2026)
- Added translation for blocks label and category, using bundle defined translation domain (04/07/2026)

## v1.3.1

- Converted blocks.js to Stimulus controller (28/06/2026)

## v1.3

- Added TrixEditor for FormType (27/06/2026)
- Added animations from c975L/SiteBundle (27/06/2026)
- Removed dependency on page for blocks (27/06/2026)
- Transformed block.js in a js module (27/06/2026)
- Added a system to autoload CSS files from bundles (27/06/2026)

## v1.2

- Suppressed Stimulus Component (27/06/2026)
- Added UI related files from c975L/SiteBundle (27/06/2026)

## v1.1

- Added lots of things... (26/06/2026)

## v1.0

- Added native blocks and system to manage them (25/06/2026)

## v0.1.1

- Updated composer.json (24/06/2026)

## v0.1

- Creation of bundle (24/06/2026)

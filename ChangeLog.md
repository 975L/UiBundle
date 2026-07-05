# ChangeLog

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

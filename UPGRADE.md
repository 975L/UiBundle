# UPGRADE

## > v1.5

`Media` gained two columns (`credits`, `rights_reserved`) used by the Slider block - run `bin/console doctrine:migrations:diff` then `doctrine:migrations:migrate` in the consuming app. Slider slides no longer expose the `label`/`width`/`height`/`above` fields (they were meant for the standalone Image block); existing data in these columns is untouched, just no longer editable/displayed for Slider media.

Front and admin Stimulus controllers from c975L bundles are auto-discovered: a bundle just implements `BundleScriptProviderInterface` (front) and/or `BundleScriptAdminProviderInterface` (admin), tags itself, nothing else to wire in `c975L/SiteBundle`'s layout or `c975L/ConfigBundle`'s Dashboard for that part. But AssetMapper only rewrites a file's internal relative imports (e.g. `import Foo from './js/foo.js'`) to their digested public path if that file has an entry in `importmap.php` - so **every bundle providing controllers still needs its own `importmap.php` line** (a Symfony/AssetMapper constraint, not something that can be avoided).

**`importmap.php`** - add one entry per bundle providing controllers, always with `'entrypoint' => true`:

```php
'@c975l/ui-bundle/controllers.js' => [
    'path' => './vendor/c975l/ui-bundle/assets/controllers.js',
    'entrypoint' => true,
],
'@c975l/ui-bundle/controllers-admin.js' => [
    'path' => './vendor/c975l/ui-bundle/assets/controllers-admin.js',
    'entrypoint' => true,
],
'@c975l/site-bundle/controllers.js' => [
    'path' => './vendor/c975l/site-bundle/assets/controllers.js',
    'entrypoint' => true,
],
'@c975l/site-bundle/controllers-admin.js' => [
    'path' => './vendor/c975l/site-bundle/assets/controllers-admin.js',
    'entrypoint' => true,
],
```

This is the **only** thing to remember per bundle from now on - front layout and admin Dashboard pick it up automatically.

**Layout** (already done in `c975L/SiteBundle`'s own `layout.html.twig` if you extend it - nothing to do):

```twig
{{ importmap(['app']|merge(bundle_scripts()), {'nonce': csp_nonce('script')}) }}
```

## v4.x > v5.x

Made use of database to store config parameters. Needs a databse migration.

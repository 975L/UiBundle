# UPGRADE

## Unreleased

`c975L\SiteBundle\Service\FormBotProtection` moved here as `c975L\UiBundle\Service\FormBotProtection`, merged with ContactFormBundle's own rotating honeypot (field name/label now rotate per session instead of the fixed `website` field SiteBundle used) - update any `use` referencing the old class. `addHoneypotField()` now takes the current `Request` as a second argument (needed to read/generate the rotated field name/label) - a `FormType` calling it needs `RequestStack` injected to obtain it, see `RegistrationFormType`/`ResetPasswordRequestFormType` in the scaffold for the pattern.

`c975L\ContactFormBundle\Service\ReCaptchaFactory`/`Form\Extension\Recaptcha3TypeExtension`/`DependencyInjection\CompilerPass\RecaptchaPass` moved here under `c975L\UiBundle\Service\ReCaptchaFactory`/`Form\Extension\Recaptcha3TypeExtension`/`DependencyInjection\Compiler\RecaptchaPass` - if you referenced them directly, update the namespace. Requires `karser/karser-recaptcha3-bundle` (already a ContactFormBundle dependency, now also a UiBundle one - a no-op if that bundle isn't registered in the app).

`c975L\ContactFormBundle\Service\EmailService`/`EmailServiceInterface` moved here as a generic `c975L\UiBundle\Service\EmailService`, no longer tied to `ContactForm`/`ContactFormEvent` - it now takes a `c975L\UiBundle\Model\EmailSendRequest` and exposes errors via `getLastError()` instead of mutating an event. Requires `symfony/mailer` and `symfony/security-bundle` (both new UiBundle dependencies - `symfony/security-bundle` was already pulled transitively by most apps, `symfony/mailer` is new). New built-in `c975L\UiBundle\Service\SendEmailFormAction` (`FormActionInterface` key `send_email`), configured per-`Form` via the new `Form::$actionConfig` JSON column (`to`/`from`/`replyTo`/`subject`/`template`/`senderEmailField`/`offerReceiveCopy`) - default template `@c975LUi/emails/form_submission.html.twig` if none set.

`Form` also gained `$restricted` (bool, same principle as `FormField::$restricted`) - a seeded Form (e.g. ContactFormBundle's "contact") gets its `name` locked in the admin, and `$actionConfig` (JSON, nullable) - free-shape config read by whichever action is configured. Run `doctrine:migrations:diff`/`doctrine:migrations:migrate` for the new `site_form` columns.

Added `Controller/Management/FormCrudController` - a generic "manage any Form" admin screen. Link it yourself (or via `c975l/site-bundle`'s "Forms" menu entry, which already does) if you built your own dashboard menu.

The `form` Block/`FormController`/`FormSubmissionType` now add the same honeypot/timing/GDPR/recaptcha protection contact/register/reset already had, plus an optional shared rate limiter (`limiter.ui_form`, configure it in `config/packages/rate_limiter.yaml` like `limiter.registration`/`limiter.reset_password` already are - a single shared one, since a Form built through the admin can't be bound to its own dedicated named DI service). `FormSubmissionType`'s constructor gained `FormBotProtection`/`ConfigServiceInterface`/`RequestStack` - if you instantiate it directly (rather than via the form factory), update the call. `Form::$actionConfig`'s `receiveCopy` key (fixed admin choice) is renamed `offerReceiveCopy` (shows a checkbox, the visitor's own answer decides) - `SendEmailFormAction` now reads the submitted `receiveCopy` value, not the config flag.

`FormController`'s constructor gained a required `Service/FormPrefillHelper` argument (autowired, nothing to do if you use the service container) - update the call if you instantiate it directly. Call `FormPrefillHelper::prefill($request, $formName, ['fieldName' => $value])` right before redirecting a visitor to a Form's page (e.g. a listing's "Contact us about this" button redirecting to the "contact" Form's page) - the matching field(s) get pre-filled and turned readonly, cleared automatically once the submission succeeds. Replaces the need for ContactFormBundle's `?s=...` query string.

## > v1.6

The `video_iframe` block's markup changed: `templates/components/Video/Iframe.html.twig` used to render a bare `<iframe>` directly, it now renders a wrapping `<div>` and creates the `<iframe>` client-side (gated behind cookie consent if a `window.CookieConsent`-exposing banner is present on the page, see the README's "Video:Iframe" section - otherwise it renders immediately, same as before). If your CSS/JS specifically targets that block's `<iframe>` element, update the selector to target the new wrapper instead.

## > v1.5

`Media` gained two columns (`credits`, `rights_reserved`) used by the Slider block - run `bin/console doctrine:migrations:diff` then `doctrine:migrations:migrate` in the consuming app. Slider slides no longer expose the `label`/`width`/`height`/`above` fields (they were meant for the standalone Image block); existing data in these columns is untouched, just no longer editable/displayed for Slider media.

`Media` also gained a nullable, unique `role` column (`Media::ROLE_FAVICON`, `ROLE_APPLE_TOUCH_ICON`, `ROLE_OG_IMAGE`, `ROLE_LOGO`) for site-wide graphics not attached to any `Block`, and its `block` FK is now nullable to allow that - run the same `doctrine:migrations:diff` / `doctrine:migrations:migrate` to pick up both changes. Fetch a role's `Media` anywhere in Twig with `site_media('favicon')` (returns `null` if none was uploaded yet).

The `<twig:c975LUi:Menu:Menu>` and `<twig:c975LUi:Menu:MenuItem>` components, and their sass, moved to `c975L/SiteBundle` - update any template still referencing them from UiBundle.

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

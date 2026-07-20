<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Contract;

// Lets an app/bundle (e.g. SiteBundle) wrap an EmailTemplateRenderer-produced body in its own branded email
// layout (header/footer, no-spam text, legal mentions...) so EmailTemplateCrudController::preview() and a real
// EmailTemplate-based send (e.g. SendEmailFormAction) both render the same way a recipient would actually see it
// - see EmailLayoutRegistry/EmailLayoutProviderPass. With none registered, EmailTemplateRenderer falls back to
// its own standalone document (_wrapper.html.twig)
interface EmailLayoutProviderInterface
{
    public function wrap(string $bodyHtml): string;
}

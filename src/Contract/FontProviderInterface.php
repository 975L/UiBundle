<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Contract;

// Lets an app/bundle (e.g. SiteBundle) expose the font-family names it declares via @font-face in its own
// CSS, so ConfigBundle's ConfigCrudController can render a real <select> for "font" kind configs (e.g.
// theme-font-family-title) instead of free text - see FontRegistry/FontProviderPass/FontChoiceType. With
// none registered, callers fall back to their own default behavior (e.g. a plain text field)
interface FontProviderInterface
{
    public function getFonts(): array;
}

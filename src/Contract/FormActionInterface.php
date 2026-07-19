<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Contract;

use c975L\UiBundle\Entity\Form;

// Implement to process a Form's submission (see FormActionRegistry) for the action key its owning bundle sets on Form::$action (e.g. ContactFormBundle's "send_email"), letting satellite bundles handle their own forms without UiBundle knowing about email/security/etc.
interface FormActionInterface
{
    public function getKey(): string;

    // $submittedData: submitted value keyed by each FormField's own "name" (see FormField::getName())
    public function handle(Form $form, array $submittedData): bool;
}

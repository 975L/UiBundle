<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Service;

use c975L\ConfigBundle\Controller\Management\ConfigCrudController;
use c975L\ConfigBundle\Entity\Config;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;

// Builds a Config's own EasyAdmin edit URL (ConfigBundle's ConfigCrudController), falling back to the plain
// Config list when $config is null (e.g. a slug not yet loaded into DB, a site that never ran
// config:load-all) - shared by Twig\ConfigLinkExtension (one ad hoc slug) and
// Controller\Management\AiAssistantController::configLinks() (a known batch of slugs)
class ConfigEditUrlResolver
{
    public function __construct(private readonly AdminUrlGeneratorInterface $adminUrlGenerator)
    {
    }

    // unsetAll() first: AdminUrlGenerator::generateUrl() never resets its own internal route parameters, so
    // reusing one builder instance across calls would leak the previous config's entityId into the next url
    public function resolve(?Config $config): string
    {
        $urlGenerator = $this->adminUrlGenerator->unsetAll()->setController(ConfigCrudController::class);

        return $config
            ? $urlGenerator->setAction(Action::EDIT)->setEntityId($config->getId())->generateUrl()
            : $urlGenerator->setAction(Action::INDEX)->generateUrl();
    }
}

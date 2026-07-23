<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Management;

use c975L\ConfigBundle\Management\DashboardWidgetProviderInterface;
use c975L\UiBundle\Contract\AiAssistantClientInterface;
use Symfony\Bundle\SecurityBundle\Security;

// Donovan's card on Config's dashboard home - deliberately no "not enabled yet" placeholder: exactly
// the same condition as AiAssistantController::index()'s own question-box branch
// (AiAssistantClientInterface::isEnabled() + ROLE_SUPER_ADMIN, see _ai_assistant_base.html.twig), so
// as long as either isn't true the widget stays entirely absent - AiAlertProvider is the only nudge
// pointing at the dedicated setup page in that case.
class DonovanWidgetProvider implements DashboardWidgetProviderInterface
{
    public function __construct(
        private readonly AiAssistantClientInterface $aiAssistantClient,
        private readonly Security $security,
    ) {
    }

    public function getDashboardWidgets(): array
    {
        if (!$this->aiAssistantClient->isEnabled() || !$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return [];
        }

        return [
            [
                'template' => '@c975LUi/management/_donovan_dashboard_widget.html.twig',
                'context' => [],
            ],
        ];
    }
}

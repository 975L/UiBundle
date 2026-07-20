<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\DependencyInjection\Compiler;

use Nelmio\SecurityBundle\EventListener\ContentSecurityPolicyListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// A no-op unless nelmio/security-bundle is actually registered in the consuming app - NelmioSecurityBundle
// never aliases its listener by FQCN itself, so without this pass FormSubmissionType's optional
// "?ContentSecurityPolicyListener $cspListener" autowires to null and recaptcha's inline script renders
// with an empty nonce, always violating a strict CSP
class CspListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('nelmio_security.csp_listener')) {
            return;
        }

        $container->setAlias(ContentSecurityPolicyListener::class, 'nelmio_security.csp_listener');
    }
}

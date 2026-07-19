<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\DependencyInjection\Compiler;

use c975L\UiBundle\DependencyInjection\Compiler\RecaptchaPass;
use c975L\UiBundle\Service\ReCaptchaFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RecaptchaPassTest extends TestCase
{
    public function testProcessDoesNothingWhenRecaptchaBundleIsNotRegistered(): void
    {
        $container = new ContainerBuilder();

        (new RecaptchaPass())->process($container);

        $this->assertFalse($container->hasDefinition(ReCaptchaFactory::class));
    }

    public function testProcessRewiresTheRecaptchaDefinitionWhenBundleIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('karser_recaptcha3.secret_key', 'fallback_secret');
        $container->setParameter('karser_recaptcha3.score_threshold', 0.5);
        $container->register('karser_recaptcha3.google.recaptcha')
            ->addMethodCall('setScoreThreshold', [0.5]);

        (new RecaptchaPass())->process($container);

        $this->assertTrue($container->hasDefinition(ReCaptchaFactory::class));

        $definition = $container->getDefinition('karser_recaptcha3.google.recaptcha');
        $this->assertEquals([new Reference(ReCaptchaFactory::class), 'create'], $definition->getFactory());
        $this->assertEquals(
            ['fallback_secret', new Reference('karser_recaptcha3.google.request_method'), 0.5],
            $definition->getArguments()
        );
        $this->assertFalse($definition->hasMethodCall('setScoreThreshold'));
    }
}

<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Service;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use Karser\Recaptcha3Bundle\ReCaptcha\ReCaptcha;
use Karser\Recaptcha3Bundle\ReCaptcha\RequestMethod;

// Moved from ContactFormBundle so any form can use reCAPTCHA v3, not just the contact form - see RecaptchaPass
class ReCaptchaFactory
{
    public function __construct(private readonly ConfigServiceInterface $configService) {}

    public function create(string $fallbackSecret, RequestMethod $requestMethod, float $fallbackScoreThreshold): ReCaptcha
    {
        $secret = $fallbackSecret;
        if ($this->configService->hasParameter('recaptcha3-secret-key')) {
            $configSecret = $this->configService->get('recaptcha3-secret-key');
            if ($configSecret) {
                $secret = $configSecret;
            }
        }

        $scoreThreshold = $fallbackScoreThreshold;
        if ($this->configService->hasParameter('recaptcha3-score-threshold')) {
            $configScoreThreshold = $this->configService->get('recaptcha3-score-threshold');
            // Not a truthy check: 0 is a deliberate, valid threshold (accept everything), not "unset"
            if (null !== $configScoreThreshold && '' !== $configScoreThreshold) {
                $scoreThreshold = (float) $configScoreThreshold;
            }
        }

        return (new ReCaptcha($secret, $requestMethod))->setScoreThreshold($scoreThreshold);
    }
}

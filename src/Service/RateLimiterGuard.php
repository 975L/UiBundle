<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Service;

use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

// Consumes an optional rate limiter, extracted from ContactFormBundle's ContactFormService - $limiterFactory stays
// nullable (a form with no configured limiter, i.e. the app never defined the named "limiter.xxx" service, is simply
// never rate-limited) but is typed against the real Symfony interface, since symfony/rate-limiter is a soft
// dependency the caller opts into, not one UiBundle forces on every consumer
class RateLimiterGuard
{
    public function isAccepted(?RateLimiterFactoryInterface $limiterFactory, string $key): bool
    {
        if (null === $limiterFactory) {
            return true;
        }

        return $limiterFactory->create($key)->consume(1)->isAccepted();
    }
}

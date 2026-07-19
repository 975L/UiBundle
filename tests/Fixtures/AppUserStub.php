<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// App\Entity\User belongs to the consuming application, not to this standalone bundle checkout - BlockUserListener still type-checks against it directly (`$user instanceof User`), so any test needing a real logged-in user (not just the "nobody logged in" branch) needs a minimal stand-in. Guarded so this stays harmless if a real App\Entity\User is ever autoloadable in the same process.
namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

if (!class_exists(__NAMESPACE__ . '\User', false)) {
    class User implements UserInterface
    {
        public function __construct(private string $identifier = 'stub-user')
        {
        }

        public function getRoles(): array
        {
            return ['ROLE_USER'];
        }

        public function getUserIdentifier(): string
        {
            return $this->identifier;
        }
    }
}

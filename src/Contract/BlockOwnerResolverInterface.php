<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Contract;

// Lets any bundle owning a HasBlocksInterface entity (Page/Menu in SiteBundle, Book/Strip/Serie in
// BookBundle...) make it reachable by BlockMoveController without UiBundle depending on any of their
// concrete classes. Implement this and the service is auto-discovered by BlockOwnerResolverPass - see Readme
interface BlockOwnerResolverInterface
{
    // $ownerType: a short, stable string identifying the owning entity (e.g. "page", "menu") - chosen by
    // the implementing bundle, round-tripped verbatim by the client that requested the move
    public function supports(string $ownerType): bool;

    public function find(string $ownerType, int $ownerId): ?HasBlocksInterface;
}

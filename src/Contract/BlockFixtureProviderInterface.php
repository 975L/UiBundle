<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Contract;

// Implement to feed a block showcase (see BlockFixtureRegistry, consumed by 975l.com's public
// /vitrine-blocks) with sample data for the block kinds a bundle registers via the "ui.block" tag -
// lets satellite bundles show their own kinds there without UiBundle knowing about them
interface BlockFixtureProviderInterface
{
    // One entry per covered kind: kind => [variant label => data]. "data" has the same shape Block::
    // setData() stores. A kind with a single, unlabelled example can use '' as its only variant key -
    // a consumer typically only displays variant labels when a kind has more than one (e.g. "alert"
    // showing its info/success/warning/danger styles side by side).
    public function getFixtures(): array;
}

<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Maker;

use c975L\UiBundle\Maker\MakeDonovanQaCommand;
use PHPUnit\Framework\TestCase;

// Only covers the deterministic, IO-free parts of the Maker - generate() delegates to MakerBundle's own
// Generator (file writing) which needs a real booted app to exercise meaningfully, so it's verified
// manually via "bin/console c975l:ui:donovan-qa:create" (see 975l.com, where it was run once against a
// scratch output and diffed against the hand-written reference implementation)
class MakeDonovanQaCommandTest extends TestCase
{
    public function testCommandNameMatchesCustomC975lConvention(): void
    {
        $this->assertSame('c975l:ui:donovan-qa:create', MakeDonovanQaCommand::getCommandName());
    }

    public function testCommandDescriptionIsNotEmpty(): void
    {
        $this->assertNotSame('', MakeDonovanQaCommand::getCommandDescription());
    }
}

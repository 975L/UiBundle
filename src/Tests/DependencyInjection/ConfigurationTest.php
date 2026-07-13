<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\DependencyInjection;

use c975L\UiBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

// The bundle currently exposes no semantic configuration (see Configuration::getConfigTreeBuilder) -
// this only guards against a regression (e.g. an empty array config no longer processing cleanly)
class ConfigurationTest extends TestCase
{
    public function testEmptyConfigProcessesToAnEmptyArray(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), []);

        $this->assertSame([], $config);
    }

    // An unexpected key must still be rejected, confirming the tree builder is actually wired up
    // (root name "c975_l_ui") rather than silently accepting anything
    public function testUnknownKeyIsRejected(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [['unexpected_key' => true]]);
    }
}

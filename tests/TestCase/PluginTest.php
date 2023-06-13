<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase;

use BEdita\ElasticSearch\Plugin;
use Cake\TestSuite\TestCase;

/**
 * {@see BEdita\ElasticSearch\Plugin} Test Case
 *
 * @coversDefaultClass \BEdita\ElasticSearch\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * Dummy test
     *
     * @return void
     * @covers ::dummy()
     */
    public function testDummy(): void
    {
        $plugin = new Plugin();
        $actual = $plugin->dummy();
        static::assertSame('dummy', $actual);
    }
}

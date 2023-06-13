<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase;

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
     */
    public function testDummy(): void
    {
        static::assertSame('dummy', 'dummy');
    }
}

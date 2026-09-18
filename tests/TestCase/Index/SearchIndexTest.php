<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase\Index;

use BEdita\ElasticSearch\Model\Index\SearchIndex;
use Cake\Datasource\ConnectionManager;
use Cake\ElasticSearch\TestSuite\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * {@see \BEdita\ElasticSearch\Model\Index\SearchIndex} Test Case
 */
#[CoversClass(SearchIndex::class)]
class SearchIndexTest extends TestCase
{
    protected SearchIndex $index;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->index = new SearchIndex([
            'connection' => ConnectionManager::get('test_elastic'),
            'name' => 'testindex',
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->index->indexExists()) {
            $this->index->getConnection()->getIndex($this->index->getName())->delete();
        }
    }

    /**
     * Test `getName` method when the name has been explicitly set.
     *
     * @return void
     */
    public function testGetName(): void
    {
        static::assertSame('testindex', $this->index->getName());
    }

    /**
     * Test `getDefaultName` method.
     *
     * @return void
     */
    public function testGetDefaultName(): void
    {
        $index = new class (['connection' => ConnectionManager::get('test_elastic')]) extends SearchIndex {
            private int $depth = 0;

            // Bound the recursion, or a failure exhausts memory instead of reporting.
            public function getName(): string
            {
                if ($this->depth++ > 0) {
                    Assert::fail('`SearchIndex::getName()` recursed via `Index::getAlias()`');
                }

                return parent::getName();
            }
        };

        /** @var \Cake\Database\Driver $driver */
        $driver = ConnectionManager::get('default')->getDriver();
        static::assertStringStartsWith($driver->schema() . '_', $index->getName());
    }

    /**
     * Test `create` method.
     *
     * @return void
     */
    public function testCreate()
    {
        $result = $this->index->create();
        static::assertTrue($result);
    }

    /**
     * Test `indexExists` method.
     *
     * @return void
     */
    public function testIndexExists()
    {
        static::assertFalse($this->index->indexExists());
        static::assertTrue($this->index->create());
        static::assertTrue($this->index->indexExists());
    }
}

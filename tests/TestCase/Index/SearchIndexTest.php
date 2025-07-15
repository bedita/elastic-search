<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase\Index;

use BEdita\ElasticSearch\Model\Index\SearchIndex;
use Cake\Datasource\ConnectionManager;
use Cake\ElasticSearch\TestSuite\TestCase;

/**
 * {@see \BEdita\ElasticSearch\Model\Index\SearchIndex} Test Case
 *
 * @coversDefaultClass \BEdita\ElasticSearch\Model\Index\SearchIndex
 */
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

        $this->index->getConnection()->getIndex($this->index->getName())->delete();
    }

    /**
     * Test `create` method.
     *
     * @return void
     * @covers ::create()
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
     * @covers ::indexExists()
     * @covers ::create()
     */
    public function testIndexExists()
    {
        static::assertFalse($this->index->indexExists());
        static::assertTrue($this->index->create());
        static::assertTrue($this->index->indexExists());
    }
}

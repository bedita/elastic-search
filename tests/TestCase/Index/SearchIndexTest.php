<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase\Index;

use BEdita\ElasticSearch\Model\Index\ObjectSearchIndex;
use BEdita\ElasticSearch\Model\Index\SearchIndex;
use Cake\Datasource\ConnectionManager;
use Cake\ElasticSearch\TestSuite\TestCase;
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
     * Test the index name, both configured and derived from the class name.
     *
     * @return void
     */
    public function testGetName(): void
    {
        static::assertSame('testindex', $this->index->getName());
        static::assertSame('object_search', (new ObjectSearchIndex())->getName());
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

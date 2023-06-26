<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase\Adapter;

use BEdita\ElasticSearch\Adapter\ElasticSearchAdapter;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use ReflectionClass;

/**
 * {@see BEdita\ElasticSearch\Adapter\ElasticSearchAdapter} Test Case
 *
 * @coversDefaultClass \BEdita\ElasticSearch\Adapter\ElasticSearchAdapter
 */
class ElasticSearchAdapterTest extends TestCase
{
    /**
     * Test `buildElasticSearchQuery` method
     *
     * @return void
     * @covers ::buildElasticSearchQuery()
     */
    public function testBuildElasticSearchQuery(): void
    {
        $reflectionClass = new ReflectionClass(ElasticSearchAdapter::class);
        $method = $reflectionClass->getMethod('buildElasticSearchQuery');
        $method->setAccessible(true);
        $options = [];
        $actual = $method->invokeArgs(new ElasticSearchAdapter(), [$options]);
        $expected = [];
        static::assertEquals($expected, $actual);
    }

    /**
     * Test `buildElasticSearchQuery` method
     *
     * @return array
     */
    public function searchProvider(): array
    {
        $query = $this->fetchTable('objects')->find()->where(['id' => 1]);

        return [
            'query' => [
                Query::class,
                $query,
                'text',
                [],
                [],
            ],
        ];
    }

    /**
     * Test `search` method
     *
     * @param Query $query
     * @param string $text
     * @param array $options
     * @param array $config
     * @param string $expected
     * @return void
     * @covers ::search()
     * @covers ::buildQuery()
     * @covers ::buildElasticSearchQuery()
     * @dataProvider searchProvider()
     */
    public function testSearch(
        string $expected,
        Query $query,
        string $text,
        array $options = [],
        array $config = []
    ): void {
        $adapter = new ElasticSearchAdapter();
        $actual = $adapter->search($query, $text, $options, $config);
        static::assertInstanceOf($expected, $actual);
    }

    /**
     * Test `createTempTable` method
     *
     * @return void
     * @covers ::createTempTable()
     */
    public function testCreateTempTable(): void
    {
        $reflectionClass = new ReflectionClass(ElasticSearchAdapter::class);
        $method = $reflectionClass->getMethod('createTempTable');
        $method->setAccessible(true);
        $connection = ConnectionManager::get('test');
        $actual = $method->invokeArgs(new ElasticSearchAdapter(), [$connection]);
        $expected = Table::class;
        static::assertInstanceOf($expected, $actual);
    }

    /**
     * Test `createTempTable` method on wrong db connection
     *
     * @return void
     * @covers ::createTempTable()
     */
    public function testCreateTempTableNull(): void
    {
        ConnectionManager::setConfig('test2', [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => dirname(__DIR__) . DS . 'wrongdir' . DS . 'test2.sqlite',
            'encoding' => 'utf8',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
        ]);
        $reflectionClass = new ReflectionClass(ElasticSearchAdapter::class);
        $method = $reflectionClass->getMethod('createTempTable');
        $method->setAccessible(true);
        $connection = ConnectionManager::get('test2');
        $actual = $method->invokeArgs(new ElasticSearchAdapter(), [$connection]);
        static::assertNull($actual);
    }
}

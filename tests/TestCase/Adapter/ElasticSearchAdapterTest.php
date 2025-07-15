<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase\Adapter;

use BEdita\ElasticSearch\Adapter\ElasticSearchAdapter;
use BEdita\ElasticSearch\Model\Index\AdapterCompatibleInterface;
use BEdita\ElasticSearch\Model\Index\SearchIndex;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\ElasticSearch\Index;
use Cake\ElasticSearch\TestSuite\TestCase;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Exception;
use ReflectionClass;
use UnexpectedValueException;

/**
 * {@see \BEdita\ElasticSearch\Adapter\ElasticSearchAdapter} Test Case
 *
 * @coversDefaultClass \BEdita\ElasticSearch\Adapter\ElasticSearchAdapter
 */
class ElasticSearchAdapterTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected $fixtures = [
//        'plugin.BEdita/Core.ObjectTypes',
//        'plugin.BEdita/Core.Objects',
    ];

    /**
     * Data provider for {@see ElasticSearchAdapterTest::testGetIndex()} test case.
     *
     * @return array<string, array{\Cake\ElasticSearch\Index|Exception, string|\Cake\ElasticSearch\Index}>
     */
    public function getIndexProvider(): array
    {
        /** @var \Cake\ElasticSearch\Datasource\IndexLocator $locator */
        $locator = FactoryLocator::get('ElasticSearch');

        $index = new SearchIndex();
        $locator->set('IndexProviderTest', $index);

        return [
            'string' => [$index, 'IndexProviderTest'],
            'object' => [$index, $index],
            'index does not implement interface' => [
                new UnexpectedValueException('Search index must be an instance of Cake\ElasticSearch\Index that implements BEdita\ElasticSearch\Model\Index\AdapterCompatibleInterface interface, got Cake\ElasticSearch\Index'),
                new Index(),
            ],
        ];
    }

    /**
     * Test {@see ElasticSearchAdapter::getIndex()} method.
     *
     * @param \Cake\ElasticSearch\Index|Exception $expected Expected outcome.
     * @param string|\Cake\ElasticSearch\Index $index Index configuration.
     * @return void
     * @dataProvider getIndexProvider()
     * @covers ::getIndex()
     */
    public function testGetIndex(Index|Exception $expected, string|Index $index): void
    {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $adapter = new class extends ElasticSearchAdapter {
            public function getIndex(): Index&AdapterCompatibleInterface
            {
                return parent::getIndex();
            }
        };
        $adapter->setConfig(compact('index'));

        $actual = $adapter->getIndex();
        static::assertSame($expected, $actual);
    }

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
        $text = 'searchme';
        $options = [];
        $actual = $method->invokeArgs(new ElasticSearchAdapter(), [$text, $options]);
        $expected = [];
        static::assertEquals($expected, $actual);
        static::markTestIncomplete('This test has not been implemented yet.');
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
            ],
        ];
    }

    /**
     * Test `search` method
     *
     * @param string $expected
     * @param Query $query
     * @param string $text
     * @param array $options
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
    ): void {
        $adapter = new ElasticSearchAdapter();
        $actual = $adapter->search($query, $text, $options)->find('list', ['valueField' => 'id'])->all()->toList();
        $expected = [];
        static::assertSame($expected, $actual);
    }

    /**
     * Test `search` with elastic search
     *
     * @return void
     * @covers ::search()
     * @covers ::buildQuery()
     * @covers ::buildElasticSearchQuery()
     * @covers ::createTempTable()
     */
    public function testSearchElastic(): void
    {
        $adapter = new class extends ElasticSearchAdapter {
            protected function buildElasticSearchQuery(string $text, array $options): array
            {
                return [
                    ['id' => '1', 'score' => 1.0],
                    ['id' => '2', 'score' => 0.5],
                ];
            }
        };
        $query = $this->fetchTable('objects')->find()->where(['id' => 1]);
        $text = 'searchme';
        $actual = $adapter->search($query, $text, []);
        static::assertInstanceOf(Query::class, $actual);
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `createTempTable` method
     *
     * @return void
     * @covers ::createTempTable()
     */
    public function testCreateTempTable(): void
    {
        $adapter = new class extends ElasticSearchAdapter {
            /** @inheritDoc */
            public function createTempTable(Connection $connection): Table
            {
                return parent::createTempTable($connection);
            }
        };
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('test');
        $table = $adapter->createTempTable($connection);
        // The type of `$table` is trivial, perform assertions on created columns or such, if necessary.
        static::assertInstanceOf(Table::class, $table);
        static::assertSame(
            $table->getSchema()->getColumn('id'),
            [
                'type' => 'integer',
                'length' => 11,
                'unsigned' => true,
                'null' => false,
                'precision' => null,
                'default' => null,
                'comment' => null,
                'autoIncrement' => null,
            ],
        );
        static::assertSame(
            $table->getSchema()->getColumn('score'),
            [
                'type' => 'float',
                'null' => false,
                'length' => null,
                'precision' => null,
                'default' => null,
                'comment' => null,
                'unsigned' => null,
            ],
        );
    }
}

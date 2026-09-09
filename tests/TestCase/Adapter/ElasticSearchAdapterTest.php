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
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use UnexpectedValueException;

/**
 * {@see \BEdita\ElasticSearch\Adapter\ElasticSearchAdapter} Test Case
 */
#[CoversClass(ElasticSearchAdapter::class)]
class ElasticSearchAdapterTest extends TestCase
{
    /**
     * Data provider for {@see ElasticSearchAdapterTest::testGetIndex()} test case.
     *
     * @return array<string, array{\Cake\ElasticSearch\Index|Exception, string|\Cake\ElasticSearch\Index}>
     */
    public static function getIndexProvider(): array
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
     */
    #[DataProvider('getIndexProvider')]
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
     */
    public function testBuildElasticSearchQuery(): void
    {
        $reflectionClass = new ReflectionClass(ElasticSearchAdapter::class);
        $method = $reflectionClass->getMethod('buildElasticSearchQuery');
        $text = 'searchme';
        $options = [];
        $actual = $method->invokeArgs(new ElasticSearchAdapter(), [$text, $options]);
        $expected = [];
        static::assertEquals($expected, $actual);
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Data provider for {@see ElasticSearchAdapterTest::testSearch()} test case.
     *
     * @return array
     */
    public static function searchProvider(): array
    {
        return [
            'query' => [
                SelectQuery::class,
                ['id' => 1],
                'text',
                [],
            ],
        ];
    }

    /**
     * Test `search` method
     *
     * @param string $expected
     * @param array $conditions
     * @param string $text
     * @param array $options
     * @return void
     */
    #[DataProvider('searchProvider')]
    public function testSearch(
        string $expected,
        array $conditions,
        string $text,
        array $options = [],
    ): void {
        $query = $this->fetchTable('objects')->find()->where($conditions);
        $adapter = new ElasticSearchAdapter();
        $actual = $adapter->search($query, $text, $options)->find('list', valueField: 'id')->all()->toList();
        $expected = [];
        static::assertSame($expected, $actual);
    }

    /**
     * Test `search` with elastic search
     *
     * @return void
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
        static::assertInstanceOf(SelectQuery::class, $actual);
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `createTempTable` method
     *
     * @return void
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
                'null' => false,
                'default' => null,
                'generated' => null,
                'unsigned' => true,
                'precision' => null,
                'comment' => null,
                'autoIncrement' => false,
            ],
        );
        static::assertSame(
            $table->getSchema()->getColumn('score'),
            [
                'type' => 'float',
                'length' => null,
                'null' => false,
                'default' => null,
                'unsigned' => null,
                'precision' => null,
                'comment' => null,
            ],
        );
    }
}

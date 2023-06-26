<?php
namespace BEdita\ElasticSearch\Test\TestCase\Adapter;

use BEdita\ElasticSearch\Adapter\ElasticSearchAdapter;
use Cake\ORM\Query;
use Cake\TestSuite\TestCase;

/**
 * {@see BEdita\Core\Command\ElasticSearchAdapter} Test Case
 *
 * @coversDefaultClass \BEdita\Core\Command\ElasticSearchAdapter
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
        $reflectionClass = new \ReflectionClass(ElasticSearchAdapter::class);// @phan-suppress-current-line
        $method = $reflectionClass->getMethod('buildElasticSearchQuery');
        $method->setAccessible(true);
        $options = [];
        $actual = $method->invokeArgs(new ElasticSearchAdapter(), [$options]);
        $expected = ['todo'];
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
}

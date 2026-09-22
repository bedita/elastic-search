<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase\Index;

use BEdita\ElasticSearch\Model\Index\ObjectSearchIndex;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ElasticSearch\TestSuite\TestCase;
use Cake\I18n\DateTime;
use Cake\Utility\Hash;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * {@see \BEdita\ElasticSearch\Model\Index\ObjectSearchIndex} Test Case
 */
#[CoversClass(ObjectSearchIndex::class)]
class ObjectSearchIndexTest extends TestCase
{
    protected ObjectSearchIndex $index;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->index = new ObjectSearchIndex([
            'connection' => ConnectionManager::get('test_elastic'),
            'name' => 'objects',
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        DateTime::setTestNow(null);
        Configure::delete('Publish.checkDate');
    }

    /**
     * Test `findQuery` method with the `type` option.
     *
     * @return void
     */
    public function testFindQueryByType(): void
    {
        $query = $this->index->findQuery($this->index->query(), [
            'query' => 'gustavo',
            'type' => ['documents', 'events'],
        ]);

        static::assertSame(
            [
                ['terms' => ['type' => ['documents', 'events']]],
                ['bool' => ['must' => [['term' => ['deleted' => 'false']]]]],
            ],
            Hash::get($query->compileQuery()->toArray(), 'query.bool.filter.0.bool.must'),
        );
    }

    /**
     * Test `findAvailable` finder with publication date check enabled.
     *
     * @return void
     */
    public function testFindQueryCheckPublishDate(): void
    {
        Configure::write('Publish.checkDate', true);
        DateTime::setTestNow(new DateTime('2026-09-18T12:00:00+00:00'));

        $query = $this->index->findQuery($this->index->query(), ['query' => 'gustavo']);

        $now = DateTime::now();
        static::assertEquals(
            [
                ['term' => ['deleted' => 'false']],
                [
                    'bool' => [
                        'should' => [
                            ['bool' => ['must_not' => [['exists' => ['field' => 'publish_start']]]]],
                            ['range' => ['publish_start' => ['lte' => $now]]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
                [
                    'bool' => [
                        'should' => [
                            ['bool' => ['must_not' => [['exists' => ['field' => 'publish_end']]]]],
                            ['range' => ['publish_end' => ['gte' => $now]]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
            ],
            Hash::get($query->compileQuery()->toArray(), 'query.bool.filter.0.bool.must.0.bool.must'),
        );
    }
}

<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\Fixture;

use Cake\ElasticSearch\TestSuite\TestFixture;

/**
 * Fixture for the `search` index.
 */
class SearchFixture extends TestFixture
{
    /**
     * Index name
     *
     * @var string
     */
    public string $table = 'search';

    /**
     * Connection name
     *
     * @var string
     */
    public string $connection = 'test_elastic';

    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => '1',
            'title' => 'Searchme searchme searchme',
        ],
        [
            'id' => '2',
            'title' => 'Searchme once, then other words',
        ],
        [
            'id' => '3',
            'title' => 'Nothing relevant here',
        ],
    ];
}

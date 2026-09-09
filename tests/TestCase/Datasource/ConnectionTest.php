<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase\Datasource;

use BEdita\ElasticSearch\Datasource\Connection;
use BEdita\ElasticSearch\Datasource\OpenSearchCompatibleClient;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Elastic\Elasticsearch\Exception\HttpClientException;
use Elastic\Elasticsearch\Transport\RequestOptions;
use Elastic\Transport\Client\Curl;
use Elastica\Document;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;

/**
 * {@see \BEdita\ElasticSearch\Datasource\Connection} Test Case
 */
#[CoversClass(Connection::class)]
class ConnectionTest extends TestCase
{
    /**
     * Data provider for {@see ConnectionTest::testIsOpenSearch()} test case.
     *
     * @return array<string, array{bool, array}>
     */
    public static function isOpenSearchProvider(): array
    {
        return [
            'empty' => [false, []],
            'driver elasticsearch' => [false, ['driver' => 'elasticsearch']],
            'driver class name' => [false, ['driver' => Connection::class]],
            'driver opensearch' => [true, ['driver' => 'opensearch']],
            'flag' => [true, ['opensearch' => true]],
            'flag false' => [false, ['opensearch' => false, 'driver' => 'elasticsearch']],
        ];
    }

    /**
     * Test {@see Connection::isOpenSearch()} method.
     *
     * @param bool $expected Expected result.
     * @param array $config Connection configuration.
     * @return void
     */
    #[DataProvider('isOpenSearchProvider')]
    public function testIsOpenSearch(bool $expected, array $config): void
    {
        static::assertSame($expected, Connection::isOpenSearch($config));
    }

    /**
     * Test that the HTTP client is left untouched when OpenSearch compatibility is not enabled.
     *
     * @return void
     */
    public function testConstructElasticSearch(): void
    {
        $connection = new Connection(['host' => '127.0.0.1', 'port' => 9200, 'driver' => 'elasticsearch']);

        $client = $connection->getTransport()->getClient();
        static::assertNotInstanceOf(OpenSearchCompatibleClient::class, $client);
        static::assertSame(['127.0.0.1:9200'], $connection->config()['hosts']);
    }

    /**
     * Test that the discovered HTTP client is decorated when OpenSearch compatibility is enabled.
     *
     * @return void
     */
    public function testConstructOpenSearch(): void
    {
        $connection = new Connection(['host' => '127.0.0.1', 'port' => 9200, 'driver' => 'opensearch']);

        $client = $connection->getTransport()->getClient();
        static::assertInstanceOf(OpenSearchCompatibleClient::class, $client);
        static::assertNotInstanceOf(OpenSearchCompatibleClient::class, $client->getInnerClient());
        static::assertSame(['127.0.0.1:9200'], $connection->config()['hosts']);
    }

    /**
     * Test that a custom HTTP client is decorated when OpenSearch compatibility is enabled.
     *
     * @return void
     */
    public function testConstructOpenSearchCustomClient(): void
    {
        $inner = $this->createStub(ClientInterface::class);
        $connection = new Connection([
            'hosts' => ['127.0.0.1:9200'],
            'opensearch' => true,
            'transport_config' => ['http_client' => $inner],
        ]);

        $client = $connection->getTransport()->getClient();
        static::assertInstanceOf(OpenSearchCompatibleClient::class, $client);
        static::assertSame($inner, $client->getInnerClient());
    }

    /**
     * Test that an HTTP client that is already decorated is not decorated twice.
     *
     * @return void
     */
    public function testConstructOpenSearchDecoratedClient(): void
    {
        $inner = $this->createStub(ClientInterface::class);
        $connection = new Connection([
            'hosts' => ['127.0.0.1:9200'],
            'driver' => 'opensearch',
            'transport_config' => ['http_client' => new OpenSearchCompatibleClient($inner)],
        ]);

        $client = $connection->getTransport()->getClient();
        static::assertInstanceOf(OpenSearchCompatibleClient::class, $client);
        static::assertSame($inner, $client->getInnerClient());
    }

    /**
     * Test that HTTP client configuration and options are applied to the inner client.
     *
     * @return void
     */
    public function testConstructOpenSearchHttpClientConfig(): void
    {
        $inner = new Curl();
        $connection = new Connection([
            'hosts' => ['127.0.0.1:9200'],
            'driver' => 'opensearch',
            'transport_config' => [
                'http_client' => $inner,
                'http_client_config' => [RequestOptions::SSL_VERIFY => false],
                'http_client_options' => [CURLOPT_TIMEOUT => 5],
            ],
        ]);

        $client = $connection->getTransport()->getClient();
        static::assertInstanceOf(OpenSearchCompatibleClient::class, $client);
        static::assertInstanceOf(Curl::class, $client->getInnerClient());
        static::assertNotSame($inner, $client->getInnerClient());
    }

    /**
     * Test that HTTP client configuration is rejected for unsupported HTTP clients, like Elastica does.
     *
     * @return void
     */
    public function testConstructOpenSearchUnsupportedHttpClientConfig(): void
    {
        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('is not supported for custom options');

        new Connection([
            'hosts' => ['127.0.0.1:9200'],
            'driver' => 'opensearch',
            'transport_config' => [
                'http_client' => $this->createStub(ClientInterface::class),
                'http_client_config' => [RequestOptions::SSL_VERIFY => false],
            ],
        ]);
    }

    /**
     * Test a round trip against the configured ElasticSearch/OpenSearch server.
     *
     * @return void
     */
    public function testServerRoundTrip(): void
    {
        $connection = ConnectionManager::get('test_elastic');
        static::assertInstanceOf(Connection::class, $connection);

        $index = $connection->getIndex('bedita_elastic_search_connection_test');
        if ($index->exists()) {
            $index->delete();
        }

        try {
            static::assertTrue($index->create()->isOk());
            static::assertTrue($index->exists());
            static::assertTrue($index->addDocument(new Document('1', ['title' => 'Hello world']))->isOk());
            static::assertTrue($index->refresh()->isOk());
            static::assertSame(1, $index->count());
            static::assertSame(1, $index->search('hello')->count());
            static::assertSame(0, $index->search('goodbye')->count());
        } finally {
            $index->delete();
        }
    }
}

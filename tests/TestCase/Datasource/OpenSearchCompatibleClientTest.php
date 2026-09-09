<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Test\TestCase\Datasource;

use BEdita\ElasticSearch\Datasource\OpenSearchCompatibleClient;
use Cake\TestSuite\TestCase;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * {@see \BEdita\ElasticSearch\Datasource\OpenSearchCompatibleClient} Test Case
 */
#[CoversClass(OpenSearchCompatibleClient::class)]
class OpenSearchCompatibleClientTest extends TestCase
{
    /**
     * Create a PSR-18 client mock that returns the given response and stores the request it received.
     *
     * @param \Psr\Http\Message\ResponseInterface $response Response to return.
     * @param \Psr\Http\Message\RequestInterface|null $sent Variable where the received request is stored.
     * @return \Psr\Http\Client\ClientInterface
     */
    protected function createInnerClient(ResponseInterface $response, ?RequestInterface &$sent): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use (&$sent): bool {
                $sent = $request;

                return true;
            }))
            ->willReturn($response);

        return $client;
    }

    /**
     * Data provider for {@see OpenSearchCompatibleClientTest::testRewriteMediaTypes()} test case.
     *
     * @return array<string, array{string, string}>
     */
    public static function rewriteMediaTypesProvider(): array
    {
        return [
            'json' => ['application/json', 'application/vnd.elasticsearch+json; compatible-with=9'],
            'ndjson' => ['application/x-ndjson', 'application/vnd.elasticsearch+x-ndjson; compatible-with=9'],
            'text' => ['text/plain', 'text/vnd.elasticsearch+plain; compatible-with=8'],
            'multiple' => [
                'text/plain,application/json',
                'text/vnd.elasticsearch+plain; compatible-with=9,application/vnd.elasticsearch+json; compatible-with=9',
            ],
            'header line' => [
                'text/plain,application/json',
                'text/vnd.elasticsearch+plain; compatible-with=9, application/vnd.elasticsearch+json; compatible-with=9',
            ],
            'plain' => ['application/json', 'application/json'],
            'plain multiple' => ['text/plain,application/json', 'text/plain,application/json'],
            'other vendor' => ['application/vnd.mapbox-vector-tile', 'application/vnd.mapbox-vector-tile'],
            'empty' => ['', ''],
        ];
    }

    /**
     * Test {@see OpenSearchCompatibleClient::rewriteMediaTypes()} method.
     *
     * @param string $expected Expected result.
     * @param string $value Header value.
     * @return void
     */
    #[DataProvider('rewriteMediaTypesProvider')]
    public function testRewriteMediaTypes(string $expected, string $value): void
    {
        static::assertSame($expected, OpenSearchCompatibleClient::rewriteMediaTypes($value));
    }

    /**
     * Test {@see OpenSearchCompatibleClient::sendRequest()} method.
     *
     * @return void
     */
    public function testSendRequest(): void
    {
        $request = (new Request('POST', 'http://127.0.0.1:9200/_search', [], '{}'))
            ->withHeader('Content-Type', 'application/vnd.elasticsearch+json; compatible-with=9')
            ->withHeader('Accept', 'text/vnd.elasticsearch+plain; compatible-with=9,application/vnd.elasticsearch+json; compatible-with=9') // phpcs:ignore
            ->withHeader('User-Agent', 'test');
        $response = new Response(200, ['Content-Type' => 'application/json'], '{"hits":[]}');

        $sent = null;
        $inner = $this->createInnerClient($response, $sent);
        $client = new OpenSearchCompatibleClient($inner);
        static::assertSame($inner, $client->getInnerClient());

        $actual = $client->sendRequest($request);

        static::assertInstanceOf(RequestInterface::class, $sent);
        static::assertSame('POST', $sent->getMethod());
        static::assertSame('http://127.0.0.1:9200/_search', (string)$sent->getUri());
        static::assertSame('{}', (string)$sent->getBody());
        static::assertSame('application/json', $sent->getHeaderLine('Content-Type'));
        static::assertSame('text/plain,application/json', $sent->getHeaderLine('Accept'));
        static::assertSame('test', $sent->getHeaderLine('User-Agent'));

        static::assertSame(200, $actual->getStatusCode());
        static::assertSame('{"hits":[]}', (string)$actual->getBody());
        static::assertSame('application/json', $actual->getHeaderLine('Content-Type'));
        static::assertSame(['Elasticsearch'], $actual->getHeader('X-Elastic-Product'));
    }

    /**
     * Test that {@see OpenSearchCompatibleClient::sendRequest()} leaves untouched requests without
     * compatibility media types, and responses that already carry the product header.
     *
     * @return void
     */
    public function testSendRequestPassThrough(): void
    {
        $request = new Request('HEAD', 'http://127.0.0.1:9200/my_index');
        $response = new Response(404, ['X-Elastic-Product' => 'Elasticsearch']);

        $sent = null;
        $client = new OpenSearchCompatibleClient($this->createInnerClient($response, $sent));
        $actual = $client->sendRequest($request);

        static::assertSame($request, $sent);
        static::assertSame($response, $actual);
        static::assertSame(['Elasticsearch'], $actual->getHeader('X-Elastic-Product'));
    }

    /**
     * Test that {@see OpenSearchCompatibleClient::sendRequest()} adds the product header to responses
     * of any status.
     *
     * @return void
     */
    public function testSendRequestErrorResponse(): void
    {
        $request = (new Request('GET', 'http://127.0.0.1:9200/my_index/_doc/1'))
            ->withHeader('Accept', 'application/vnd.elasticsearch+json; compatible-with=9');
        $response = new Response(404, ['Content-Type' => 'application/json'], '{"found":false}');

        $sent = null;
        $client = new OpenSearchCompatibleClient($this->createInnerClient($response, $sent));
        $actual = $client->sendRequest($request);

        static::assertInstanceOf(RequestInterface::class, $sent);
        static::assertSame('application/json', $sent->getHeaderLine('Accept'));
        static::assertFalse($sent->hasHeader('Content-Type'));
        static::assertSame(404, $actual->getStatusCode());
        static::assertSame('Elasticsearch', $actual->getHeaderLine('X-Elastic-Product'));
    }
}

<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Datasource;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 HTTP client decorator that lets the Elasticsearch PHP client talk to an OpenSearch server.
 *
 * The Elasticsearch client sends `Content-Type` and `Accept` headers using the API compatibility
 * media type (e.g. `application/vnd.elasticsearch+json; compatible-with=9`), which OpenSearch rejects
 * with a `406 Not Acceptable` response: such media types are rewritten to their plain counterparts
 * (e.g. `application/json`). The client also requires every successful response to carry the
 * `X-Elastic-Product: Elasticsearch` header, which OpenSearch does not send: the header is added
 * to responses that lack it.
 *
 * @see \Elastic\Elasticsearch\Client::API_COMPATIBILITY_HEADER
 * @see \Elastic\Elasticsearch\Traits\ProductCheckTrait::productCheck()
 */
class OpenSearchCompatibleClient implements ClientInterface
{
    /**
     * Regular expression matching the API compatibility media type sent by the Elasticsearch client.
     */
    protected const COMPATIBILITY_MEDIA_TYPE =
        '#^(application|text)/vnd\.elasticsearch\+([^;\s]+)\s*;\s*compatible-with=\d+$#i';

    /**
     * Request headers whose media types must be rewritten.
     *
     * @var array<string>
     */
    protected const REWRITE_HEADERS = ['Content-Type', 'Accept'];

    /**
     * Constructor.
     *
     * @param \Psr\Http\Client\ClientInterface $client Decorated PSR-18 HTTP client.
     */
    public function __construct(protected ClientInterface $client)
    {
    }

    /**
     * Get the decorated HTTP client.
     *
     * @return \Psr\Http\Client\ClientInterface
     */
    public function getInnerClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        foreach (static::REWRITE_HEADERS as $header) {
            if ($request->hasHeader($header)) {
                $request = $request->withHeader($header, static::rewriteMediaTypes($request->getHeaderLine($header)));
            }
        }

        $response = $this->client->sendRequest($request);
        if (!$response->hasHeader(Elasticsearch::HEADER_CHECK)) {
            $response = $response->withHeader(Elasticsearch::HEADER_CHECK, Elasticsearch::PRODUCT_NAME);
        }

        return $response;
    }

    /**
     * Rewrite Elasticsearch API compatibility media types to their plain counterparts,
     * e.g. `application/vnd.elasticsearch+json; compatible-with=9` becomes `application/json`.
     *
     * @param string $value Header value, possibly listing multiple comma-separated media types.
     * @return string
     */
    public static function rewriteMediaTypes(string $value): string
    {
        $mediaTypes = array_map(
            fn(string $mediaType): string => (string)preg_replace(
                static::COMPATIBILITY_MEDIA_TYPE,
                '$1/$2',
                trim($mediaType),
            ),
            explode(',', $value),
        );

        return implode(',', $mediaTypes);
    }
}

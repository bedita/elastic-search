<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Datasource;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client decorator that lets the Elasticsearch client talk to OpenSearch.
 *
 * OpenSearch answers `406` to the API compatibility media type (`application/vnd.elasticsearch+json;
 * compatible-with=9`) and does not send the `X-Elastic-Product` header the client checks for.
 *
 * @see \Elastic\Elasticsearch\Client::API_COMPATIBILITY_HEADER
 * @see \Elastic\Elasticsearch\Traits\ProductCheckTrait::productCheck()
 */
class OpenSearchCompatibleClient implements ClientInterface
{
    protected const COMPATIBILITY_MEDIA_TYPE =
        '#^(application|text)/vnd\.elasticsearch\+([^;\s]+)\s*;\s*compatible-with=\d+$#i';

    /**
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
     * Rewrite compatibility media types, e.g. `application/vnd.elasticsearch+json; compatible-with=9`
     * becomes `application/json`.
     *
     * @param string $value Header value, possibly with comma-separated media types.
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

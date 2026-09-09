<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Datasource;

use Cake\ElasticSearch\Datasource\Connection as ElasticSearchConnection;
use Elastic\Elasticsearch\Exception\HttpClientException;
use Elastic\Elasticsearch\Transport\Adapter\AdapterOptions;
use Elastic\Transport\TransportBuilder;
use Psr\Http\Client\ClientInterface;

/**
 * ElasticSearch connection with opt-in OpenSearch compatibility.
 *
 * When the connection is configured with `driver` set to `opensearch` (or with `opensearch` set to `true`),
 * the HTTP client used by Elastica is decorated with {@see \BEdita\ElasticSearch\Datasource\OpenSearchCompatibleClient}
 * so that the Elasticsearch PHP client can talk to an OpenSearch server. With any other configuration
 * this connection behaves exactly like {@see \Cake\ElasticSearch\Datasource\Connection}.
 *
 * Example DSN: `http://127.0.0.1:9200/?className=BEdita\ElasticSearch\Datasource\Connection&driver=opensearch`
 */
class Connection extends ElasticSearchConnection
{
    /**
     * Driver name enabling OpenSearch compatibility.
     */
    public const DRIVER_OPENSEARCH = 'opensearch';

    /**
     * @inheritDoc
     */
    public function __construct(array $config = [])
    {
        if (static::isOpenSearch($config)) {
            $config = static::injectOpenSearchClient($config);
        }

        parent::__construct($config);
    }

    /**
     * Check whether OpenSearch compatibility is enabled by the connection configuration.
     *
     * @param array $config Connection configuration.
     * @return bool
     */
    public static function isOpenSearch(array $config): bool
    {
        return !empty($config['opensearch']) || ($config['driver'] ?? null) === static::DRIVER_OPENSEARCH;
    }

    /**
     * Decorate the HTTP client used by Elastica with {@see \BEdita\ElasticSearch\Datasource\OpenSearchCompatibleClient}.
     *
     * The HTTP client is obtained the same way Elastica does (`transport_config.http_client`, or PSR-18 discovery
     * with a fallback to the built-in cURL client). Since Elastica is not able to apply `http_client_config` and
     * `http_client_options` to the decorator, those are applied to the inner client here, before decorating it.
     *
     * @param array $config Connection configuration.
     * @return array Updated connection configuration.
     * @see \Elastica\Client::_buildTransport()
     */
    protected static function injectOpenSearchClient(array $config): array
    {
        $transportConfig = (array)($config['transport_config'] ?? []);

        $client = $transportConfig['http_client'] ?? null;
        if (!$client instanceof ClientInterface) {
            $client = TransportBuilder::create()->getClient();
        }
        if ($client instanceof OpenSearchCompatibleClient) {
            $client = $client->getInnerClient();
        }

        $client = static::applyHttpClientOptions(
            $client,
            (array)($transportConfig['http_client_config'] ?? []),
            (array)($transportConfig['http_client_options'] ?? []),
        );
        unset($transportConfig['http_client_config'], $transportConfig['http_client_options']);

        $transportConfig['http_client'] = new OpenSearchCompatibleClient($client);
        $config['transport_config'] = $transportConfig;

        return $config;
    }

    /**
     * Apply HTTP client configuration and options to a client, the same way Elastica does.
     *
     * @param \Psr\Http\Client\ClientInterface $client HTTP client.
     * @param array $config HTTP client configuration (`transport_config.http_client_config`).
     * @param array $options HTTP client options (`transport_config.http_client_options`).
     * @return \Psr\Http\Client\ClientInterface
     * @throws \Elastic\Elasticsearch\Exception\HttpClientException If the HTTP client does not support custom options.
     * @see \Elastica\Client::setTransportClientOptions()
     */
    protected static function applyHttpClientOptions(
        ClientInterface $client,
        array $config,
        array $options,
    ): ClientInterface {
        if (empty($config) && empty($options)) {
            return $client;
        }

        $adapterClass = AdapterOptions::HTTP_ADAPTERS[$client::class] ?? null;
        if ($adapterClass === null) {
            throw new HttpClientException(
                sprintf('The HTTP client %s is not supported for custom options', $client::class),
            );
        }

        return (new $adapterClass())->setConfig($client, $config, $options);
    }
}

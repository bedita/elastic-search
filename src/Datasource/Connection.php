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
 * With `driver` set to `opensearch` the HTTP client is decorated with {@see \BEdita\ElasticSearch\Datasource\OpenSearchCompatibleClient},
 * otherwise this behaves like {@see \Cake\ElasticSearch\Datasource\Connection}.
 * DSN: `http://127.0.0.1:9200/?className=BEdita\ElasticSearch\Datasource\Connection&driver=opensearch`
 */
class Connection extends ElasticSearchConnection
{
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
     * Check whether OpenSearch compatibility is enabled.
     *
     * @param array $config Connection configuration.
     * @return bool
     */
    public static function isOpenSearch(array $config): bool
    {
        return !empty($config['opensearch']) || ($config['driver'] ?? null) === static::DRIVER_OPENSEARCH;
    }

    /**
     * Decorate the HTTP client used by Elastica.
     *
     * Client options are applied here, since Elastica cannot apply them to the decorator.
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
     * Apply HTTP client configuration and options, the same way Elastica does.
     *
     * @param \Psr\Http\Client\ClientInterface $client HTTP client.
     * @param array $config HTTP client configuration.
     * @param array $options HTTP client options.
     * @return \Psr\Http\Client\ClientInterface
     * @throws \Elastic\Elasticsearch\Exception\HttpClientException If custom options are unsupported.
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

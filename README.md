# BEdita/ElasticSearch plugin for BEdita

[![Github Actions](https://github.com/bedita/elastic-search/workflows/php/badge.svg)](https://github.com/bedita/elastic-search/actions?query=workflow%3Aphp)
[![codecov](https://codecov.io/gh/bedita/elastic-search/branch/main/graph/badge.svg)](https://codecov.io/gh/bedita/elastic-search)
[![phpstan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bedita/elastic-search/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/bedita/elastic-search/?branch=main)
[![image](https://img.shields.io/packagist/v/bedita/elastic-search.svg?label=stable)](https://packagist.org/packages/bedita/elastic-search)
[![image](https://img.shields.io/github/license/bedita/elastic-search.svg)](https://github.com/bedita/elastic-search/blob/main/LICENSE.LGPL)

ElasticSearch (and OpenSearch) search adapter for [BEdita](https://github.com/bedita/bedita) 6, built on top of
[cakephp/elastic-search](https://github.com/cakephp/elastic-search) 5 and [Elastica](https://github.com/ruflin/Elastica) 9.

## Requirements

- PHP 8.3+
- CakePHP 5.2+
- BEdita Core 6.0+
- ElasticSearch 8+ / 9, or OpenSearch 2 (see [OpenSearch compatibility](#opensearch-compatibility))

## Installation

First, if `vendor` directory has not been created, you have to install composer dependencies using:

```bash
composer install
```

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```bash
composer require bedita/elastic-search
```

Then load the plugin in your application (`src/Application.php`):

```php
$this->addPlugin('BEdita/ElasticSearch');
```

The plugin loads `Cake/ElasticSearch` on its own.

## Configuration

### Datasource

Configure an `elastic` datasource in `config/app.php`. The `BEdita\ElasticSearch\Datasource\Connection` class
extends `Cake\ElasticSearch\Datasource\Connection` and adds optional OpenSearch compatibility:

```php
use BEdita\ElasticSearch\Datasource\Connection as ElasticSearchConnection;

'Datasources' => [
    'elastic' => [
        'className' => ElasticSearchConnection::class,
        'driver' => ElasticSearchConnection::class,
        'log' => false,

        'url' => env('DATABASE_ELASTIC_DSN', null),
    ],
],
```

The connection accepts every option supported by `Cake\ElasticSearch\Datasource\Connection` and Elastica
(`hosts`, `username`, `password`, `api_key`, `retries`, `transport_config`, ...).

When using a DSN, both `className` and `driver` must be set in the query string, otherwise CakePHP
would replace `className` with the default database connection class:

```
# ElasticSearch
DATABASE_ELASTIC_DSN='http://127.0.0.1:9200/?className=BEdita\ElasticSearch\Datasource\Connection&driver=elasticsearch'

# OpenSearch
DATABASE_ELASTIC_DSN='http://127.0.0.1:9200/?className=BEdita\ElasticSearch\Datasource\Connection&driver=opensearch'
```

### OpenSearch compatibility

The `elasticsearch/elasticsearch` 9 client used by Elastica sends every request with the
`application/vnd.elasticsearch+json; compatible-with=9` media type, which OpenSearch rejects with a
`406 Not Acceptable` response, and requires every response to carry the `X-Elastic-Product: Elasticsearch` header,
which OpenSearch does not send.

`BEdita\ElasticSearch\Datasource\Connection` fixes this when the connection is configured with `driver` set
to `opensearch` (or with `opensearch` set to `true`): the PSR-18 HTTP client used by Elastica is decorated with
`BEdita\ElasticSearch\Datasource\OpenSearchCompatibleClient`, which rewrites `Content-Type` and `Accept`
request headers to plain media types (e.g. `application/json`) and adds the missing product header to responses.
With any other configuration the connection behaves exactly like `Cake\ElasticSearch\Datasource\Connection`.

The HTTP client is obtained the same way Elastica does: `transport_config.http_client` if set, otherwise
PSR-18 discovery with a fallback to the built-in cURL client. `transport_config.http_client_config` and
`transport_config.http_client_options` are supported as well, and applied to the inner HTTP client.

```php
'elastic' => [
    'className' => \BEdita\ElasticSearch\Datasource\Connection::class,
    'driver' => 'opensearch',
    'hosts' => ['127.0.0.1:9200'],
],
```

### Search adapter

Register the adapter in the BEdita `Search` configuration:

```php
'Search' => [
    'adapters' => [
        'default' => [
            'className' => \BEdita\ElasticSearch\Adapter\ElasticSearchAdapter::class,
            // Index class (or instance), defaults to `BEdita/ElasticSearch.Search`
            'index' => 'BEdita/ElasticSearch.ObjectSearch',
            // Options passed to the index locator
            'options' => [],
        ],
    ],
],
```

The plugin ships two index classes, both extending `BEdita\ElasticSearch\Model\Index\SearchIndex`
and implementing `BEdita\ElasticSearch\Model\Index\AdapterCompatibleInterface`:

- `BEdita/ElasticSearch.Search` (`SearchIndex`): base index, full text search on `title`;
- `BEdita/ElasticSearch.ObjectSearch` (`ObjectSearchIndex`): index for BEdita objects (`uname`, `type`, `status`,
  `deleted`, `publish_start`, `publish_end`, `title`, `description`, `body`), full text search on `title`,
  `description` and `body`, respecting deletion, status (`Status.level`) and publication (`Publish.checkDate`)
  constraints, with an optional `type` filter.

Applications may extend these classes to customize `$_properties` (mappings), `$_analysis` (analysis settings),
`prepareData()` (indexed data) and `findQuery()` (search query).

The index name defaults to `<database schema>_<underscored alias>` and can be overridden with the `name` option.

## Commands

```bash
# Create indices for configured adapters (or only for the given ones)
bin/cake elastic:createIndex [--adapters default,other]

# Update mappings and analysis settings of existing indices, optionally creating missing ones
bin/cake elastic:updateIndex [--create] [--adapters default,other]
```

## Testing

Tests run against SQLite by default, and against the ElasticSearch/OpenSearch server configured with the `es_dsn`
environment variable (`127.0.0.1:9200` by default):

```bash
# OpenSearch
es_dsn='http://127.0.0.1:9200/?className=BEdita\ElasticSearch\Datasource\Connection&driver=opensearch' vendor/bin/phpunit

# MySQL instead of SQLite
db_dsn='mysql://user:password@127.0.0.1:3306/es_test' vendor/bin/phpunit
```

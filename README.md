# BEdita/ElasticSearch plugin for BEdita

[![Github Actions](https://github.com/bedita/elastic-search/workflows/php/badge.svg)](https://github.com/bedita/elastic-search/actions?query=workflow%3Aphp)
[![codecov](https://codecov.io/gh/bedita/elastic-search/branch/main/graph/badge.svg)](https://codecov.io/gh/bedita/elastic-search)
[![phpstan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bedita/elastic-search/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/bedita/elastic-search/?branch=main)
[![image](https://img.shields.io/packagist/v/bedita/elastic-search.svg?label=stable)](https://packagist.org/packages/bedita/elastic-search)
[![image](https://img.shields.io/github/license/bedita/elastic-search.svg)](https://github.com/bedita/elastic-search/blob/main/LICENSE.LGPL)

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

## OpenSearch compatibility

OpenSearch rejects the `compatible-with` media type sent by `elasticsearch/elasticsearch` 9 and does not send the
`X-Elastic-Product` header the client checks. `BEdita\ElasticSearch\Datasource\Connection` with `driver=opensearch`
works around both:

```
DATABASE_ELASTIC_DSN='http://127.0.0.1:9200/?className=BEdita\ElasticSearch\Datasource\Connection&driver=opensearch'
```

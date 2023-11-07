<?php
declare(strict_types=1);

use BEdita\Core\ORM\Locator\TableLocator;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Database\Connection as DbConnection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\ElasticSearch\Datasource\Connection as EsConnection;
use Cake\ElasticSearch\Datasource\IndexLocator;
use Cake\ElasticSearch\TestSuite\Fixture\MappingGenerator;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Migrations\TestSuite\Migrator;

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);
    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

require_once 'vendor/cakephp/cakephp/src/basics.php';
require_once 'vendor/autoload.php';

define('ROOT', $root . DS . 'tests' . DS . 'test_app' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('CACHE', TMP . 'cache' . DS);
define('CORE_PATH', $root . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);

Configure::write('debug', true);
Cache::drop('_bedita_object_types_');
Cache::drop('_bedita_core_');
Cache::setConfig([
    '_cake_core_' => [
        'engine' => 'File',
        'prefix' => 'cake_core_',
        'serialize' => true,
    ],
    '_cake_model_' => [
        'engine' => 'File',
        'prefix' => 'cake_model_',
        'serialize' => true,
    ],
    '_bedita_object_types_' => [
        'className' => 'Null',
    ],
    '_bedita_core_' => [
        'className' => 'Null',
    ],
]);
ConnectionManager::drop('test');
if (getenv('db_dsn')) {
    ConnectionManager::setConfig('test', ['url' => getenv('db_dsn')]);
} else {
    ConnectionManager::setConfig('test', [
        'className' => DbConnection::class,
        'driver' => Sqlite::class,
        'database' => dirname(__DIR__) . DS . 'tmp' . DS . 'test.sqlite',
        'encoding' => 'utf8',
        'cacheMetadata' => true,
        'quoteIdentifiers' => false,
    ]);
}
ConnectionManager::alias('test', 'default');

if (getenv('es_dsn')) {
    ConnectionManager::setConfig('test-es', ['url' => getenv('es_dsn')]);
} else {
    ConnectionManager::setConfig('test-es', [
        'className' => EsConnection::class,
        'driver' => EsConnection::class,
        'host' => '127.0.0.1',
        'port' => 9200,
    ]);
}

if (!TableRegistry::getTableLocator() instanceof TableLocator) {
    TableRegistry::setTableLocator(new TableLocator());
}

Security::setSalt('YlAPGwItcN6msaiuej76a6uyasdNTn3ikcO');

(new Migrator())->runMany([
    ['plugin' => 'BEdita/Core'],
    ['connection' => 'test'],
]);

$schema = new MappingGenerator('./tests/mappings.php', 'test-es');
$schema->reload();

$indexLocator = new IndexLocator();
FactoryLocator::add('ElasticSearch', $indexLocator);

// clear all before running tests
TableRegistry::getTableLocator()->clear();
Cache::clearAll();

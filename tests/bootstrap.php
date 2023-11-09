<?php
declare(strict_types=1);

use BEdita\Core\ORM\Locator\TableLocator;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\ElasticSearch\Datasource\Connection as ElasticConnection;
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
ConnectionManager::setConfig('test', [
    'className' => Connection::class,
    'driver' => Sqlite::class,
    'database' => dirname(__DIR__) . DS . 'tmp' . DS . 'test.sqlite',
    'encoding' => 'utf8',
    'cacheMetadata' => true,
    'quoteIdentifiers' => false,

    'url' => env('db_dsn'),
]);
ConnectionManager::alias('test', 'default');

ConnectionManager::setDsnClassMap(['elasticsearch' => ElasticConnection::class, 'opensearch' => ElasticConnection::class]);
ConnectionManager::drop('test_elastic');
ConnectionManager::setConfig('test_elastic', [
    'className' => ElasticConnection::class,
    'host' => '127.0.0.1',
    'port' => 9200,

    'curl' => [
        // Trust any TLS certificate ElasticSearch/OpenSearch may present.
        // This is INSECURE but for unit test purposes should be ok.
        CURLOPT_SSL_VERIFYPEER => false,
    ],

    'url' => env('es_dsn'),
]);

Security::setSalt(str_pad('TEST SECURITY SALT', 32, '\0'));

(new MappingGenerator('./tests/mappings.php', 'test_elastic'))->reload();

FactoryLocator::add('ElasticSearch', new IndexLocator());

// clear all before running tests
TableRegistry::setTableLocator(new TableLocator());
Cache::clearAll();

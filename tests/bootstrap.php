<?php
declare(strict_types=1);

use BEdita\Core\ORM\Locator\TableLocator;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Migrations\TestSuite\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';

ConnectionManager::setConfig('test', [
    'className' => Connection::class,
    'driver' => Sqlite::class,
    'database' => dirname(__DIR__) . DS . 'tmp' . DS . 'test.sqlite',
    'encoding' => 'utf8',
    'cacheMetadata' => true,
    'quoteIdentifiers' => false,
]);
ConnectionManager::alias('test', 'default');

if (!TableRegistry::getTableLocator() instanceof TableLocator) {
    TableRegistry::setTableLocator(new TableLocator());
}

$now = FrozenTime::parse('2023-06-26T00:00:00Z');
FrozenTime::setTestNow($now);
FrozenDate::setTestNow($now);
Configure::write('debug', true);

// Fixate sessionid early on, as php7.2+
// does not allow the sessionid to be set after stdout
// has been written to.
session_id('cli');

TypeFactory::map('jsonobject', JsonObjectType::class);

Cache::disable();
(new Migrator())->runMany([
    ['plugin' => 'BEdita/Core', 'connection' => 'test'],
    ['connection' => 'test'], // default migrations of this application
]);

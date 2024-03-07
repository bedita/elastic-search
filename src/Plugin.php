<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch;

use BEdita\ElasticSearch\Command\CreateIndexCommand;
use BEdita\ElasticSearch\Command\UpdateIndexCommand;
use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\ElasticSearch\Plugin as ElasticSearchPlugin;

/**
 * Plugin for BEdita ElasticSearch
 */
class Plugin extends BasePlugin
{
    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        $app->addPlugin(ElasticSearchPlugin::class);
    }

    /**
     * @inheritDoc
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return parent::console($commands)
            ->add('elastic:createIndex', CreateIndexCommand::class)
            ->add('elastic:updateIndex', UpdateIndexCommand::class);
    }
}

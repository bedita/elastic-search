<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch;

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
}

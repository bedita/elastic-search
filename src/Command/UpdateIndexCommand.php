<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Command;

use BEdita\Core\Search\SearchRegistry;
use BEdita\ElasticSearch\Adapter\ElasticSearchAdapter;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * Utility command to update the configured ElasticSearch index.
 */
class UpdateIndexCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription('Update ElasticSearch indices for configured adapters.')
            ->addOption('adapters', [
                'help' => 'Update indices only for these adapters (comma-separated list).',
                'required' => false,
            ]);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception Error loading adapter from registry
     */
    public function execute(Arguments $args, ConsoleIo $io): int|null
    {
        $registry = new SearchRegistry();
        $adapters = array_keys((array)Configure::read('Search.adapters'));
        if ($args->hasOption('adapters')) {
            $adapters = explode(',', (string)$args->getOption('adapters'));
        }

        foreach ($adapters as $name) {
            $config = (array)Configure::read(sprintf('Search.adapters.%s', $name));
            if (empty($config)) {
                $io->warning(sprintf('Missing configuration for adapter "%s", skipping index update', $name));

                continue;
            }

            $adapter = $registry->load($name, $config);
            if (!$adapter instanceof ElasticSearchAdapter) {
                $io->warning(sprintf(
                    'Adapter "%s" is not an instance of %s, skipping index update',
                    $name,
                    ElasticSearchAdapter::class,
                ));

                continue;
            }

            $index = $adapter->getIndex();
            if (!$index->updateProperties() || !$index->updateAnalysis()) {
                $io->error(sprintf('Error updating index "%s" for adapter "%s"', $index->getName(), $name));

                continue;
            }

            $io->success(sprintf('Updated index "%s" for adapter "%s"', $index->getName(), $name));
        }

        return static::CODE_SUCCESS;
    }
}

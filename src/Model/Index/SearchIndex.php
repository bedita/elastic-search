<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Model\Index;

use Cake\Database\DriverInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\ElasticSearch\Index;
use Cake\ElasticSearch\Query;
use Cake\ElasticSearch\QueryBuilder;
use Cake\Log\Log;
use Cake\Utility\Inflector;
use Elastica\Query\AbstractQuery;

/**
 * Base search index for ElasticSearch.
 */
class SearchIndex extends Index implements AdapterCompatibleInterface
{
    use IndexTrait;

    /**
     * Returns the index name.
     *
     * If it isn't set, it is constructed from the default collection schema and the alias for the index.
     *
     * @return string
     */
    public function getName(): string
    {
        if ($this->_name === null) {
            $defaultName = $this->getDefaultName();
            if ($defaultName !== null) {
                $this->_name = $defaultName;
            }
        }

        return parent::getName();
    }

    /**
     * Returns the default index name, constructed from the default collection schema and the alias for the index.
     *
     * @return string|null
     */
    protected function getDefaultName(): string|null
    {
        $driver = ConnectionManager::get('default')->getDriver();
        if (!$driver instanceof DriverInterface) {
            return null;
        }

        $prefix = $driver->schema();
        $suffix = Inflector::underscore($this->getAlias());
        if (empty($prefix) || empty($suffix)) {
            return null;
        }

        return $prefix . '_' . $suffix;
    }

    /**
     * Delete a document from the index knowing its ID, or throw an exception upon failure.
     *
     * @param string $id Document ID.
     * @param array $options Options to be passed to {@see \Cake\ElasticSearch\Index::delete()} method.
     * @return void
     */
    public function deleteByIdOrFail(string $id, array $options = []): void
    {
        $document = $this->getIfExists($id);
        if ($document === null) {
            return;
        }

        $this->deleteOrFail($document, $options);
    }

    /**
     * Prepare data for indexing. This method may be overridden by implementations to customize indexed fields.
     *
     * If `null` is returned, entity indexing is skipped (and an entity with such ID is removed
     * from index if already present).
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity to be indexed.
     * @return array<string, mixed>|null
     */
    protected function prepareData(EntityInterface $entity): array|null
    {
        return $entity->toArray();
    }

    /**
     * @inheritDoc
     */
    public function reindex(EntityInterface $entity, string $operation): void
    {
        $id = (string)$entity->id;
        switch ($operation) {
            case 'edit':
                $data = $this->prepareData($entity);
                if ($data === null) {
                    $this->deleteByIdOrFail($id);
                } else {
                    $document = $this->patchEntity($this->getIfExists($id) ?: $this->newEmptyEntity(), $data);
                    $this->saveOrFail($document);
                }
                break;

            case 'delete':
                $this->deleteByIdOrFail($id);
                break;

            default:
                Log::warning(sprintf('Unknown operation on ElasticSearch reindex: %s', $operation));
        }
    }

    /**
     * @inheritDoc
     */
    public function findQuery(Query $query, array $options): Query
    {
        return $query->queryMust(
            fn (QueryBuilder $builder): AbstractQuery => $builder->simpleQueryString('title', $options['query']),
        );
    }
}

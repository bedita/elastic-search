<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Model\Index;

use Cake\Datasource\EntityInterface;
use Cake\ElasticSearch\Query;

/**
 * Interface for ElasticSearch indices classes to reindex an entity.
 */
interface AdapterCompatibleInterface
{
    /**
     * Perform full text search.
     *
     * @param \Cake\ElasticSearch\Query $query Query object instance.
     * @param array{query: string} $options Search options.
     * @return \Cake\ElasticSearch\Query
     */
    public function findQuery(Query $query, array $options): Query;

    /**
     * Reindex an entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity to be reindexed.
     * @param string $operation Operation.
     * @return void
     */
    public function reindex(EntityInterface $entity, string $operation): void;
}

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
     * Check if the index exists.
     *
     * @return bool
     */
    public function indexExists(): bool;

    /**
     * Create the index.
     *
     * @param array $arguments Body for the request to ElasticSearch's API
     * @param array $options Query parameters for the request to ElasticSearch's API
     * @return bool
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
     */
    public function create(array $arguments = [], array $options = []): bool;

    /**
     * Update properties mappings, if possible.
     *
     * @param array $properties New properties configuration
     * @return bool
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-mapping.html
     */
    public function updateProperties(array $properties = []): bool;

    /**
     * Update analysis settings, if possible.
     *
     * @param array $analysis New analysis configuration
     * @return bool
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html
     */
    public function updateAnalysis(array $analysis = []): bool;

    /**
     * Reindex an entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity to be reindexed.
     * @param string $operation Operation.
     * @return void
     */
    public function reindex(EntityInterface $entity, string $operation): void;
}

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
use Cake\Log\LogTrait;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Elastica\Query\AbstractQuery;
use Elasticsearch\Endpoints\Indices\PutMapping;
use Elasticsearch\Endpoints\Indices\PutSettings;

/**
 * Base search index for ElasticSearch.
 */
class SearchIndex extends Index implements AdapterCompatibleInterface
{
    use IndexTrait;
    use LogTrait;

    /**
     * Map of fields and their configuration (type, analyzer, normalizer, etc.).
     *
     * This property must be overridden by implementations to customize the mappings used by the index.
     *
     * @var array
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-params.html
     */
    protected static array $_properties = [];

    /**
     * Settings for the text analysis (define/configure analyzers, tokenizers, filters, etc.).
     *
     * @var array
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/configure-text-analysis.html
     */
    protected static array $_analysis = [];

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
     * @inheritDoc
     */
    public function create(array $arguments = [], array $options = []): bool
    {
        if (empty(Hash::get($arguments, 'mappings.properties')) && !empty(static::$_properties)) {
            $arguments = Hash::insert($arguments, 'mappings.properties', static::$_properties);
        }
        if (empty(Hash::get($arguments, 'settings.analysis')) && !empty(static::$_analysis)) {
            $arguments = Hash::insert($arguments, 'settings.analysis', static::$_analysis);
        }

        $esIndex = $this->getConnection()->getIndex($this->getName());
        $response = $esIndex->create($arguments, $options);
        if (!$response->isOk()) {
            Log::error(sprintf(
                'Error creating index "%s": %s',
                $this->getName(),
                $response->getErrorMessage(),
            ));

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function updateProperties(array $properties = []): bool
    {
        if (empty($properties)) {
            $properties = self::$_properties;
        }

        $endpoint = new PutMapping();
        $endpoint->setBody(compact('properties'));
        $esIndex = $this->getConnection()->getIndex();
        $response = $esIndex->requestEndpoint($endpoint);
        if (!$response->isOk()) {
            Log::error(sprintf(
                'Error updating index "%s" mappings: %s',
                $this->getName(),
                $response->getErrorMessage(),
            ));

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function updateAnalysis(array $analysis = []): bool
    {
        if (empty($analysis)) {
            $analysis = self::$_analysis;
        }

        // Adding new analyzers requires temporarily closing the index
        $esIndex = $this->getConnection()->getIndex();
        $response = $esIndex->close();
        if (!$response->isOk()) {
            Log::error(sprintf(
                'Error closing the index "%s" before updating analysis settings: %s',
                $this->getName(),
                $response->getErrorMessage(),
            ));

            return false;
        }

        $endpoint = new PutSettings();
        $endpoint->setBody(compact('analysis'));
        $response = $esIndex->requestEndpoint($endpoint);
        if (!$response->isOk()) {
            Log::error(sprintf(
                'Error updating index "%s" settings: %s',
                $this->getName(),
                $response->getErrorMessage(),
            ));

            return false;
        }

        $response = $esIndex->open();
        if (!$response->isOk()) {
            Log::error(sprintf(
                'Error opening the index "%s" after updating analysis settings: %s',
                $this->getName(),
                $response->getErrorMessage(),
            ));

            return false;
        }

        return true;
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

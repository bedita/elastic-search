<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Model\Index;

use Cake\Datasource\EntityInterface;
use Cake\ElasticSearch\Datasource\Connection;
use Cake\ORM\Exception\PersistenceFailedException;
use Closure;
use Elastica\Document;
use Elastica\Exception\NotFoundException;
use Psr\SimpleCache\CacheInterface;

trait IndexTrait
{
    /**
     * Returns the ElasticSearch connection instance for this index.
     *
     * @return \Cake\ElasticSearch\Datasource\Connection
     */
    abstract public function getConnection(): Connection;

    /**
     * Retrieve a document from the index.
     *
     * @param mixed $primaryKey Document ID.
     * @param array|string $finder Array of options, or finder name.
     * @param \Psr\SimpleCache\CacheInterface|string|null $cache Caching results is unsupported.
     * @param \Closure|string|null $cacheKey Caching results is unsupported.
     * @param mixed ...$args Additional arguments.
     * @return \Cake\Datasource\EntityInterface
     */
    abstract public function get(
        mixed $primaryKey,
        array|string $finder = 'all',
        CacheInterface|string|null $cache = null,
        Closure|string|null $cacheKey = null,
        mixed ...$args,
    ): EntityInterface;

    /**
     * Persist a document to the index.
     *
     * @param \Cake\Datasource\EntityInterface $entity Document.
     * @param array $options Array of options.
     * @return \Cake\Datasource\EntityInterface|false
     */
    abstract public function save(EntityInterface $entity, array $options = []): EntityInterface|false;

    /**
     * Delete a document from the index.
     *
     * @param \Cake\Datasource\EntityInterface $entity Document.
     * @param array $options Array of options.
     * @return bool
     */
    abstract public function delete(EntityInterface $entity, array $options = []): bool;

    /**
     * Retrieve a document from the index if exists, or `null` on failure.
     *
     * @see \Cake\ElasticSearch\Index::get()
     * @param string $primaryKey Document ID.
     * @param array $options Array of options.
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function getIfExists(string $primaryKey, array $options = []): ?EntityInterface
    {
        try {
            return $this->get($primaryKey, $options);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Persist a document to the index, or throw an exception upon failure.
     *
     * @param \Cake\Datasource\EntityInterface $entity Document.
     * @param array $options
     * @return \Cake\Datasource\EntityInterface
     */
    public function saveOrFail(EntityInterface $entity, array $options = []): EntityInterface
    {
        if ($this->save($entity, $options) === false) {
            throw new PersistenceFailedException($entity, ['save']);
        }

        return $entity;
    }

    /**
     * Delete a document from the index, or throw an exception upon failure.
     *
     * @param \Cake\Datasource\EntityInterface $entity Document.
     * @param array $options Array of options.
     * @return void
     */
    public function deleteOrFail(EntityInterface $entity, array $options): void
    {
        if ($this->delete($entity, $options) === false) {
            throw new PersistenceFailedException($entity, ['delete']);
        }
    }

    /**
     * Update a single field on an existing document.
     *
     * @param string $primaryKey Document ID.
     * @param string $field Name of field to update.
     * @param mixed $value New value.
     * @return bool Success.
     */
    public function set(string $primaryKey, string $field, mixed $value): bool
    {
        $esIndex = $this->getConnection()->getIndex($this->getName());
        $document = new Document($primaryKey, [$field => $value]);
        $response = $esIndex->updateDocument($document);

        return $response->isOk();
    }
}

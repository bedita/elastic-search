<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Model\Index;

use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\ElasticSearch\Query;
use Cake\ElasticSearch\QueryBuilder;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\Validation\Validator;
use Elastica\Exception\ResponseException;
use Elastica\Query\AbstractQuery;
use InvalidArgumentException;

/**
 * Base search index for BEdita objects in ElasticSearch.
 */
class ObjectSearchIndex extends SearchIndex
{
    /**
     * @inheritDoc
     */
    protected static array $_properties = [
        'uname' => ['type' => 'text'],
        'type' => ['type' => 'text'],
        'status' => ['type' => 'text'],
        'deleted' => ['type' => 'boolean'],
        'publish_start' => ['type' => 'date'],
        'publish_end' => ['type' => 'date'],
        'title' => ['type' => 'text'],
        'description' => ['type' => 'text'],
        'body' => ['type' => 'text'],
    ];

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->notEmptyString('uname')
            ->requirePresence('uname', 'create')

            ->notEmptyString('type')
            ->requirePresence('type', 'create')

            ->inList('status', ['on', 'draft', 'off'])
            ->requirePresence('status', 'create')

            ->boolean('deleted')

            ->dateTime('publish_start')
            ->allowEmptyDateTime('publish_start')

            ->dateTime('publish_end')
            ->allowEmptyDateTime('publish_end')

            ->scalar('title')
            ->allowEmptyString('title')

            ->scalar('description')
            ->allowEmptyString('description')

            ->scalar('body')
            ->allowEmptyString('body');
    }

    /**
     * @inheritDoc
     */
    public function reindex(EntityInterface $entity, string $operation): void
    {
        if (!$entity instanceof ObjectEntity) {
            Log::warning(sprintf(
                '%s index is supposed to be used only with sub-types of "%s", got "%s" instead',
                static::class,
                ObjectEntity::class,
                get_debug_type($entity),
            ));
            parent::reindex($entity, $operation);

            return;
        }

        switch ($operation) {
            case 'softDelete':
            case 'softDeleteRestore':
                try {
                    $id = (string)$entity->id;
                    if (!$this->set($id, 'deleted', $entity->deleted)) {
                        throw new PersistenceFailedException($this->get($id), ['set']);
                    }
                } catch (ResponseException $e) {
                    $fullError = (array)$e->getResponse()->getFullError();
                    if ($fullError['type'] !== 'document_missing_exception') {
                        throw $e;
                    }

                    // This scenario might be caused by an object that was not yet added to the index.
                    // Rather than updating a single field on an existing document, we should add the document instead.
                    parent::reindex($entity, 'edit');
                }
                break;

            default:
                parent::reindex($entity, $operation);
        }
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
    protected function prepareData(EntityInterface $entity): ?array
    {
        if (!$entity instanceof ObjectEntity) {
            return null;
        }

        return [
            'id' => (string)$entity->id,
            'deleted' => $entity->deleted ?? false,
        ] + $entity->extract(array_keys(static::$_properties));
    }

    /**
     * {@inheritDoc}
     *
     * @param \Cake\ElasticSearch\Query $query Query object instance.
     * @param array{query: string, type?: string|string[]} $options Search options.
     * @return \Cake\ElasticSearch\Query
     */
    public function findQuery(Query $query, array $options): Query
    {
        if (isset($options['type'])) {
            $query = $query->find('type', ['type' => $options['type']]);
        }

        return $query
            ->find('available')
            ->queryMust(
                fn(QueryBuilder $builder): AbstractQuery => $builder
                    ->simpleQueryString(['title', 'description', 'body'], $options['query']),
            );
    }

    /**
     * Find "available" documents, i.e. respective of deletion, status and publication constraints.
     *
     * @param \Cake\ElasticSearch\Query $query Query object instance.
     * @return \Cake\ElasticSearch\Query
     */
    protected function findAvailable(Query $query): Query
    {
        return $query->andWhere(function (QueryBuilder $builder): AbstractQuery {
            $conditions = [
                // Filter objects that are not deleted.
                $builder->term('deleted', 'false'),
            ];

            // Filter by object status.
            $statusLevel = Configure::read('Status.level', 'all');
            if ($statusLevel === 'on') {
                $conditions[] = $builder->term('status', 'on');
            } elseif ($statusLevel === 'draft') {
                $conditions[] = $builder->terms('status', ['on', 'draft']);
            }

            // Filter by publication date.
            if ((bool)Configure::read('Publish.checkDate', false)) {
                $now = FrozenTime::now();

                $conditions[] = $builder
                    ->or($builder->not($builder->exists('publish_start')), $builder->lte('publish_start', $now))
                    ->setMinimumShouldMatch(1);
                $conditions[] = $builder
                    ->or($builder->not($builder->exists('publish_end')), $builder->gte('publish_end', $now))
                    ->setMinimumShouldMatch(1);
            }

            return $builder->and(...$conditions);
        });
    }

    /**
     * Filter by object type.
     *
     * @param \Cake\ElasticSearch\Query $query Query object instance.
     * @param array{type: string|string[]} $options Finder options.
     * @return \Cake\ElasticSearch\Query
     */
    protected function findType(Query $query, array $options): Query
    {
        if (empty($options['type'])) {
            throw new InvalidArgumentException('Missing or empty `type` option');
        }

        return $query->andWhere(
            fn(QueryBuilder $builder): AbstractQuery => $builder->terms('type', (array)$options['type']),
        );
    }
}

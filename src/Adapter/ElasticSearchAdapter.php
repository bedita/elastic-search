<?php
declare(strict_types=1);

namespace BEdita\ElasticSearch\Adapter;

use BEdita\Core\Search\BaseAdapter;
use Cake\Database\Connection;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\EntityInterface;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Security;
use Exception;
use Psr\Log\LogLevel;
use RuntimeException;

class ElasticSearchAdapter extends BaseAdapter
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * @inheritDoc
     */
    public function search(Query $query, string $text, array $options = []): Query
    {
        return $this->buildQuery($query, $text, $options);
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function indexResource(EntityInterface $entity, string $operation): void
    {
    }

    /**
     * Build elastic search query
     *
     * @param string $text The search text
     * @param array $options The options
     * @return array
     */
    protected function buildElasticSearchQuery(string $text, array $options): array
    {
        return [];
    }

    /**
     * Build query and return it
     *
     * @param \Cake\ORM\Query $query The query
     * @param string $text The search text
     * @param array $options The options
     * @return \Cake\ORM\Query
     */
    protected function buildQuery(Query $query, string $text, array $options): Query
    {
        $results = $this->buildElasticSearchQuery($text, $options);

        if (count($results) === 0) {
            // Nothing found. No results should be returned. Add a contradiction to the `WHERE` clause.
            return $query->where(new ComparisonExpression('1', '1', 'integer', '<>'));
        }

        // Prepare temporary table with `id` and `score` from ElasticSearch results.
        $tempTable = $this->createTempTable($query->getConnection());
        $insertQuery = $tempTable->query()->insert(['id', 'score']);
        foreach ($results as $row) {
            $insertQuery = $insertQuery->values($row);
        }
        $insertQuery->execute();

        // Add a join with the temporary table to filter by ID and sort by relevance score.
        return $query
            ->innerJoin(
                $tempTable->getTable(),
                // @phpstan-ignore-next-line
                fn (QueryExpression $exp): QueryExpression => $exp->equalFields(
                    $tempTable->aliasField('id'),
                    $query->getRepository()->aliasField('id'),
                )
            )
            ->orderDesc($tempTable->aliasField('score'));
    }

    /**
     * Create a temporary table to store search results.
     * The table is created with a `score` column to sort results by relevance.
     * The table is dropped when the connection is closed.
     *
     * @param \Cake\Database\Connection $connection The database connection
     * @return \Cake\ORM\Table
     * @throws \RuntimeException
     */
    protected function createTempTable(Connection $connection): Table
    {
        $table = sprintf('elasticsearch_%s', Security::randomString(16));
        $schema = (new TableSchema($table))
            ->setTemporary(true)
            ->addColumn('id', [
                'type' => TableSchema::TYPE_INTEGER,
                'length' => 11,
                'unsigned' => true,
                'null' => false,
            ])
            ->addColumn('score', [
                'type' => TableSchema::TYPE_FLOAT,
                'null' => false,
            ])
            ->addConstraint(
                'PRIMARY',
                [
                    'type' => TableSchema::CONSTRAINT_PRIMARY,
                    'columns' => ['id'],
                ]
            )
            ->addIndex(
                sprintf('%s_score_idx', str_replace('_', '', $table)),
                [
                    'type' => TableSchema::INDEX_INDEX,
                    'columns' => ['score'],
                ]
            );

        try {
            // Execute SQL to create table. In MySQL the transaction is completely useless,
            // because `CREATE TABLE` implicitly implies a commit.
            $connection->transactional(function (Connection $connection) use ($schema): void {
                foreach ($schema->createSql($connection) as $statement) {
                    $connection->execute($statement);
                }
            });
        } catch (Exception $e) {
            $this->log(sprintf('Could not create temporary table for ElasticSearch results: %s', $e), LogLevel::ERROR);

            throw new RuntimeException('Could not create temporary table for ElasticSearch results', 0, $e);
        }

        return (new Table(compact('connection', 'table', 'schema')))
            ->setPrimaryKey('id')
            ->setDisplayField('score');
    }
}

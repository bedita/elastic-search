<?php
namespace BEdita\ElasticSearch\Adapter;

use BEdita\Core\Search\BaseAdapter;
use Cake\Database\Expression\ComparisonExpression;
use Cake\ORM\Query;
use Cake\Datasource\EntityInterface;

class ElasticSearchAdapter extends BaseAdapter
{
    /**
     * @inheritDoc
     */
    public function search(Query $query, string $text, array $options = [], array $config = []): Query
    {
        return $query;
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
     */
    protected function buildElasticSearchQuery(array $options): array
    {
        return ['todo'];
    }

    /**
     * Build query and return it
     *
     * @param Query $query The query
     * @param array $options The options
     * @return Query
     */
    protected function buildQuery(Query $query, array $options): Query
    {
        $results = $this->buildElasticSearchQuery($options);

        if (count($results) === 0) {
            // Nothing found. No results should be returned. Add a contradiction to the `WHERE` clause.
            return $query->where(new ComparisonExpression(1, 1, 'integer', '<>'));
        }

        // TODO: implement this
        return $query;

        // // Prepare temporary table with `id` and `score` from ElasticSearch results.
        // $tempTable = sprintf('elasticsearch_%s', sha1($this->compiledQuery));
        // $tempTable = $this->createTempTable($tempTable);
        // $insertQuery = $tempTable->query()->insert(['id', 'score']);
        // foreach ($results as $row) {
        //     $insertQuery = $insertQuery->values($row);
        // }
        // $insertQuery->execute();

        // // Add a join with the temporary table to filter by ID and sort by relevance score.
        // return $query
        //     ->innerJoin(
        //         $tempTable->getTable(),
        //         new ComparisonExpression(
        //             new IdentifierExpression($tempTable->aliasField('id')),
        //             new IdentifierExpression($this->getTable()->aliasField('id')),
        //             'integer',
        //             '='
        //         )
        //     )
        //     ->orderDesc($tempTable->aliasField('score'));
    }

    // /**
    //  * Create a temporary table to store search results.
    //  *
    //  * @param string $table Temporary table name.
    //  * @return \Cake\ORM\Table|null
    //  */
    // protected function createTempTable($table)
    // {
    //     // $connection = $this->getTable()->getConnection();
    //     // $schema = (new TableSchema($table))
    //     //     ->setTemporary(true)
    //     //     ->addColumn('id', [
    //     //         'type' => TableSchema::TYPE_INTEGER,
    //     //         'length' => 11,
    //     //         'unsigned' => true,
    //     //         'null' => false,
    //     //     ])
    //     //     ->addColumn('score', [
    //     //         'type' => TableSchema::TYPE_FLOAT,
    //     //         'null' => false,
    //     //     ])
    //     //     ->addConstraint(
    //     //         'PRIMARY',
    //     //         [
    //     //             'type' => TableSchema::CONSTRAINT_PRIMARY,
    //     //             'columns' => ['id'],
    //     //         ]
    //     //     )
    //     //     ->addIndex(
    //     //         sprintf('%s_score_idx', str_replace('_', '', $table)),
    //     //         [
    //     //             'type' => TableSchema::INDEX_INDEX,
    //     //             'columns' => ['score'],
    //     //         ]
    //     //     );

    //     // try {
    //     //     // Execute SQL to create table. In MySQL the transaction is completely useless,
    //     //     // because `CREATE TABLE` implicitly implies a commit.
    //     //     $connection->transactional(function (Connection $connection) use ($schema) {
    //     //         foreach ($schema->createSql($connection) as $statement) {
    //     //             $connection->execute($statement);
    //     //         }
    //     //     });
    //     // } catch (\Exception $e) {
    //     //     $this->log($e, LogLevel::CRITICAL);

    //     //     return null;
    //     // }

    //     // $table = (new Table(compact('connection', 'table', 'schema')))
    //     //     ->setPrimaryKey('id')
    //     //     ->setDisplayField('score');

    //     // return $table;
    // }
}

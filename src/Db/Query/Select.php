<?php
/**
 * Bluz Framework Component
 *
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

declare(strict_types=1);

namespace Bluz\Db\Query;

use Bluz\Db\Exception\DbException;
use Bluz\Proxy\Db;

/**
 * Builder of SELECT queries
 *
 * @package Bluz\Db\Query
 */
class Select extends AbstractBuilder
{
    use Traits\From;
    use Traits\Where;
    use Traits\Order;
    use Traits\Limit;

    /**
     * @var mixed PDO fetch types or object class
     */
    protected $fetchType = \PDO::FETCH_ASSOC;

    /**
     * {@inheritdoc}
     *
     * @param  integer|string|object $fetchType
     * @return integer|string|array
     */
    public function execute($fetchType = null)
    {
        if (!$fetchType) {
            $fetchType = $this->fetchType;
        }

        switch ($fetchType) {
            case (!is_int($fetchType)):
                return Db::fetchObjects($this->getSql(), $this->params, $fetchType);
            case \PDO::FETCH_CLASS:
                return Db::fetchObjects($this->getSql(), $this->params);
            case \PDO::FETCH_ASSOC:
            default:
                return Db::fetchAll($this->getSql(), $this->params);
        }
    }

    /**
     * Setup fetch type, any of PDO, or any Class
     *
     * @param  string $fetchType
     * @return Select instance
     */
    public function setFetchType($fetchType)
    {
        $this->fetchType = $fetchType;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getSql()
    {
        $query = "SELECT " . implode(', ', $this->sqlParts['select']) . " FROM ";

        $fromClauses = [];

        // Loop through all FROM clauses
        foreach ($this->sqlParts['from'] as $from) {
            $fromClause = $from['table'] . ' ' . $from['alias']
                . $this->getSQLForJoins($from['alias']);

            $fromClauses[$from['alias']] = $fromClause;
        }

        $query .= join(', ', $fromClauses)
            . ($this->sqlParts['where'] !== null ? " WHERE " . ((string) $this->sqlParts['where']) : "")
            . ($this->sqlParts['groupBy'] ? " GROUP BY " . join(", ", $this->sqlParts['groupBy']) : "")
            . ($this->sqlParts['having'] !== null ? " HAVING " . ((string) $this->sqlParts['having']) : "")
            . ($this->sqlParts['orderBy'] ? " ORDER BY " . join(", ", $this->sqlParts['orderBy']) : "")
            . ($this->limit ? " LIMIT ". $this->limit ." OFFSET ". $this->offset : "")
        ;

        return $query;
    }

    /**
     * Specifies an item that is to be returned in the query result
     * Replaces any previously specified selections, if any
     *
     * Example
     * <code>
     *     $sb = new Select();
     *     $sb
     *         ->select('u.id', 'p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phone', 'p', 'u.id = p.user_id');
     * </code>
     *
     * @param  string[] $select the selection expressions
     * @return Select instance
     */
    public function select(...$select)
    {
        return $this->addQueryPart('select', $select, false);
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * Example
     * <code>
     *     $sb = new Select();
     *     $sb
     *         ->select('u.id')
     *         ->addSelect('p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phone', 'u.id = p.user_id');
     * </code>
     *
     * @param  string $select the selection expression
     * @return Select instance
     */
    public function addSelect($select)
    {
        return $this->addQueryPart('select', $select, true);
    }

    /**
     * Creates and adds a join to the query
     *
     * Example
     * <code>
     *     $sb = new Select();
     *     $sb
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->join('u', 'phone', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param  string $fromAlias the alias that points to a from clause
     * @param  string $join      the table name to join
     * @param  string $alias     the alias of the join table
     * @param  string $condition the condition for the join
     * @return Select instance
     */
    public function join($fromAlias, $join, $alias, $condition = null)
    {
        return $this->innerJoin($fromAlias, $join, $alias, $condition);
    }

    /**
     * Creates and adds a join to the query
     *
     * Example
     * <code>
     *     $sb = new Select();
     *     $sb
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->innerJoin('u', 'phone', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param  string $fromAlias the alias that points to a from clause
     * @param  string $join      the table name to join
     * @param  string $alias     the alias of the join table
     * @param  string $condition the condition for the join
     * @return Select instance
     */
    public function innerJoin($fromAlias, $join, $alias, $condition = null)
    {
        $this->aliases[] = $alias;

        return $this->addQueryPart(
            'join',
            [
                $fromAlias => [
                    'joinType'      => 'inner',
                    'joinTable'     => $join,
                    'joinAlias'     => $alias,
                    'joinCondition' => $condition
                ]
            ],
            true
        );
    }

    /**
     * Creates and adds a left join to the query.
     *
     * Example
     * <code>
     *     $sb = new Select();
     *     $sb
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phone', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param  string $fromAlias the alias that points to a from clause
     * @param  string $join      the table name to join
     * @param  string $alias     the alias of the join table
     * @param  string $condition the condition for the join
     * @return Select instance
     */
    public function leftJoin($fromAlias, $join, $alias, $condition = null)
    {
        $this->aliases[] = $alias;

        return $this->addQueryPart(
            'join',
            [
                $fromAlias => [
                    'joinType'      => 'left',
                    'joinTable'     => $join,
                    'joinAlias'     => $alias,
                    'joinCondition' => $condition
                ]
            ],
            true
        );
    }

    /**
     * Creates and adds a right join to the query.
     *
     * Example
     * <code>
     *     $sb = new Select();
     *     $sb
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightJoin('u', 'phone', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param  string $fromAlias the alias that points to a from clause
     * @param  string $join      the table name to join
     * @param  string $alias     the alias of the join table
     * @param  string $condition the condition for the join
     * @return Select instance
     */
    public function rightJoin($fromAlias, $join, $alias, $condition = null)
    {
        $this->aliases[] = $alias;

        return $this->addQueryPart(
            'join',
            [
                $fromAlias => [
                    'joinType'      => 'right',
                    'joinTable'     => $join,
                    'joinAlias'     => $alias,
                    'joinCondition' => $condition
                ]
            ],
            true
        );
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * Example
     * <code>
     *     $sb = new Select();
     *     $sb
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.id');
     * </code>
     *
     * @param  string[] $groupBy the grouping expression
     * @return Select instance
     */
    public function groupBy(...$groupBy)
    {
        if (empty($groupBy)) {
            return $this;
        }

        return $this->addQueryPart('groupBy', $groupBy, false);
    }

    /**
     * Adds a grouping expression to the query.
     *
     * Example
     * <code>
     *     $sb = new Select();
     *     $sb
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.lastLogin');
     *         ->addGroupBy('u.createdAt')
     * </code>
     *
     * @param  string[] $groupBy the grouping expression
     * @return Select instance
     */
    public function addGroupBy(...$groupBy)
    {
        if (empty($groupBy)) {
            return $this;
        }

        return $this->addQueryPart('groupBy', $groupBy, true);
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any
     *
     * @param  string[] $condition the query restriction predicates
     * @return Select
     */
    public function having(...$condition)
    {
        $condition = $this->prepareCondition($condition);
        return $this->addQueryPart('having', $condition, false);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions
     *
     * @param  string[] $condition the query restriction predicates
     * @return Select
     */
    public function andHaving(...$condition)
    {
        $condition = $this->prepareCondition($condition);
        $having = $this->getQueryPart('having');

        if ($having instanceof CompositeBuilder && $having->getType() == 'AND') {
            $having->add($condition);
        } else {
            $having = new CompositeBuilder([$having, $condition]);
        }

        return $this->addQueryPart('having', $having, false);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions
     *
     * @param  string[] $condition the query restriction predicates
     * @return Select
     */
    public function orHaving(...$condition)
    {
        $condition = $this->prepareCondition($condition);
        $having = $this->getQueryPart('having');

        if ($having instanceof CompositeBuilder && $having->getType() == 'OR') {
            $having->add($condition);
        } else {
            $having = new CompositeBuilder([$having, $condition], 'OR');
        }

        return $this->addQueryPart('having', $having, false);
    }

    /**
     * Setup offset like a page number, start from 1
     *
     * @param  integer $page
     * @return Select
     * @throws DbException
     */
    public function setPage($page = 1)
    {
        if (!$this->limit) {
            throw new DbException("Please setup limit for use method `setPage`");
        }
        $this->offset = $this->limit * ($page - 1);
        return $this;
    }

    /**
     * Generate SQL string for JOINs
     *
     * @internal
     * @param  string $fromAlias alias of table
     * @return string
     */
    protected function getSQLForJoins($fromAlias)
    {
        $sql = '';

        if (isset($this->sqlParts['join'][$fromAlias])) {
            foreach ($this->sqlParts['join'][$fromAlias] as $join) {
                $sql .= ' ' . strtoupper($join['joinType'])
                    . " JOIN " . $join['joinTable'] . ' ' . $join['joinAlias']
                    . " ON " . ((string) $join['joinCondition']);
                $sql .= $this->getSQLForJoins($join['joinAlias']);
            }
        }

        return $sql;
    }
}

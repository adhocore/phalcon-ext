<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Db;

use PhalconExt\Di\ProvidesDi;

/**
 * A cross platform extension to phalcon db adapter.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
trait Extension
{
    use ProvidesDi;

    // Implemented by \Phalcon\Db\Adapter.
    abstract public function updateAsDict($table, $data, $conditions = null, $dataTypes = null);

    abstract public function insertAsDict($table, $data, $dataTypes = null);

    /**
     * Update a row matching given criteria if exists or insert new one.
     *
     * @param string $table    The table to act upon.
     * @param array  $data     The actual data dict ([field => value]) to update/insert.
     * @param array  $criteria The criteria dict ([field => value]) to match updatable row.
     *
     * @throws \InvalidArgumentException When the criteria is insufficient.
     *
     * @return bool
     */
    public function upsert(string $table, array $data, array $criteria): bool
    {
        if (empty($data)) {
            return false;
        }

        // Doesnt exist, insert new!
        if (0 === $count = $this->countBy($table, $criteria)) {
            return $this->insertAsDict($table, $data + $criteria);
        }

        // Ambiguous, multiple rows exist!
        if ($count > 1) {
            throw new \InvalidArgumentException('The criteria is not enough to fetch a single row for update!');
        }

        list($conditions, $bind) = $this->clauseBinds($criteria);

        // Update the existing data by criteria!
        return $this->updateAsDict($table, $data, \compact('conditions', 'bind'));
    }

    /**
     * Count rows in db table using given criteria.
     *
     * @param string $table
     * @param array  $criteria Col=>Val pairs
     *
     * @return int
     */
    public function countBy(string $table, array $criteria): int
    {
        if (empty($criteria)) {
            return $this->fetchColumn("SELECT COUNT(1) FROM {$table}");
        }

        list($clause, $binds) = $this->clauseBinds($criteria);

        return $this->fetchColumn("SELECT COUNT(1) FROM {$table} WHERE $clause", $binds) ?: 0;
    }

    /**
     * Prepare clause and Binds using data dict.
     *
     * @param array $dict  Col=>Val pairs
     * @param bool  $named Whether to use named placeholder.
     *
     * @return array ['clause', [binds]]
     */
    public function clauseBinds(array $dict, bool $named = false): array
    {
        $fields = [];
        foreach ($dict as $key => $value) {
            $fields[] = $named ? "$key = :$key" : "$key = ?";
        }

        return [
            \implode(' AND ', $fields),
            $named ? $dict : \array_values($dict),
        ];
    }

    /**
     * Insert bulk data to a table in single query.
     *
     * @param string $table
     * @param array  $data
     *
     * @return bool
     */
    public function insertAsBulk(string $table, array $data): bool
    {
        $binds   = [];
        $columns = $this->getInclusiveColumns($data);
        $default = \array_fill_keys($columns, null);

        foreach ($data as $row) {
            $row   = \array_merge($default, $row);
            $binds = \array_merge($binds, \array_values($row));
        }

        $sql  = "INSERT INTO {$table} (" . \implode(',', $columns) . ') VALUES ';
        $set  = '(' . \rtrim(\str_repeat('?,', \count($columns)), ',') . ')';
        $sql .= \rtrim(\str_repeat($set . ',', \count($data)), ',');

        return $this->execute($sql, $binds);
    }

    /**
     * Get inclusive columns from multiple unbalanced/unorderd data dicts.
     *
     * @param array $data
     *
     * @return array
     */
    public function getInclusiveColumns(array $data): array
    {
        $columns = [];

        foreach (\array_filter($data, 'is_array') as $row) {
            $columns = \array_merge($columns, \array_keys($row));
        }

        $columns = \array_unique($columns);
        \sort($columns);

        return $columns;
    }

    /**
     * Register sql logger.
     *
     * @param array $config
     *
     * @return self
     */
    public function registerLogger(array $config): self
    {
        $evm = $this->di('eventsManager');

        $evm->attach('db', new Logger($config));
        $this->setEventsManager($evm);

        return $this;
    }
}

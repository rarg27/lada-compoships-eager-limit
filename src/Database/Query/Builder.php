<?php

namespace Rarg27\LadaComposhipsEagerLimit\Database\Query;

use Spiritix\LadaCache\Database\QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * The maximum number of records to return per group.
     *
     * @var array
     */
    public $groupLimit;

    /**
     * Add a "group limit" clause to the query.
     *
     * @param  int    $value
     * @param  string $column
     * @return \Rarg27\LadaComposhipsEagerLimit\Database\Query\Builder
     */
    public function groupLimit($value, $column)
    {
        if ($value >= 0) {
            $this->groupLimit = compact('value', 'column');
        }

        return $this;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array                          $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        $items = parent::get($columns);

        if (!$this->groupLimit) {
            return $items;
        }

        $keys = ['laravel_row'];

        if (is_array($this->groupLimit['column'])) {
            foreach ($this->groupLimit['column'] as $i => $column) {
                $keys[] = "@laravel_partition_$i := " . $this->grammar->wrap(last(explode('.', $column)));
                $keys[] = "@laravel_partition_$i := " . $this->grammar->wrap('pivot_' . last(explode('.', $column)));
            }
        } else {
            $keys[] = '@laravel_partition := ' . $this->grammar->wrap(last(explode('.', $this->groupLimit['column'])));
            $keys[] = '@laravel_partition := ' . $this->grammar->wrap('pivot_' . last(explode('.', $this->groupLimit['column'])));
        }

        foreach ($items as $item) {
            foreach ($keys as $key) {
                unset($item->$key);
            }
        }

        return $items;
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        //Here we implement custom support for multi-column 'IN'
        //A multi-column 'IN' is a series of OR/AND clauses
        //TODO: Optimization
        if (is_array($column)) {
            $this->where(function ($query) use ($column, $values) {
                foreach ($values as $value) {
                    $query->orWhere(function ($query) use ($column, $value) {
                        foreach ($column as $index => $aColumn) {
                            $query->where($aColumn, $value[$index]);
                        }
                    });
                }
            });

            return $this;
        }

        return parent::whereIn($column, $values, $boolean, $not);
    }

    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        // If the column and values are arrays, we will assume it is a multi-columns relationship
        // and we adjust the 'where' clauses accordingly
        if (is_array($first) && is_array($second)) {
            $type = 'Column';

            foreach ($first as $index => $f) {
                $this->wheres[] = [
                    'type'     => $type,
                    'first'    => $f,
                    'operator' => $operator,
                    'second'   => $second[$index],
                    'boolean'  => $boolean,

                ];
            }

            return $this;
        }

        return parent::whereColumn($first, $operator, $second, $boolean);
    }
}
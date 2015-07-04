<?php

namespace Novanova\IPGeoBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use ArrayIterator;

/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class DatabaseDiffer
{
    const INSERTED = 'inserted';
    const UPDATED = 'updated';
    const DELETED = 'deleted';

    /**
     * @var \Generator
     */
    private $data;
    private $fields;
    private $flipFields;
    private $primary;
    private $count;
    private $limit;
    private $diff;

    public function __construct($data, $fields, $primary = ['id'], array $options = []) {
        $this->data = $data;
        $this->fields = $fields;
        $this->flipFields = array_flip($fields);
        $this->primary = $primary;

        $this->count = array_get($options, 'count', 1000);
        $this->limit = array_get($options, 'limit', 10000);
    }

    /**
     * @param Builder $query
     * @param callable $callback
     * @return \Generator
     */
    public function getDiff(Builder $query, callable $callback) {
        $this->data->rewind();
        $this->diff = [
            self::INSERTED => [],
            self::UPDATED => [],
            self::DELETED => []
        ];

        $query->chunk($this->limit, function($items) use ($callback) {
            $destination = new ArrayIterator($items);
            $destination->rewind();

            while ($destination->valid() && $this->data->valid()) {
                $item = $destination->current();
                $source = $this->data->current();
                $compare = $this->compare($source, $item);
                $type = null;

                if ($compare == 0) {
                    if (!$this->equals($source, $item)) {
                        $type = self::UPDATED;
                    }

                    $this->data->next();
                    $destination->next();
                } else if ($compare < 0){
                    $type = self::INSERTED;
                    $this->data->next();
                } else if ($compare > 0) {
                    $type = self::DELETED;
                    $destination->next();
                }

                if ($this->add($type, $source)) {
                    $callback($type, $this->diff[$type]);
                    $this->diff[$type] = [];
                }
            }

            $type = static::DELETED;

            while ($destination->valid()) {
                $source = $destination->current();

                if ($this->add($type, $source)) {
                    $callback($type, $this->diff[$type]);
                    $this->diff[$type] = [];
                }
            }
        });

        while ($this->data->valid()) {
            $source = $this->data->current();
            $this->data->next();
            $type = static::INSERTED;

            if ($this->add($type, $source)) {
                $callback($type, $this->diff[$type]);
                $this->diff[$type] = [];
            }
        }

        foreach ($this->diff as $type => $items) {
            if (!empty($items)) {
                $callback($type, $items);
            }
        }
    }

    protected function compare($lhs, $rhs) {
        foreach ($this->primary as $key) {
            $compare = strcmp("~{$lhs[$this->flipFields[$key]]}~", "~{$rhs[$key]}~");

            if ($compare !== 0) {
                return $compare;
            }
        }

        return 0;
    }

    protected function equals($lhs, $rhs) {
        foreach ($this->fields as $index => $key) {
            if (is_numeric($lhs[$index]) && is_numeric($rhs[$key])) {
                if ($lhs[$index] != $rhs[$key]) {
                    return $lhs[$index] < $rhs[$key];
                }
            } else if (strcmp($lhs[$index], $rhs[$key]) !== 0) {
                return false;
            }
        }

        return true;
    }

    protected function add($type, $item) {
        if (empty($type)) {
            return false;
        }

        $model = [];

        foreach ($item as $key => $value) {
            $model[$this->fields[$key]] = $value;
        }

        $this->diff[$type][] = $model;

        return count($this->diff[$type]) >= $this->count;
    }
}
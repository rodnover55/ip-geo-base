<?php

namespace Novanova\IPGeoBase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use ArrayIterator;
use Illuminate\Support\Collection;
use Labora\DAL\Models\Product;

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

    private $rowsNumber;
    private $currentRow;

    /**
     * @return mixed
     */
    public function getRowsNumber()
    {
        return $this->rowsNumber;
    }

    /**
     * @return mixed
     */
    public function getCurrentRow()
    {
        return $this->currentRow;
    }

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

        $this->rowsNumber = $query->count();
        $this->currentRow = 1;

        $items = $query->get();

        $destination = new ArrayIterator($items);
        $destination->rewind();

        while ($destination->valid() && $this->data->valid()) {
            $item = $this->convertModel($destination->current());

            $source = $this->convertSource($this->data->current());
            $compare = $this->compare($source, $item);
            $type = null;

            if ($compare == 0) {
                if (!$this->equals($source, $item)) {
                    $type = self::UPDATED;
                }

                $this->data->next();
                $destination->next();
                $this->currentRow++;
            } else if ($compare < 0){
                $type = self::INSERTED;
                $item2 = Product::find($source['id']);
                $this->data->next();
            } else if ($compare > 0) {
                $type = self::DELETED;
                $source = $item;
                $destination->next();
                $this->currentRow++;
            }

            if ($this->add($type, $source)) {
                $callback($type, $this->diff[$type]);
                $this->diff[$type] = [];
            }
        }

        $type = static::DELETED;

        while ($destination->valid()) {
            $source = $this->convertModel($destination->current());

            if ($this->add($type, $source)) {
                $callback($type, $this->diff[$type]);
                $this->diff[$type] = [];
            }

            $destination->next();
            $this->currentRow++;
            }

        while ($this->data->valid()) {
            $source = $this->convertSource($this->data->current());
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

    protected function convertModel($item) {
        return ($item instanceof Model) ? ($item->toArray()) : ((array)$item);
    }

    protected function convertSource($item) {
        return (new Collection($this->flipFields))->map(function ($field, $key) use ($item) {
            return $item[$this->flipFields[$key]];
        })->toArray();
    }

    protected function compare($lhs, $rhs) {
        foreach ($this->primary as $key) {
            $compare = strcmp("~{$lhs[$key]}~", "~{$rhs[$key]}~");

            if ($compare !== 0) {
                return $compare;
            }
        }

        return 0;
    }

    protected function equals($lhs, $rhs) {
        foreach ($this->fields as $key) {
            if (is_numeric($lhs[$key]) && is_numeric($rhs[$key])) {
                if ($lhs[$key] != $rhs[$key]) {
                    return $lhs[$key] < $rhs[$key];
                }
            } else if (strcmp($lhs[$key], $rhs[$key]) !== 0) {
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
            $model[$key] = $value;
        }

        $this->diff[$type][] = $model;

        return count($this->diff[$type]) >= $this->count;
    }
}
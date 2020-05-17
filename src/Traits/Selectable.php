<?php

namespace Dykhuizen\Datatable\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

trait Selectable {

	/** @var array */
	public static array $selectableFields = [];

	/** @var string  */
	protected $selectableFieldsKey = 'selectableFields';

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public function scopeSelectable($query) {
        if (request()->hasFilled([$this->selectableFieldsKey])) {
            /** @var \Dykhuizen\Datatable\Traits\Selectable $class */
            $class = get_class($this);
            $class::$selectableFields = $this->filterAndExplode(
            	request()->input($this->selectableFieldsKey, '')
			);
        }

        return $query;
    }

    /**
     * @return array
     */
    public function toArray() {
        if (self::$selectableFields == []) {
            return parent::toArray();
        } else {
            $select = [];
            foreach (self::$selectableFields as $selectable) {
                $select = Arr::add($select, $selectable, '');
            }

            return $this->fetchSelect($this, $select, 1, 3);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\Dykhuizen\Datatable\Traits\Selectable $model
     * @param array $select
     * @param integer $currentDepth
     * @param integer $maxDepth
     * @return null
     */
    private function fetchSelect($model, $select, $currentDepth = 1, $maxDepth = 3) {
        $response = [];

        if ($currentDepth <= $maxDepth) {
            if ($select == "") {
                return $model->toArray();
            } else {
                foreach ($select as $key => $value) {
                    // Check if relation
                    if ($this->hasRelation($key, $model)) {
                        if (!($model->relationLoaded($key))) {
                            abort(403, "Attempted to access invalid relation key ${key}");
                        }
                        // If it is a collection, it is a toMany relation
                        if ($model->{$key} instanceof Collection) {
                            $response[$key] = [];
                            foreach ($this->$key as $index => $item) {
                                $response[$key][] = $this->fetchSelect($model->{$key}[$index], $value, $currentDepth + 1, $maxDepth);
                            }
                        } else if ($model->$key) {
                            $response[$key] = $this->fetchSelect($model->{$key}, $value, $currentDepth + 1, $maxDepth);
                        } else {
                            $response[$key] = null;
                        }
                    } // Dealing with raw values
                    else {
                        $response[$key] = $model->{$key} ?? null;
                    }
                }

                // This will filter out all hidden / visible attributes
                $response = $this->getArrayableItems($response);
            }
        } else {
            abort(403, "Attempted to access invalid data");
        }

        return $response;
    }
}

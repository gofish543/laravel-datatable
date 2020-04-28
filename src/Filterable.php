<?php

namespace Dykhuizen\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait Filterable {

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterable(Builder $query) {
        if (request()->hasFilled(['filter'])) {
            $params = [
                'filter' => $this->filterAndExplode(request()->input('filter'))
            ];

            foreach (request()->all() as $key => $item) {
                if (strstr($key, 'filter_')) {
                    $params[str_replace('filter_', '', $key)] = $this->filterAndExplode($item ?: '');
                }
            }

            return $this->queryFilterBuilder($query, $params);
        }

        return $query;
    }


    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function queryFilterBuilder($query, array $params) {
        return $query->where(function (Builder $query) use ($params) {
            foreach ($params['filter'] as $column) {
                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model = $this;

                if (isset($params[$column])) {
                    $values = $params[$column];

                    $isRelation = $this->explodeRelation($column);
                    if (!empty($isRelation)) {
                        $relationName = $isRelation[0];
                        $column = $isRelation[1];

                        if ($this->hasRelation($relationName)) {
                            $model = $query->getRelation($relationName)->getRelated();
                            $query = $query->orWhereHas($relationName, function (Builder $query) use ($model, $column, $values) {
                                return $this->applyFilterQuery($query, $model, $column, $values, 'where');
                            });
                        }
                    } else {
                        $query = $this->applyFilterQuery($query, $model, $column, $values, 'where');
                    }
                }
            }
        });
    }

    /**
     * Since the logic for filtering can be complex with different column types
     * Put all logic of filtering in separate function
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $column
     * @param array $values
     * @param string $where
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilterQuery(Builder $query, Model $model, string $column, array $values, string $where = 'orWhere') {
        if (count($values) == 0) {
            $query = $query->whereRaw('FALSE = TRUE');
        } else if (method_exists($model, Str::camel($column) . 'Filterable')) {
            $query = call_user_func_array([$this, Str::camel($column) . 'Filterable'], [$query, $values]);
        } else if ($this->columnExists($model, $column)) {
            $tableColumn = $model->qualifyColumn($column);

            $query = $query->{$where}(function (Builder $query) use ($values, $model, $column, $tableColumn) {
                foreach ($values as $value) {
                    if (in_array($column, $model->getDates())) {
                        // Searching by date field
                        // The search could be a boolean true/false, turn into where[Not]Null
                        switch (Str::lower($value)) {
                            case 'yes':
                            case 'true':
                                $query = $query->orWhereNotNull($tableColumn);
                                break;
                            case 'no':
                            case 'false':
                                $query = $query->orWhereNull($tableColumn);
                                break;
                            default:
                                $query = $query->orWhereDate($tableColumn, '=', $value);
                                break;
                        }
                    } else {
                        switch (Str::lower($value)) {
                            case 'yes':
                            case 'true':
                                $query = $query->orWhere($tableColumn, '=', true);
                                break;
                            case 'no':
                            case 'false':
                                $query = $query->orWhere($tableColumn, '=', false);
                                break;
                            default:
                                $query = $query->orWhere($tableColumn, '=', $value);
                                break;
                        }
                    }
                }
            });
        }

        return $query;
    }
}
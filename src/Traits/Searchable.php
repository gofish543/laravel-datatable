<?php

namespace Gofish\Datatable\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


trait Searchable {

	/** @var string */
	protected $searchableColumnsKey = 'searchColumns';

	/** @var string */
	protected $searchableSearchKey = 'search';

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchable(Builder $query) {
        if (request()->hasFilled([$this->searchableColumnsKey, $this->searchableSearchKey])) {
            return $this->querySearchBuilder($query, request()->only([$this->searchableColumnsKey, $this->searchableSearchKey]));
        }

        return $query;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function querySearchBuilder($query, array $params) {
        return $query->where(function (Builder $query) use ($params) {
            $search = $params[$this->searchableSearchKey];
            $columns = $this->filterAndExplode($params[$this->searchableColumnsKey]);

            foreach ($columns as $column) {
                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model = $this;

                $isRelation = $this->explodeRelation($column);
                if (!empty($isRelation)) {
                    $relationName = $isRelation[0];
                    $column = $isRelation[1];

                    if ($this->hasRelation($relationName)) {
                        $model = $query->getRelation($relationName)->getRelated();
                        $query = $query->orWhereHas($relationName, function (Builder $query) use ($model, $column, $search) {
                            return $this->applySearchableQuery($query, $model, $column, $search, 'where');
                        });
                    }
                } else {
                    $query = $this->applySearchableQuery($query, $model, $column, $search, 'orWhere');
                }
            }

            return $query;
        });
    }

    /**
     * Since the logic for searching can be complex with different column types
     * Put all logic of searching in separate function
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $column
     * @param string $search
     * @param string $where
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applySearchableQuery(Builder $query, Model $model, string $column, string $search, string $where = 'orWhere') {
        return $query->{$where}(function (Builder $query) use ($model, $column, $search) {
            if (method_exists($model, Str::camel($column) . 'Searchable')) {
                $query = call_user_func_array([$model, Str::camel($column) . 'Searchable'], [$query, $search]);
            } else if ($this->columnExists($model, $column)) {
                $tableColumn = $model->qualifyColumn($column);

                if (in_array($column, $model->getDates())) {
                    // Searching by date field
                    // The search could be a boolean true/false, turn into where[Not]Null
                    switch (Str::lower($search)) {
                        case 'yes':
                        case 'true':
                            $query = $query->orWhereNotNull($tableColumn);
                            break;
                        case 'no':
                        case 'false':
                            $query = $query->orWhereNull($tableColumn);
                            break;
                        default:
                            $query = $query->orWhereDate($tableColumn, '=', $search);
                            break;
                    }
                } else {
                    // Searching by a generic field
                    $query = $query->orWhere($tableColumn, 'LIKE', "%{$this->escapeLike($search)}%");
                }
            } else {
                $query = $this->nullQuery($query);
            }

            return $query;
        });
    }
}
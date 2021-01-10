<?php

namespace Dykhuizen\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * @method static static|self|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder datatable()
 * @method static static|self|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder selectable()
 * @method static static|self|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder searchable()
 * @method static static|self|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder sortable()
 * @method static static|self|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder filterable()
 * @method static static|self|\Illuminate\Database\Eloquent\Collection paginateable($forcePagination = true)
 * @method static static|self|\Illuminate\Database\Eloquent\Collection simplePaginateable($forcePagination = true)
 */
trait Datatable {

    use Traits\Searchable;
    use Traits\Sortable;
    use Traits\Selectable;
    use Traits\Paginateable;
    use Traits\Filterable;

    /**
     * Initialize the datatable trait
     *
     * @return void
     */
    public static function bootDatatable() {
        // Reset $selectableFields on boot
        self::$selectableFields = [];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|self $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDatatable(Builder $query) {
        return $query->selectable()->searchable()->filterable()->sortable();
    }

    /**
     * @param string $string
     * @param string $char
     * @return string
     */
    protected function escapeLike(string $string, string $char = '\\') {
        return str_replace(
            [$char, '%', '_'],
            [$char . $char, $char . '%', $char . '_'],
            $string
        );
    }

    /**
     * Take a string phrase, filter out the bad elements and explode it
     *
     * @param string $phrase
     * @param \Closure|null $filterFunction
     * @return array
     */
    protected function filterAndExplode(string $phrase, $filterFunction = null) {
        if (is_null($filterFunction)) {
            $filterFunction = function ($item) {
                return $item !== null && $item !== '';
            };
        }

        return array_values(array_filter(
            explode(',',
                trim(
                    str_replace(', ', ',', $phrase), " \t\n\r\0\x0B,"
                )
            ), $filterFunction
        ));
    }

    /**
     * Explodes parameter if possible and returns array [column, relation]
     * Empty array is returned if explode could not run eg: separator was not found.
     *
     * @param string $parameter
     * @return array
     */
    protected function explodeRelation($parameter) {
        $separator = '.';
        if (Str::contains($parameter, $separator)) {
            $relation = explode($separator, $parameter);
            if (count($relation) == 2) {
                return $relation;
            }
        }

        return [];
    }

    /**
     * Check the schema if a column exists
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $column
     * @return bool
     */
    protected function columnExists(Model $model, string $column) {
        return Schema::connection($model->getConnection()->getName())
            ->hasColumn($model->getTable(), $column);
    }

    /**
     * Return the uniqueId generated for the join statement
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @return string
     */
    protected function queryJoinBuilder(Builder $query, Relation $relation) {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable = $relation->getParent()->getTable();

        if ($parentTable == $relatedTable) {
            $query = $query->from($parentTable . ' as parent_' . $parentTable);
            $parentTable = 'parent_' . $parentTable;
            $relation->getParent()->setTable($parentTable);
        }

        // Mark the left join by a random string
        // This avoids any leftJoin's commited before or after this query call by the end user
        $uniqueId = Str::random(16);
        if ($relation instanceof HasOne) {
            $relatedPrimaryKey = "{$uniqueId}.{$relation->getForeignKeyName()}";
            $parentPrimaryKey = $relation->getQualifiedParentKeyName();
        } elseif ($relation instanceof BelongsTo) {
            $relatedPrimaryKey = "{$uniqueId}.{$relation->getOwnerKeyName()}";
            $parentPrimaryKey = $relation->getQualifiedForeignKeyName();
        } else {
            return $this->nullQuery($query);
        }

        $query->leftJoin("{$relatedTable} as {$uniqueId}", $parentPrimaryKey, '=', $relatedPrimaryKey);

        return $uniqueId;
    }

    /**
     * Determine if the given relationship (method) exists.
     *
     * @param string $key
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @return bool
     */
    protected function hasRelation($key, $model = null) {
        if (is_null($model)) {
            $model = $this;
        }

        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if ($model->relationLoaded($key)) {
            return true;
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        if (method_exists($model, $key)) {
            // Uses PHP built in function to determine whether the returned object is a laravel relation
            return is_a($model->$key(), Relation::class);
        }

        return false;
    }

    /**
     * Null out a given query with a false = true statement
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function nullQuery(Builder $query) {
        return $query->whereRaw('0 = 1');
    }
}

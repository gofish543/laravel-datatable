<?php

namespace Gofish\Datatable\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait Sortable
{
	/** @var string */
	protected $sortableColumnsKey = 'sortColumns';

	/** @var string */
	protected $sortableOrderKey = 'sortOrder';

	/**
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeSortable(Builder $query)
	{
		if (request()->hasFilled([$this->sortableColumnsKey, $this->sortableOrderKey]))
		{
			return $this->queryOrderBuilder($query, request()->only([$this->sortableColumnsKey, $this->sortableOrderKey]));
		}

		return $query;
	}

	/**
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array $params
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function queryOrderBuilder(Builder $query, array $params)
	{
		$columns = $this->filterAndExplode($params[$this->sortableColumnsKey]);
		$directions = $this->filterAndExplode($params[$this->sortableOrderKey]);

		if (count($columns) != count($directions))
		{
			return $this->nullQuery($query);
		}

		for ($index = 0; $index < count($columns); $index++)
		{
			/** @var \Illuminate\Database\Eloquent\Model $model */
			$model = $this;

			$column = $columns[$index];
			$direction = $directions[$index];

			$isRelation = $this->explodeRelation($column);
			if (!empty($isRelation))
			{
				$relationName = $isRelation[0];
				$column = $isRelation[1];

				// Verify the relation exists
				if ($this->hasRelation($relationName))
				{
					$relation = $query->getRelation($relationName);
					$model = $relation->getRelated();
					$query = $this->queryJoinBuilder($query, $relation);
					$query = $this->applyOrderQuery($query, $model, $column, $direction);
				}
			}
			else
			{
				$query = $this->applyOrderQuery($query, $model, $column, $direction);
			}
		}

		return $query;
	}

	/**
	 * Since the logic for searching can be complex with different column types
	 * Put all logic of searching in separate function
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param \Illuminate\Database\Eloquent\Model $model
	 * @param string $column
	 * @param string $direction
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function applyOrderQuery(Builder $query, Model $model, string $column, string $direction)
	{
		if (method_exists($model, Str::camel($column) . 'Sortable'))
		{
			$query = call_user_func_array([$model, Str::camel($column) . 'Sortable'], [$query, $direction]);
		}
		elseif ($this->columnExists($model, $column))
		{
			$column = $model->qualifyColumn($column);
			$query = $query->orderBy($column, $direction);
		}
		else
		{
			$query = $this->nullQuery($query);
		}

		return $query;
	}
}
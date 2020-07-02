<?php

namespace Dykhuizen\Datatable\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method int getPerPage()
 */
trait Paginateable
{

    /** @var string */
    protected $paginateablePageKey = 'page';

    /** @var string */
    protected $paginateablePerPageKey = 'per_page';

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $simple
     * @return \Illuminate\Database\Eloquent\Model[]|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function scopePaginateable(Builder $query, $simple = false)
    {
        if (request()->hasFilled([$this->paginateablePageKey, $this->paginateablePerPageKey]))
        {
            return $simple ?
                $query->simplePaginate(
                    request()->input('per_page', $this->getPerPage()),
                    ['*'],
                    $this->paginateablePageKey
                ) : $query->paginate(
                    request()->input('per_page', $this->getPerPage()),
                    ['*'],
                    $this->paginateablePageKey
                );
        }

        return $query->get();
    }
}

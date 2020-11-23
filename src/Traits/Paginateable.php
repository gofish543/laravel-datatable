<?php

namespace Dykhuizen\Datatable\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method int getPerPage()
 */
trait Paginateable {

    /** @var int */
    protected $maxPerPage = 100;

    /** @var string */
    protected $paginateablePageKey = 'page';

    /** @var string */
    protected $paginateablePerPageKey = 'per_page';

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $forcePagination
     * @return \Illuminate\Database\Eloquent\Model[]|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function scopePaginateable(Builder $query, $forcePagination = true) {
        if (request()->hasFilled([$this->paginateablePageKey, $this->paginateablePerPageKey])) {
            return $query->paginate(
                min(request()->input('per_page', $this->getPerPage()), $this->maxPerPage),
                ['*'],
                $this->paginateablePageKey
            );
        }

        return $forcePagination ? $query->paginate() : $query->get();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $forcePagination
     * @return \Illuminate\Database\Eloquent\Model[]|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function scopeSimplePaginateable(Builder $query, $forcePagination = true) {
        if (request()->hasFilled([$this->paginateablePageKey, $this->paginateablePerPageKey])) {
            return $query->simplePaginate(
                min(request()->input('per_page', $this->getPerPage()), $this->maxPerPage),
                ['*'],
                $this->paginateablePageKey
            );
        }

        return $forcePagination ? $query->simplePaginate() : $query->get();
    }
}

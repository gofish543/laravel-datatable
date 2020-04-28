<?php

namespace Dykhuizen\Datatable;

use Illuminate\Database\Eloquent\Builder;

trait Paginateable {

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Model[]|\Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function scopePaginateable(Builder $query) {
        if (request()->hasFilled(['page', 'per_page'])) {
            return $query->paginate(
                request()->input('per_page', $this->getPerPage())
            );
        }

        return $query->get();
    }
}
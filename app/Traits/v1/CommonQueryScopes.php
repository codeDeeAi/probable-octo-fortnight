<?php

declare(strict_types=1);

namespace App\Traits\v1;

use Illuminate\Database\Eloquent\Builder;

trait CommonQueryScopes
{
    /**
     * Filter by date range (default: created_at)
     */
    public function scopeFilterByDate(Builder $query, ?string $date = null, string $column = 'created_at'): Builder
    {
        return $query->when($date, fn ($q) => $q->whereDate($column, $date)
        );
    }

    /**
     * Search by title (or any given column)
     */
    public function scopeSearchByTitle(Builder $query, ?string $title = null, string $column = 'title'): Builder
    {

        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->when($title, fn ($q) => $q->where($column, $operator, "%{$title}%")
        );
    }
}

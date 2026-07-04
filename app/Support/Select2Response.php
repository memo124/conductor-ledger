<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Select2Response
{
    public static function fromPaginator(LengthAwarePaginator $paginator, callable $mapper): array
    {
        return [
            'results' => collect($paginator->items())->map($mapper)->values()->all(),
            'pagination' => ['more' => $paginator->hasMorePages()],
        ];
    }

    public static function fromCollection(Collection $items, callable $mapper, bool $hasMore = false): array
    {
        return [
            'results' => $items->map($mapper)->values()->all(),
            'pagination' => ['more' => $hasMore],
        ];
    }
}

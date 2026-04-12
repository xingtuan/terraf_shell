<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;

class PaginatesResources
{
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}

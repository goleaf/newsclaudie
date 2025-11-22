<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Contracts\Pagination\Paginator;

/**
 * Contract for components that handle pagination.
 */
interface Paginatable
{
    /**
     * Get the paginated results.
     */
    public function getPaginator(): Paginator;

    /**
     * Get the current per-page value.
     */
    public function getPerPage(): int;

    /**
     * Get the available per-page options.
     *
     * @return array<int>
     */
    public function getPerPageOptions(): array;
}


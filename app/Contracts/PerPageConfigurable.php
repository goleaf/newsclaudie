<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for components that can configure per-page options.
 */
interface PerPageConfigurable
{
    /**
     * Get the default per-page value.
     */
    public function getDefaultPerPage(): int;

    /**
     * Get the available per-page options.
     *
     * @return array<int>
     */
    public function getAvailablePerPageOptions(): array;

    /**
     * Get the query parameter name for per-page.
     */
    public function getPerPageQueryParam(): string;
}



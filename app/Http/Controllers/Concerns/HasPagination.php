<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Contracts\PerPageConfigurable;
use App\Support\Pagination\PageSize;
use Illuminate\Http\Request;

/**
 * Trait for controllers that handle pagination.
 */
trait HasPagination
{
    /**
     * Resolve per-page value from request using the controller's configuration.
     */
    protected function resolvePerPage(Request $request, ?int $requested = null): int
    {
        if (! $this instanceof PerPageConfigurable) {
            return PageSize::FALLBACK;
        }

        $param = $this->getPerPageQueryParam();
        $requested = $requested ?? $request->integer($param, 0) ?: null;

        return PageSize::resolve(
            $requested,
            $this->getAvailablePerPageOptions(),
            $this->getDefaultPerPage()
        );
    }

    /**
     * Get sanitized per-page options.
     *
     * @return array<int>
     */
    protected function getSanitizedPerPageOptions(): array
    {
        if (! $this instanceof PerPageConfigurable) {
            return [PageSize::FALLBACK];
        }

        return PageSize::options(
            $this->getAvailablePerPageOptions(),
            $this->getDefaultPerPage()
        );
    }
}



<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Support\Pagination\PageSize;

/**
 * Shared helpers for Livewire components that expose a configurable $perPage value.
 *
 * Intended for use alongside Livewire's WithPagination trait.
 */
trait ManagesPerPage
{
    public ?int $perPage = null;

    public function bootManagesPerPage(): void
    {
        $requested = $this->perPage;

        foreach (['perPage', PageSize::queryParam()] as $param) {
            $value = request()->query($param);

            if ($value !== null && $value !== '') {
                $requested = $value;
                break;
            }
        }

        $this->perPage = $this->sanitizePerPage(
            is_numeric($requested) ? (int) $requested : $this->defaultPerPage()
        );
    }

    public function updatingPerPage(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Ensure any manual assignments or query-string driven updates stay within the allowed range.
     */
    public function updatedPerPage($value): void
    {
        $this->perPage = $this->sanitizePerPage((int) $value);
    }

    /**
     * Expose the allowed per-page options to Blade via $this->perPageOptions.
     *
     * @return array<int>
     */
    public function getPerPageOptionsProperty(): array
    {
        return $this->availablePerPageOptions();
    }

    /**
     * Override this in the component when a different default is required.
     */
    protected function defaultPerPage(): int
    {
        return PageSize::contextDefault($this->perPageContext());
    }

    /**
     * Override this in the component to customize the selectable options.
     *
     * @return array<int>
     */
    protected function availablePerPageOptions(): array
    {
        return PageSize::contextOptions($this->perPageContext());
    }

    /**
     * Standard query-string config for per-page values, keeping the default out of the URL.
     */
    protected function perPageQueryStringConfig(?string $alias = null): array
    {
        $config = ['except' => $this->defaultPerPage()];

        if ($alias) {
            $config['as'] = $alias;
        }

        return $config;
    }

    protected function sanitizePerPage(int $value): int
    {
        $options = collect($this->availablePerPageOptions());
        $default = $this->defaultPerPage();

        return PageSize::resolve($value, $options->all(), $default);
    }

    /**
     * Override to pull a different option/default context from interface.php.
     */
    protected function perPageContext(): string
    {
        return 'admin';
    }
}


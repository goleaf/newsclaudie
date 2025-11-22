<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Resets pagination and normalizes search terms in Livewire tables.
 */
trait WithSearch
{
    public function updatingSearch(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function updatedSearch(?string $value): void
    {
        $this->search = $this->normalizeSearch($value);
    }

    protected function searchTerm(): string
    {
        return $this->normalizeSearch($this->search ?? '');
    }

    protected function normalizeSearch(?string $value): string
    {
        return trim((string) $value);
    }
}

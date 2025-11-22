<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for items that can be displayed in tables.
 */
interface TableDisplayable
{
    /**
     * Get the table row data for this item.
     *
     * @return array<string, mixed>
     */
    public function getTableRowData(): array;

    /**
     * Get the table header columns.
     *
     * @return array<string>
     */
    public static function getTableHeaders(): array;
}



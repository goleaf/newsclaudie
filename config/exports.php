<?php

declare(strict_types=1);

return [
    'disk' => env('EXPORT_DISK', config('filesystems.default', 'local')),
    'directory' => env('EXPORT_DIRECTORY', 'exports'),
    'max_sync_rows' => (int) env('EXPORT_MAX_SYNC_ROWS', 500),
    'link_ttl_minutes' => (int) env('EXPORT_LINK_TTL', 30),
    'queue' => env('EXPORT_QUEUE'),
];

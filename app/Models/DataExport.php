<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

final class DataExport extends Model
{
    use HasFactory;

    public const TYPE_POSTS = 'posts';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'format',
        'status',
        'disk',
        'path',
        'total_rows',
        'filters',
        'expires_at',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_rows' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markCompleted(string $path, int $rows, CarbonInterface $expiresAt): void
    {
        $this->forceFill([
            'path' => $path,
            'total_rows' => $rows,
            'expires_at' => $expiresAt,
            'completed_at' => Carbon::now(),
            'status' => self::STATUS_COMPLETED,
        ])->save();
    }

    public function markFailed(): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'completed_at' => Carbon::now(),
        ])->save();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->path !== null
            && ! $this->isExpired();
    }

    public function downloadFilename(): string
    {
        $timestamp = ($this->completed_at ?? Carbon::now())->format('Ymd_His');

        return "{$this->type}-export-{$timestamp}.{$this->format}";
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class Comment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'post_id',
        'content',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'status' => CommentStatus::class,
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => CommentStatus::Pending->value,
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Approved);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Pending);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Rejected);
    }

    public function scopeWithStatus(Builder $query, ?CommentStatus $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function isApproved(): bool
    {
        return $this->status === CommentStatus::Approved;
    }

    public function isPending(): bool
    {
        return $this->status === CommentStatus::Pending;
    }

    public function isRejected(): bool
    {
        return $this->status === CommentStatus::Rejected;
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}

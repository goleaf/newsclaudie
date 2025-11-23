<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The relationships that should always be eager loaded.
     *
     * @var array<int, string>
     */
    protected $with = ['user'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'post_id',
        'content',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => CommentStatus::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => CommentStatus::Pending->value,
    ];

    /**
     * Scope a query to only include approved comments.
     *
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Approved);
    }

    /**
     * Scope a query to only include pending comments.
     *
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Pending);
    }

    /**
     * Scope a query to only include rejected comments.
     *
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Rejected);
    }

    /**
     * Scope a query to filter by status.
     *
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopeWithStatus(Builder $query, ?CommentStatus $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    /**
     * Scope a query to order comments by creation date.
     *
     * @param Builder<Comment> $query
     * @param string $direction Sort direction ('desc' for newest first, 'asc' for oldest first)
     * @return Builder<Comment>
     */
    public function scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Scope a query to order comments by newest first.
     *
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDate('desc');
    }

    /**
     * Scope a query to order comments by oldest first.
     *
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopeOldest(Builder $query): Builder
    {
        return $query->orderByDate('asc');
    }

    /**
     * Check if the comment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === CommentStatus::Approved;
    }

    /**
     * Check if the comment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === CommentStatus::Pending;
    }

    /**
     * Check if the comment is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === CommentStatus::Rejected;
    }

    /**
     * Get the user who wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post this comment belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Approve the comment.
     * 
     * Transitions the comment to approved status. This method provides
     * a clear intent and can be extended with events/notifications.
     *
     * @return bool True if the comment was approved, false if already approved
     */
    public function approve(): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        $this->status = CommentStatus::Approved;
        $this->save();

        return true;
    }

    /**
     * Reject the comment.
     * 
     * Transitions the comment to rejected status. This method provides
     * a clear intent and can be extended with events/notifications.
     *
     * @return bool True if the comment was rejected, false if already rejected
     */
    public function reject(): bool
    {
        if ($this->isRejected()) {
            return false;
        }

        $this->status = CommentStatus::Rejected;
        $this->save();

        return true;
    }

    /**
     * Mark the comment as pending.
     * 
     * Transitions the comment back to pending status. Useful for
     * re-review workflows.
     *
     * @return bool True if the comment was marked pending, false if already pending
     */
    public function markPending(): bool
    {
        if ($this->isPending()) {
            return false;
        }

        $this->status = CommentStatus::Pending;
        $this->save();

        return true;
    }

    /**
     * Scope a query to filter comments by post.
     *
     * @param Builder<Comment> $query
     * @param int|Post $post Post ID or Post model instance
     * @return Builder<Comment>
     */
    public function scopeForPost(Builder $query, int|Post $post): Builder
    {
        $postId = $post instanceof Post ? $post->id : $post;
        
        return $query->where('post_id', $postId);
    }

    /**
     * Scope a query to filter comments by user.
     *
     * @param Builder<Comment> $query
     * @param int $userId User ID
     * @return Builder<Comment>
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to get recent comments.
     *
     * @param Builder<Comment> $query
     * @param int $limit Number of comments to retrieve
     * @return Builder<Comment>
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->latest()->limit($limit);
    }

    /**
     * Get the formatted creation date.
     *
     * @return string
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if the comment can be edited by the given user.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function canBeEditedBy(User $user): bool
    {
        return $user->is_admin || $user->id === $this->user_id;
    }

    /**
     * Check if the comment can be deleted by the given user.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $user->is_admin || $user->id === $this->user_id;
    }
}

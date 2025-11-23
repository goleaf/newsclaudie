<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The relationships that should always be eager loaded.
     *
     * @var list<string>
     */
    protected $with = ['user'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'post_id',
        'content',
        'status',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => CommentStatus::class,
        'approved_at' => 'datetime',
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
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Approved);
    }

    /**
     * Scope a query to only include pending comments.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Pending);
    }

    /**
     * Scope a query to only include rejected comments.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Rejected);
    }

    /**
     * Scope a query to filter by status.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeWithStatus(Builder $query, ?CommentStatus $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    /**
     * Scope a query to order comments by creation date.
     *
     * @param  Builder<Comment>  $query
     * @param  string  $direction  Sort direction ('desc' for newest first, 'asc' for oldest first)
     * @return Builder<Comment>
     */
    public function scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Scope a query to order comments by newest first.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDate('desc');
    }

    /**
     * Scope a query to order comments by oldest first.
     *
     * @param  Builder<Comment>  $query
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
     * Get the user who approved this comment.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve the comment.
     *
     * Transitions the comment to approved status. This method provides
     * a clear intent and can be extended with events/notifications.
     *
     * @param  User|null  $approver  The user approving the comment
     * @return bool True if the comment was approved, false if already approved
     */
    public function approve(?User $approver = null): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        $this->status = CommentStatus::Approved;
        $this->approved_at = now();

        if ($approver !== null) {
            $this->approved_by = $approver->id;
        }

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
     * @param  Builder<Comment>  $query
     * @param  int|Post  $post  Post ID or Post model instance
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
     * @param  Builder<Comment>  $query
     * @param  int  $userId  User ID
     * @return Builder<Comment>
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to get recent comments.
     *
     * @param  Builder<Comment>  $query
     * @param  int  $limit  Number of comments to retrieve
     * @return Builder<Comment>
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->latest()->limit($limit);
    }

    /**
     * Scope a query to filter comments from a specific IP address.
     * Useful for spam detection and moderation.
     *
     * @param  Builder<Comment>  $query
     * @param  string  $ipAddress  IP address to filter by
     * @return Builder<Comment>
     */
    public function scopeFromIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope a query to get comments awaiting moderation.
     * Alias for pending() for better semantic clarity.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeAwaitingModeration(Builder $query): Builder
    {
        return $query->pending();
    }

    /**
     * Scope a query to get comments approved within a date range.
     * Useful for analytics and reporting.
     *
     * @param  Builder<Comment>  $query
     * @param  string  $from  Start date (Y-m-d format)
     * @param  string|null  $to  End date (Y-m-d format), null for today
     * @return Builder<Comment>
     */
    public function scopeApprovedBetween(Builder $query, string $from, ?string $to = null): Builder
    {
        $to = $to ?? now()->format('Y-m-d');

        return $query->approved()
            ->whereBetween('approved_at', [$from.' 00:00:00', $to.' 23:59:59']);
    }

    /**
     * Get the formatted creation date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if the comment can be edited by the given user.
     */
    public function canBeEditedBy(User $user): bool
    {
        return $user->is_admin || $user->id === $this->user_id;
    }

    /**
     * Check if the comment can be deleted by the given user.
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $user->is_admin || $user->id === $this->user_id;
    }

    /**
     * Get the count of comments from the same IP address.
     * Useful for spam detection.
     * 
     * Note: This method is cached for performance. Use getCachedCommentsFromSameIpCount()
     * for better performance in loops or bulk operations.
     */
    public function getCommentsFromSameIpCount(): int
    {
        if ($this->ip_address === null) {
            return 0;
        }

        return self::fromIp($this->ip_address)
            ->where('id', '!=', $this->id)
            ->count();
    }

    /**
     * Get the count of comments from the same IP address with caching.
     * Cache for 10 minutes since IP-based counts don't change frequently.
     * 
     * @return int
     */
    private function getCachedCommentsFromSameIpCount(): int
    {
        if ($this->ip_address === null) {
            return 0;
        }

        return Cache::remember(
            "ip:{$this->ip_address}:comment_count",
            now()->addMinutes(10),
            fn () => self::fromIp($this->ip_address)
                ->where('id', '!=', $this->id)
                ->count()
        );
    }

    /**
     * Check if this comment might be spam based on simple heuristics.
     * This is a basic implementation - consider using a dedicated spam detection service.
     * 
     * Results are cached for 5 minutes to improve performance.
     */
    public function isPotentialSpam(): bool
    {
        // Cache the spam check result for 5 minutes
        return Cache::remember(
            "comment:{$this->id}:spam_check",
            now()->addMinutes(5),
            function () {
                // Check for excessive links (more than 3)
                $linkCount = mb_substr_count(mb_strtolower($this->content), 'http');
                if ($linkCount > 3) {
                    return true;
                }

                // Check for excessive uppercase (more than 50% of content)
                $uppercaseCount = mb_strlen(preg_replace('/[^A-Z]/', '', $this->content));
                $totalLetters = mb_strlen(preg_replace('/[^A-Za-z]/', '', $this->content));
                if ($totalLetters > 0 && ($uppercaseCount / $totalLetters) > 0.5) {
                    return true;
                }

                // Check for very short content (less than 3 characters)
                if (mb_strlen(mb_trim($this->content)) < 3) {
                    return true;
                }

                // Check for excessive comments from same IP (using cached count)
                if ($this->getCachedCommentsFromSameIpCount() > 10) {
                    return true;
                }

                return false;
            }
        );
    }

    /**
     * Check if multiple comments are potential spam in a single query.
     * Much more efficient than calling isPotentialSpam() in a loop.
     *
     * @param  \Illuminate\Support\Collection<int, Comment>  $comments
     * @return \Illuminate\Support\Collection<int, bool> Comment ID => is spam
     */
    public static function bulkCheckSpam($comments): \Illuminate\Support\Collection
    {
        // Get all unique IP addresses
        $ipAddresses = $comments->pluck('ip_address')->unique()->filter();

        // Get comment counts per IP in a single query
        $ipCounts = self::query()
            ->whereIn('ip_address', $ipAddresses)
            ->select('ip_address', DB::raw('COUNT(*) as count'))
            ->groupBy('ip_address')
            ->pluck('count', 'ip_address');

        // Check each comment
        return $comments->mapWithKeys(function ($comment) use ($ipCounts) {
            $isSpam = false;

            // Link check
            $linkCount = mb_substr_count(mb_strtolower($comment->content), 'http');
            if ($linkCount > 3) {
                $isSpam = true;
            }

            // Uppercase check
            if (! $isSpam) {
                $uppercaseCount = mb_strlen(preg_replace('/[^A-Z]/', '', $comment->content));
                $totalLetters = mb_strlen(preg_replace('/[^A-Za-z]/', '', $comment->content));
                if ($totalLetters > 0 && ($uppercaseCount / $totalLetters) > 0.5) {
                    $isSpam = true;
                }
            }

            // Length check
            if (! $isSpam && mb_strlen(mb_trim($comment->content)) < 3) {
                $isSpam = true;
            }

            // IP frequency check (using pre-fetched counts)
            if (! $isSpam && $comment->ip_address && ($ipCounts[$comment->ip_address] ?? 0) > 10) {
                $isSpam = true;
            }

            return [$comment->id => $isSpam];
        });
    }

    /**
     * Get a sanitized version of the IP address for display.
     * Masks the last octet for privacy.
     */
    public function getMaskedIpAttribute(): ?string
    {
        if ($this->ip_address === null) {
            return null;
        }

        // IPv4
        if (mb_strpos($this->ip_address, '.') !== false) {
            $parts = explode('.', $this->ip_address);
            $parts[3] = 'xxx';

            return implode('.', $parts);
        }

        // IPv6 - mask last segment
        if (mb_strpos($this->ip_address, ':') !== false) {
            $parts = explode(':', $this->ip_address);
            $parts[count($parts) - 1] = 'xxxx';

            return implode(':', $parts);
        }

        return $this->ip_address;
    }

    /**
     * Get comments with optimal eager loading for display.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeWithDisplayRelations(Builder $query): Builder
    {
        return $query->with(['user:id,name,email', 'post:id,user_id,title,slug']);
    }

    /**
     * Get comments with optimal eager loading for moderation.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeWithModerationRelations(Builder $query): Builder
    {
        return $query->with([
            'user:id,name,email,is_admin',
            'post:id,user_id,title,slug',
            'approver:id,name',
        ]);
    }

    /**
     * Get approved comment count with caching.
     * Useful for dashboard statistics.
     */
    public static function cachedApprovedCount(): int
    {
        return Cache::remember(
            'comments:approved:count',
            now()->addMinutes(15),
            fn () => self::approved()->count()
        );
    }

    /**
     * Get pending comment count with caching.
     * Useful for moderation queue badge.
     */
    public static function cachedPendingCount(): int
    {
        return Cache::remember(
            'comments:pending:count',
            now()->addMinutes(5), // Shorter TTL for moderation queue
            fn () => self::pending()->count()
        );
    }

    /**
     * Get rejected comment count with caching.
     * Useful for statistics.
     */
    public static function cachedRejectedCount(): int
    {
        return Cache::remember(
            'comments:rejected:count',
            now()->addMinutes(15),
            fn () => self::rejected()->count()
        );
    }

    /**
     * The "booted" method of the model.
     * Sets up cache invalidation on model events.
     */
    protected static function booted(): void
    {
        // Clear caches when comment is created
        static::created(function (Comment $comment) {
            self::clearStatsCaches();
            self::clearIpCache($comment->ip_address);
        });

        // Clear caches when comment is updated
        static::updated(function (Comment $comment) {
            self::clearStatsCaches();
            Cache::forget("comment:{$comment->id}:spam_check");

            // If IP changed, clear both old and new IP caches
            if ($comment->isDirty('ip_address')) {
                self::clearIpCache($comment->getOriginal('ip_address'));
                self::clearIpCache($comment->ip_address);
            }
        });

        // Clear caches when comment is deleted
        static::deleted(function (Comment $comment) {
            self::clearStatsCaches();
            self::clearIpCache($comment->ip_address);
            Cache::forget("comment:{$comment->id}:spam_check");
        });
    }

    /**
     * Clear statistics caches.
     */
    private static function clearStatsCaches(): void
    {
        Cache::forget('comments:approved:count');
        Cache::forget('comments:pending:count');
        Cache::forget('comments:rejected:count');
    }

    /**
     * Clear IP-based caches.
     */
    private static function clearIpCache(?string $ipAddress): void
    {
        if ($ipAddress) {
            Cache::forget("ip:{$ipAddress}:comment_count");
        }
    }
}

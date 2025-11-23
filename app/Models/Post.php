<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Controllers\MarkdownFileParser;
use App\Scopes\PublishedScope;
use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Throwable;

final class Post extends Model
{
    use HasFactory;

    /**
     * The relationships that should always be eager loaded.
     *
     * @var array<int, string>
     */
    protected $with = ['author'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'body',
        'description',
        'featured_image',
        'tags',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the model's Description. If one has not been set we return a truncated part of the body.
     */
    public function description(): Attribute
    {
        return new Attribute(
            get: fn ($value) => empty($value)
                ? mb_substr($this->body, 0, 255)
                : $value
        );
    }

    /**
     * Get the model's Featured Image. If one has not been set we return a default image.
     *
     * Default Image by Picjumbo
     *
     * @see https://picjumbo.com/tremendous-mountain-peak-krivan-in-high-tatras-slovakia/
     */
    public function featuredImage(): Attribute
    {
        return new Attribute(
            get: fn ($value) => empty($value)
                ? asset('storage/default.jpg')
                : $value
        );
    }

    /**
     * Check if the post is published by comparing the published_at date with the current date.
     */
    public function isPublished(): bool
    {
        return ($this->published_at !== null) && $this->published_at->isPast();
    }

    /**
     * Check if the post was created using a markdown file. Used to show a warning in the editor that changes may be overridden if the file is changed.
     */
    public function isFileBased(): bool
    {
        try {
            MarkdownFileParser::getQualifiedFilepath($this->slug);

            return true;
        } catch (Throwable $th) {
            //
        }

        return false;
    }

    /**
     * Get the author of the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get all approved comments for the post.
     */
    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)->approved();
    }

    /**
     * Get all categories associated with this post.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps();
    }

    /**
     * Get the view count for the post
     */
    public function getViewCount(): int
    {
        if (! config('analytics.enabled')) {
            throw new BadMethodCallException('Analytics are not enabled');
        }

        $cacheKey = "post.{$this->id}.views";
        $cacheDuration = config('analytics.view_count_cache_duration');

        // Get the cached value (even if expired)
        $value = cache()->get($cacheKey);

        if ($value !== null) {
            // If the cache exists but is stale, dispatch background refresh
            if (! cache()->has($cacheKey)) {
                dispatch(function () use ($cacheKey, $cacheDuration) {
                    $newValue = PageView::where('page', route('posts.show', $this, false))->count();
                    cache()->put($cacheKey, $newValue, now()->addMinutes($cacheDuration));
                })->afterResponse();
            }

            return $value;
        }

        // If no cached value exists at all, fetch and cache synchronously
        $value = PageView::where('page', route('posts.show', $this, false))->count();
        cache()->put($cacheKey, $value, now()->addMinutes($cacheDuration));

        return $value;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Order by latest posts by default, with draft posts first
        self::addGlobalScope('order', function (Builder $builder): void {
            $builder->orderByRaw('-published_at');
        });

        // Filter out posts that are not published
        self::addGlobalScope(new PublishedScope);
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);

        if (in_array($slug, ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'], true)) {
            $slug .= '-post';
        }

        $query = self::withoutGlobalScopes();

        if ($query->where('slug', $slug)->exists()) {
            $nextId = ($query->max('id') ?? 0) + 1;

            return sprintf('%s-%d', $slug, $nextId);
        }

        return $slug;
    }

    /**
     * Scope a query to filter posts by categories (OR logic).
     *
     * Filters posts that belong to ANY of the specified categories.
     * Uses whereHas with whereIn for efficient querying.
     *
     * @param Builder<Post> $query The query builder instance
     * @param array<int> $categoryIds Array of category IDs to filter by
     */
    public function scopeFilterByCategories(Builder $query, array $categoryIds): void
    {
        if (empty($categoryIds)) {
            return;
        }

        $query->whereHas('categories', function (Builder $q) use ($categoryIds): void {
            $q->whereIn('categories.id', $categoryIds);
        });
    }

    /**
     * Scope a query to filter posts by authors (OR logic).
     *
     * Filters posts authored by ANY of the specified users.
     * Uses whereIn for efficient querying with index.
     *
     * @param Builder<Post> $query The query builder instance
     * @param array<int> $authorIds Array of user IDs to filter by
     */
    public function scopeFilterByAuthors(Builder $query, array $authorIds): void
    {
        if (empty($authorIds)) {
            return;
        }

        $query->whereIn('user_id', $authorIds);
    }

    /**
     * Scope a query to filter posts by date range.
     *
     * Filters posts published within the specified date range.
     * Both from_date and to_date are optional and inclusive.
     * Uses indexed published_at column for performance.
     *
     * @param Builder<Post> $query The query builder instance
     * @param string|null $fromDate Start date (Y-m-d format), null for no lower bound
     * @param string|null $toDate End date (Y-m-d format), null for no upper bound
     */
    public function scopeFilterByDateRange(Builder $query, ?string $fromDate, ?string $toDate): void
    {
        if ($fromDate !== null) {
            $query->where('published_at', '>=', $fromDate.' 00:00:00');
        }

        if ($toDate !== null) {
            $query->where('published_at', '<=', $toDate.' 23:59:59');
        }
    }

    /**
     * Scope a query to sort posts by publication date.
     *
     * Sorts posts by their published_at timestamp in the specified direction.
     * Uses indexed published_at column for performance.
     *
     * @param Builder<Post> $query The query builder instance
     * @param string $direction Sort direction ('asc' or 'desc')
     */
    public function scopeSortByPublishedDate(Builder $query, string $direction = 'desc'): void
    {
        $query->orderBy('published_at', $direction);
    }

    /**
     * Scope a query to only include published posts.
     *
     * Filters posts that have a published_at date set and are not scheduled for the future.
     * This is a convenience scope for security and consistency.
     * Uses indexed published_at column for performance.
     *
     * @param Builder<Post> $query The query builder instance
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include draft posts.
     *
     * @param Builder<Post> $query The query builder instance
     */
    public function scopeDraft(Builder $query): void
    {
        $query->whereNull('published_at')
            ->orWhere('published_at', '>', now());
    }
}

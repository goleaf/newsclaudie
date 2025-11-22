<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Controllers\MarkdownFileParser;
use App\Scopes\PublishedScope;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'description',
        'featured_image',
        'tags',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
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

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all of the comments for the Post
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }

    /**
     * Get all categories associated with this post
     */
    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class);
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
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Order by latest posts by default, with draft posts first
        self::addGlobalScope('order', function (Builder $builder) {
            $builder->orderByRaw('-published_at');
        });

        // Filter out posts that are not published
        self::addGlobalScope(new PublishedScope);
    }
}

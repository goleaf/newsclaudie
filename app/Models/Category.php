<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get all posts associated with this category.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)
            ->withTimestamps();
    }

    /**
     * Get only published posts associated with this category.
     */
    public function publishedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)
            ->withTimestamps()
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', now())
            ->orderBy('posts.published_at', 'desc');
    }

    /**
     * Scope a query to only include categories with published posts.
     *
     * @param Builder<Category> $query
     */
    public function scopeWithPublishedPosts(Builder $query): void
    {
        $query->whereHas('posts', function (Builder $q): void {
            $q->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        });
    }

    /**
     * Get the count of published posts for this category.
     */
    public function publishedPostsCount(): int
    {
        return $this->posts()
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', now())
            ->count();
    }
}

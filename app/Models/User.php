<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_author',
        'is_banned',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_author' => 'boolean',
        'is_banned' => 'boolean',
    ];

    /**
     * Get all posts authored by the user.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get all published posts authored by the user.
     */
    public function publishedPosts(): HasMany
    {
        return $this->hasMany(Post::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');
    }

    /**
     * Get all comments by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get all approved comments by the user.
     */
    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)->approved();
    }

    /**
     * Get all data exports for the user.
     */
    public function dataExports(): HasMany
    {
        return $this->hasMany(DataExport::class);
    }

    /**
     * Scope a query to only include admin users.
     *
     * @param Builder<User> $query
     */
    public function scopeAdmins(Builder $query): void
    {
        $query->where('is_admin', true);
    }

    /**
     * Scope a query to only include author users.
     *
     * @param Builder<User> $query
     */
    public function scopeAuthors(Builder $query): void
    {
        $query->where('is_author', true);
    }

    /**
     * Scope a query to only include active (non-banned) users.
     *
     * @param Builder<User> $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_banned', false);
    }

    /**
     * Scope a query to only include banned users.
     *
     * @param Builder<User> $query
     */
    public function scopeBanned(Builder $query): void
    {
        $query->where('is_banned', true);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if the user is an author.
     */
    public function isAuthor(): bool
    {
        return $this->is_author;
    }

    /**
     * Check if the user is banned.
     */
    public function isBanned(): bool
    {
        return $this->is_banned;
    }

    /**
     * Check if the user can publish posts.
     */
    public function canPublish(): bool
    {
        return ($this->is_admin || $this->is_author) && ! $this->is_banned;
    }
}

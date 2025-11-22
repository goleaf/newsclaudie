<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Service for filtering and loading news posts.
 *
 * Handles all business logic related to news filtering, including:
 * - Building filtered queries
 * - Loading filter options
 * - Pagination
 */
final class NewsFilterService
{
    /**
     * Items per page for news listing.
     */
    private const ITEMS_PER_PAGE = 15;

    /**
     * Get paginated news posts with applied filters.
     *
     * @param array<string, mixed> $filters Validated filter parameters
     * @return array{posts: LengthAwarePaginator, totalCount: int}
     */
    public function getFilteredPosts(array $filters): array
    {
        $query = $this->buildQuery($filters);
        $totalCount = $query->count();
        $posts = $query->paginate(self::ITEMS_PER_PAGE)->withQueryString();

        return [
            'posts' => $posts,
            'totalCount' => $totalCount,
        ];
    }

    /**
     * Get available filter options (categories and authors with published posts).
     *
     * @return array{categories: Collection, authors: Collection}
     */
    public function getFilterOptions(): array
    {
        return [
            'categories' => $this->getCategoriesWithPublishedPosts(),
            'authors' => $this->getAuthorsWithPublishedPosts(),
        ];
    }

    /**
     * Build the filtered query for news posts.
     *
     * @param array<string, mixed> $filters Filter parameters
     * @return Builder
     */
    private function buildQuery(array $filters): Builder
    {
        $query = Post::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['author', 'categories']);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters['sort'] ?? 'newest');

        return $query;
    }

    /**
     * Apply all filters to the query.
     *
     * @param Builder $query
     * @param array<string, mixed> $filters
     * @return void
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['categories'])) {
            $query->filterByCategories($filters['categories']);
        }

        if (! empty($filters['authors'])) {
            $query->filterByAuthors($filters['authors']);
        }

        $query->filterByDateRange(
            $filters['from_date'] ?? null,
            $filters['to_date'] ?? null
        );
    }

    /**
     * Apply sorting to the query.
     *
     * @param Builder $query
     * @param string $sort Sort parameter
     * @return void
     */
    private function applySorting(Builder $query, string $sort): void
    {
        $direction = match ($sort) {
            'oldest' => 'asc',
            default => 'desc',
        };

        $query->sortByPublishedDate($direction);
    }

    /**
     * Get categories that have published posts.
     *
     * @return Collection<int, Category>
     */
    private function getCategoriesWithPublishedPosts(): Collection
    {
        return Category::query()
            ->whereHas('posts', $this->publishedPostsConstraint())
            ->orderBy('name')
            ->get();
    }

    /**
     * Get authors who have published posts.
     *
     * @return Collection<int, User>
     */
    private function getAuthorsWithPublishedPosts(): Collection
    {
        return User::query()
            ->whereHas('posts', $this->publishedPostsConstraint())
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the constraint for filtering published posts.
     *
     * @return \Closure(Builder): void
     */
    private function publishedPostsConstraint(): \Closure
    {
        return function (Builder $query): void {
            $query->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        };
    }
}

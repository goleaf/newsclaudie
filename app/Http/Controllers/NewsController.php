<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\NewsIndexRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Controller for the news listing page.
 *
 * Provides a public-facing news page with comprehensive filtering and sorting
 * capabilities for published blog posts. This controller implements a clean
 * separation of concerns by extracting query building logic into private methods.
 *
 * **Features:**
 * - Multi-category filtering with OR logic
 * - Multi-author filtering with OR logic
 * - Date range filtering (from/to dates)
 * - Bi-directional sorting (newest/oldest first)
 * - Pagination with query string preservation
 * - Eager loading for optimal performance
 *
 * **Design Notes:**
 * - Uses query scopes on the Post model for reusable filtering logic
 * - Extracts filter option loading to avoid code duplication
 * - Maintains published post constraint consistency across queries
 * - For larger applications, consider using NewsFilterService (see docs/NEWS_CONTROLLER_REFACTORING.md)
 *
 * @see NewsIndexRequest For request validation rules and parameters
 * @see Post For model query scopes (filterByCategories, filterByAuthors, etc.)
 * @see resources/views/news/index.blade.php For the view template
 * @see docs/NEWS_CONTROLLER_REFACTORING.md For refactoring notes and service layer alternative
 *
 * @package App\Http\Controllers
 * @author Laravel Blog Application
 * @version 1.0.0
 */
final class NewsController extends Controller
{
    /**
     * Number of posts to display per page.
     *
     * This constant defines the pagination size for the news listing.
     * Extracted as a constant to avoid magic numbers and allow easy adjustment.
     *
     * @var int
     */
    private const ITEMS_PER_PAGE = 15;

    /**
     * Display the news page with filterable published posts.
     *
     * This is the main entry point for the news listing page. It handles:
     * 1. Request validation (via NewsIndexRequest)
     * 2. Query building with filters
     * 3. Pagination
     * 4. Loading filter options for the UI
     * 5. Rendering the view with all necessary data
     *
     * **Query Parameters:**
     * - `categories[]`: Array of category IDs (OR logic)
     * - `authors[]`: Array of author/user IDs (OR logic)
     * - `from_date`: Start date for date range filter (Y-m-d format)
     * - `to_date`: End date for date range filter (Y-m-d format)
     * - `sort`: Sort order ('newest' or 'oldest', defaults to 'newest')
     * - `page`: Current page number for pagination
     *
     * **View Data:**
     * - `posts`: LengthAwarePaginator of Post models with author and categories
     * - `categories`: Collection of categories that have published posts
     * - `authors`: Collection of users who have published posts
     * - `totalCount`: Total number of posts matching the filters
     * - `appliedFilters`: Array of currently applied filter values
     *
     * **Example Usage:**
     * ```
     * GET /news
     * GET /news?categories[]=1&categories[]=2&sort=oldest
     * GET /news?authors[]=5&from_date=2024-01-01&to_date=2024-12-31
     * ```
     *
     * @param NewsIndexRequest $request Validated request with filter parameters
     * @return View News index view with posts and filter options
     *
     * @see NewsIndexRequest::rules() For complete validation rules
     * @see buildNewsQuery() For query building logic
     * @see loadFilterOptions() For filter options loading
     */
    public function index(NewsIndexRequest $request): View
    {
        $validated = $request->validated();

        // Build and execute the filtered query
        $query = $this->buildNewsQuery($validated);
        $posts = $query->paginate(self::ITEMS_PER_PAGE)->withQueryString();

        // Load filter options for the UI
        $filterOptions = $this->loadFilterOptions();

        return view('news.index', [
            'posts' => $posts,
            'categories' => $filterOptions['categories'],
            'authors' => $filterOptions['authors'],
            'totalCount' => $posts->total(),
            'appliedFilters' => $validated,
        ]);
    }

    /**
     * Build the news query with all applicable filters.
     *
     * Constructs a query builder instance with the following applied:
     * 1. Base published posts filter (published_at is not null and <= now)
     * 2. Eager loading of author and categories relationships
     * 3. Optional category filter (OR logic via whereHas)
     * 4. Optional author filter (OR logic via whereIn)
     * 5. Optional date range filter (from_date and/or to_date)
     * 6. Sort order by publication date
     *
     * **Performance Considerations:**
     * - Uses eager loading to prevent N+1 queries
     * - Applies filters before pagination for accurate counts
     * - Uses query scopes for reusable and testable logic
     *
     * **Filter Logic:**
     * - Categories: Posts matching ANY of the selected categories (OR)
     * - Authors: Posts by ANY of the selected authors (OR)
     * - Date Range: Posts within the specified date range (AND)
     * - All filters are combined with AND logic
     *
     * @param array<string, mixed> $filters Validated filter parameters from request
     *                                       - categories: int[] (optional)
     *                                       - authors: int[] (optional)
     *                                       - from_date: string|null (Y-m-d format)
     *                                       - to_date: string|null (Y-m-d format)
     *                                       - sort: string (optional, 'newest' or 'oldest')
     * @return Builder Query builder with all filters applied, ready for pagination
     *
     * @see Post::filterByCategories() For category filtering implementation
     * @see Post::filterByAuthors() For author filtering implementation
     * @see Post::filterByDateRange() For date range filtering implementation
     * @see Post::sortByPublishedDate() For sorting implementation
     */
    private function buildNewsQuery(array $filters): Builder
    {
        // Start with published posts only, with eager loading for performance
        // This base query ensures we only show posts that are:
        // 1. Not drafts (published_at is not null)
        // 2. Not scheduled for future (published_at <= now)
        $query = Post::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['author', 'categories']);

        // Apply category filter (OR logic) - show posts in ANY selected category
        if (! empty($filters['categories'])) {
            $query->filterByCategories($filters['categories']);
        }

        // Apply author filter (OR logic) - show posts by ANY selected author
        if (! empty($filters['authors'])) {
            $query->filterByAuthors($filters['authors']);
        }

        // Apply date range filter - both from_date and to_date are optional
        // If both provided, posts must be within the range (inclusive)
        $query->filterByDateRange(
            $filters['from_date'] ?? null,
            $filters['to_date'] ?? null
        );

        // Apply sort order - defaults to newest first if not specified
        $sortDirection = $this->resolveSortDirection($filters['sort'] ?? 'newest');
        $query->sortByPublishedDate($sortDirection);

        return $query;
    }

    /**
     * Load filter options for the UI (categories and authors with published posts).
     *
     * Retrieves the lists of categories and authors that should be displayed
     * in the filter panel. Only includes categories and authors that have at
     * least one published post to avoid showing empty filter options.
     *
     * **Why Filter Options?**
     * - Improves UX by showing only relevant filters
     * - Prevents users from selecting filters that yield no results
     * - Keeps the UI clean and focused
     *
     * **Performance Note:**
     * These queries are separate from the main posts query and could be
     * cached if the filter options don't change frequently.
     *
     * @return array{categories: Collection<int, Category>, authors: Collection<int, User>}
     *         Array with 'categories' and 'authors' keys containing collections
     *
     * @see loadCategoriesWithPublishedPosts() For category loading logic
     * @see loadAuthorsWithPublishedPosts() For author loading logic
     */
    private function loadFilterOptions(): array
    {
        return [
            'categories' => $this->loadCategoriesWithPublishedPosts(),
            'authors' => $this->loadAuthorsWithPublishedPosts(),
        ];
    }

    /**
     * Load categories that have at least one published post.
     *
     * Uses whereHas to filter categories to only those with published posts.
     * Results are ordered alphabetically by category name for consistent UI.
     *
     * **Query Explanation:**
     * - whereHas('posts', ...) - Only categories with related posts
     * - publishedPostsConstraint() - Only published posts (not drafts/future)
     * - orderBy('name') - Alphabetical order for better UX
     *
     * @return Collection<int, Category> Collection of Category models
     *
     * @see Category For the Category model
     * @see publishedPostsConstraint() For the published posts filter
     */
    private function loadCategoriesWithPublishedPosts(): Collection
    {
        return Category::query()
            ->whereHas('posts', $this->publishedPostsConstraint())
            ->orderBy('name')
            ->get();
    }

    /**
     * Load authors (users) who have at least one published post.
     *
     * Uses whereHas to filter users to only those who have authored published posts.
     * Results are ordered alphabetically by user name for consistent UI.
     *
     * **Query Explanation:**
     * - whereHas('posts', ...) - Only users with related posts
     * - publishedPostsConstraint() - Only published posts (not drafts/future)
     * - orderBy('name') - Alphabetical order for better UX
     *
     * **Note:** This assumes users have a 'name' column. If using a different
     * column (e.g., 'username'), update the orderBy clause accordingly.
     *
     * @return Collection<int, User> Collection of User models who are authors
     *
     * @see User For the User model
     * @see publishedPostsConstraint() For the published posts filter
     */
    private function loadAuthorsWithPublishedPosts(): Collection
    {
        return User::query()
            ->whereHas('posts', $this->publishedPostsConstraint())
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the query constraint for published posts.
     *
     * Returns a closure that can be used in whereHas/orWhereHas queries to
     * filter for published posts. This constraint is reused across multiple
     * queries to ensure consistency in what constitutes a "published" post.
     *
     * **Published Post Criteria:**
     * 1. published_at is not null (not a draft)
     * 2. published_at <= now() (not scheduled for future)
     *
     * **Why a Closure?**
     * - Reusability: Same logic used for categories and authors
     * - Consistency: Single source of truth for "published" definition
     * - Maintainability: Change in one place affects all usages
     *
     * **Example Usage:**
     * ```php
     * Category::whereHas('posts', $this->publishedPostsConstraint())->get();
     * ```
     *
     * @return \Closure(Builder): void Closure that applies published post constraints
     *
     * @see loadCategoriesWithPublishedPosts() For usage example
     * @see loadAuthorsWithPublishedPosts() For usage example
     */
    private function publishedPostsConstraint(): \Closure
    {
        return function (Builder $query): void {
            $query->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        };
    }

    /**
     * Resolve sort direction from sort parameter.
     *
     * Converts the user-friendly sort parameter ('newest' or 'oldest') into
     * the database sort direction ('desc' or 'asc'). Uses PHP 8's match
     * expression for clean, type-safe mapping.
     *
     * **Mapping:**
     * - 'oldest' → 'asc' (ascending, oldest posts first)
     * - 'newest' → 'desc' (descending, newest posts first)
     * - default → 'desc' (any other value defaults to newest)
     *
     * **Why This Method?**
     * - Separates presentation logic (newest/oldest) from database logic (asc/desc)
     * - Provides a clear default behavior
     * - Makes the code more readable and maintainable
     *
     * @param string $sort Sort parameter from request ('newest' or 'oldest')
     * @return string Database sort direction ('asc' or 'desc')
     *
     * @see buildNewsQuery() For usage in query building
     */
    private function resolveSortDirection(string $sort): string
    {
        return match ($sort) {
            'oldest' => 'asc',
            default => 'desc',
        };
    }
}

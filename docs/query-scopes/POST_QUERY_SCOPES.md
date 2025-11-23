# Post Query Scopes Documentation

**Last Updated:** November 23, 2025  
**Version:** 1.0.0  
**Feature:** News Page Filtering

## Overview

The Post model includes a set of reusable query scopes that provide filtering and sorting capabilities for the News Page feature. These scopes are designed to be composable, testable, and performant.

## Table of Contents

- [Available Scopes](#available-scopes)
- [Usage Examples](#usage-examples)
- [Performance Considerations](#performance-considerations)
- [Testing](#testing)
- [Design Decisions](#design-decisions)
- [Related Documentation](#related-documentation)

## Available Scopes

### `filterByCategories(array $categoryIds)`

Filters posts that belong to ANY of the specified categories (OR logic).

**Parameters:**
- `$categoryIds` (array<int>): Array of category IDs to filter by

**Behavior:**
- Uses `whereHas` with `whereIn` for efficient querying
- Returns posts that belong to at least one of the specified categories
- Empty array is ignored (no filtering applied)

**Example:**
```php
// Get posts in category 1 OR category 2
$posts = Post::filterByCategories([1, 2])->get();
```

**Requirements:** 2.2, 2.3

---

### `filterByAuthors(array $authorIds)`

Filters posts authored by ANY of the specified users (OR logic).

**Parameters:**
- `$authorIds` (array<int>): Array of user IDs to filter by

**Behavior:**
- Uses `whereIn` on the `user_id` column
- Returns posts authored by at least one of the specified users
- Empty array is ignored (no filtering applied)

**Example:**
```php
// Get posts by author 5 OR author 10
$posts = Post::filterByAuthors([5, 10])->get();
```

**Requirements:** 4.2, 4.3

---

### `filterByDateRange(?string $fromDate, ?string $toDate)`

Filters posts published within the specified date range (inclusive).

**Parameters:**
- `$fromDate` (string|null): Start date in Y-m-d format (e.g., '2024-01-01')
- `$toDate` (string|null): End date in Y-m-d format (e.g., '2024-12-31')

**Behavior:**
- Both parameters are optional
- `$fromDate` filters posts published on or after the date (>=)
- `$toDate` filters posts published on or before the date (<=)
- Uses `whereDate` for date-only comparisons (ignores time)
- Null values are ignored (no bound applied)

**Examples:**
```php
// Posts from February 2024 onwards
$posts = Post::filterByDateRange('2024-02-01', null)->get();

// Posts up to December 2024
$posts = Post::filterByDateRange(null, '2024-12-31')->get();

// Posts within a specific range
$posts = Post::filterByDateRange('2024-01-01', '2024-12-31')->get();
```

**Requirements:** 3.2, 3.3, 3.4

---

### `sortByPublishedDate(string $direction = 'desc')`

Sorts posts by their publication date.

**Parameters:**
- `$direction` (string): Sort direction, either 'asc' or 'desc' (default: 'desc')

**Behavior:**
- Orders posts by the `published_at` column
- 'desc' = newest first (default)
- 'asc' = oldest first

**Examples:**
```php
// Newest posts first (default)
$posts = Post::sortByPublishedDate('desc')->get();

// Oldest posts first
$posts = Post::sortByPublishedDate('asc')->get();
```

**Requirements:** 5.2, 5.3

---

### `published()`

Filters to only include published posts (convenience scope).

**Parameters:** None

**Behavior:**
- Filters posts where `published_at` is not null
- Filters posts where `published_at` is not in the future
- Equivalent to the global `PublishedScope` but can be applied manually

**Example:**
```php
// Get only published posts (when global scope is disabled)
$posts = Post::withoutGlobalScopes()->published()->get();
```

**Note:** This scope is primarily used when global scopes are disabled for testing or special queries.

## Usage Examples

### Basic Filtering

```php
use App\Models\Post;

// Single category filter
$posts = Post::filterByCategories([1])->get();

// Multiple categories (OR logic)
$posts = Post::filterByCategories([1, 2, 3])->get();

// Single author filter
$posts = Post::filterByAuthors([5])->get();

// Date range filter
$posts = Post::filterByDateRange('2024-01-01', '2024-12-31')->get();
```

### Combined Filters

```php
// Category AND author AND date range
$posts = Post::filterByCategories([1, 2])
    ->filterByAuthors([5, 10])
    ->filterByDateRange('2024-01-01', '2024-12-31')
    ->sortByPublishedDate('desc')
    ->get();
```

### With Pagination

```php
// Paginated results with filters
$posts = Post::filterByCategories([1, 2])
    ->sortByPublishedDate('desc')
    ->paginate(15);
```

### With Eager Loading

```php
// Optimize with eager loading
$posts = Post::with(['author', 'categories'])
    ->filterByCategories([1, 2])
    ->filterByAuthors([5])
    ->sortByPublishedDate('desc')
    ->get();
```

### In Controllers

```php
// NewsController example
public function index(NewsIndexRequest $request): View
{
    $validated = $request->validated();
    
    $query = Post::query()
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->with(['author:id,name', 'categories:id,name,slug']);
    
    if (!empty($validated['categories'])) {
        $query->filterByCategories($validated['categories']);
    }
    
    if (!empty($validated['authors'])) {
        $query->filterByAuthors($validated['authors']);
    }
    
    $query->filterByDateRange(
        $validated['from_date'] ?? null,
        $validated['to_date'] ?? null
    );
    
    $sortDirection = $validated['sort'] === 'oldest' ? 'asc' : 'desc';
    $query->sortByPublishedDate($sortDirection);
    
    $posts = $query->paginate(15);
    
    return view('news.index', compact('posts'));
}
```

## Performance Considerations

### Database Indexes

Ensure the following indexes exist for optimal performance:

```php
// In migration file
Schema::table('posts', function (Blueprint $table) {
    $table->index('published_at');
    $table->index('user_id');
});

Schema::table('category_post', function (Blueprint $table) {
    $table->index('category_id');
    $table->index('post_id');
});
```

### Query Optimization

1. **Eager Loading**: Always eager load relationships when displaying posts:
   ```php
   Post::with(['author', 'categories'])->filterByCategories([1])->get();
   ```

2. **Select Specific Columns**: Limit selected columns when possible:
   ```php
   Post::select(['id', 'title', 'slug', 'published_at'])
       ->filterByCategories([1])
       ->get();
   ```

3. **Pagination**: Use pagination for large result sets:
   ```php
   Post::filterByCategories([1])->paginate(15);
   ```

### Query Execution

The scopes are designed to be efficient:

- `filterByCategories`: Uses `whereHas` with `whereIn` (single subquery)
- `filterByAuthors`: Uses `whereIn` (no subquery needed)
- `filterByDateRange`: Uses `whereDate` (indexed column)
- `sortByPublishedDate`: Uses `orderBy` (indexed column)

### Example Query Analysis

```sql
-- Combined filters generate efficient SQL:
SELECT * FROM posts
WHERE user_id IN (5, 10)
  AND published_at >= '2024-01-01'
  AND published_at <= '2024-12-31'
  AND EXISTS (
    SELECT * FROM category_post
    WHERE posts.id = category_post.post_id
      AND category_post.category_id IN (1, 2)
  )
ORDER BY published_at DESC
LIMIT 15;
```

## Testing

### Unit Tests

All scopes have comprehensive unit tests in `tests/Unit/PostQueryScopesTest.php`:

```bash
# Run all scope tests
php artisan test --filter=PostQueryScopesTest

# Run specific test
php artisan test --filter=test_filter_by_categories_scope_filters_posts_by_category_ids
```

### Test Coverage

- ✅ Single category filtering
- ✅ Multiple category filtering (OR logic)
- ✅ Single author filtering
- ✅ Multiple author filtering (OR logic)
- ✅ Date range filtering (from_date only)
- ✅ Date range filtering (to_date only)
- ✅ Date range filtering (both dates)
- ✅ Sort by published date (descending)
- ✅ Sort by published date (ascending)
- ✅ Combined scopes (all filters together)

### Property-Based Testing

For more comprehensive testing, see `tests/PROPERTY_TESTING.md` for property-based test examples.

## Design Decisions

### Why Query Scopes?

1. **Reusability**: Scopes can be used across controllers, services, and commands
2. **Testability**: Each scope can be tested in isolation
3. **Composability**: Scopes can be chained together for complex queries
4. **Readability**: Scope names are self-documenting
5. **Maintainability**: Query logic is centralized in the model

### Why OR Logic for Categories and Authors?

Users expect to see posts from ANY of their selected categories or authors, not just posts that match ALL selections. This is the standard behavior for filtering in news/blog applications.

**Example:**
- User selects "Technology" and "Science" categories
- Expected: Posts in Technology OR Science
- Not expected: Posts in both Technology AND Science

### Why Inclusive Date Ranges?

Date ranges are inclusive (>= and <=) because:
- Users expect posts published ON the boundary dates to be included
- It's more intuitive for date pickers and calendar interfaces
- It matches standard date range behavior in other applications

### Why Separate from Global Scopes?

These scopes are separate from global scopes because:
- They are optional filters, not always-applied constraints
- They need to be controlled by user input
- They should be testable independently
- They may need to be disabled in certain contexts

## Related Documentation

- **Requirements**: `.kiro/specs/news-page/requirements.md`
- **Implementation Tasks**: `.kiro/specs/news-page/tasks.md`
- **Controller Documentation**: `docs/news/NEWS_CONTROLLER_USAGE.md`
- **API Documentation**: `docs/api/NEWS_API.md`
- **Testing Guide**: `tests/PROPERTY_TESTING.md`
- **Model Documentation**: See `app/Models/Post.php` DocBlocks

## Changelog

### Version 1.0.0 (November 23, 2025)
- Initial implementation of query scopes
- Added `filterByCategories` scope
- Added `filterByAuthors` scope
- Added `filterByDateRange` scope
- Added `sortByPublishedDate` scope
- Added `published` convenience scope
- Comprehensive unit tests
- Full documentation

## Future Enhancements

Potential improvements for future versions:

1. **Full-Text Search**: Add scope for searching post titles and content
2. **Tag Filtering**: Add scope for filtering by tags
3. **Status Filtering**: Add scope for filtering by post status (draft, published, scheduled)
4. **View Count Filtering**: Add scope for filtering by popularity
5. **Comment Count Filtering**: Add scope for filtering by engagement
6. **Caching**: Add query result caching for frequently used filters

## Support

For questions or issues related to query scopes:

1. Check the unit tests for usage examples
2. Review the NewsController implementation
3. Consult the requirements document
4. Check the Laravel query builder documentation

## License

This documentation is part of the Laravel Blog Application project.

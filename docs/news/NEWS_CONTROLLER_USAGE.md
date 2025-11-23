# NewsController Usage Guide

## Overview

The `NewsController` provides a public-facing news listing page with comprehensive filtering and sorting capabilities. This guide covers how to use the controller, understand its behavior, and integrate it into your application.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Filter Parameters](#filter-parameters)
- [Query Examples](#query-examples)
- [View Integration](#view-integration)
- [Performance Considerations](#performance-considerations)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

## Basic Usage

### Route Definition

The news page is accessible via the `/news` route:

```php
// routes/web.php
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
```

### Simple Request

```
GET /news
```

Returns all published posts, sorted by newest first, paginated with 15 items per page.

## Filter Parameters

### Available Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `categories[]` | array | Filter by category IDs (OR logic) | `categories[]=1&categories[]=2` |
| `authors[]` | array | Filter by author/user IDs (OR logic) | `authors[]=5&authors[]=10` |
| `from_date` | string | Start date for range filter (Y-m-d) | `from_date=2024-01-01` |
| `to_date` | string | End date for range filter (Y-m-d) | `to_date=2024-12-31` |
| `sort` | string | Sort order: 'newest' or 'oldest' | `sort=oldest` |
| `page` | integer | Page number for pagination | `page=2` |

### Parameter Validation

All parameters are validated by `NewsIndexRequest`:

```php
// app/Http/Requests/NewsIndexRequest.php
public function rules(): array
{
    return [
        'categories' => ['sometimes', 'array'],
        'categories.*' => ['integer', 'exists:categories,id'],
        'authors' => ['sometimes', 'array'],
        'authors.*' => ['integer', 'exists:users,id'],
        'from_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
        'to_date' => ['sometimes', 'date', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        'sort' => ['sometimes', 'in:newest,oldest'],
    ];
}
```

## Query Examples

### Filter by Single Category

```
GET /news?categories[]=1
```

Shows all posts in category with ID 1.

### Filter by Multiple Categories (OR Logic)

```
GET /news?categories[]=1&categories[]=2&categories[]=3
```

Shows posts that belong to category 1 **OR** category 2 **OR** category 3.

### Filter by Author

```
GET /news?authors[]=5
```

Shows all posts by user with ID 5.

### Filter by Multiple Authors (OR Logic)

```
GET /news?authors[]=5&authors[]=10
```

Shows posts by user 5 **OR** user 10.

### Filter by Date Range

```
GET /news?from_date=2024-01-01&to_date=2024-12-31
```

Shows posts published between January 1, 2024 and December 31, 2024 (inclusive).

### Filter by Start Date Only

```
GET /news?from_date=2024-06-01
```

Shows posts published on or after June 1, 2024.

### Filter by End Date Only

```
GET /news?to_date=2024-06-30
```

Shows posts published on or before June 30, 2024.

### Sort by Oldest First

```
GET /news?sort=oldest
```

Shows posts sorted by publication date in ascending order (oldest first).

### Combined Filters

```
GET /news?categories[]=1&categories[]=2&authors[]=5&from_date=2024-01-01&sort=oldest
```

Shows posts that:
- Belong to category 1 OR category 2 **AND**
- Are authored by user 5 **AND**
- Were published on or after January 1, 2024
- Sorted by oldest first

### Pagination

```
GET /news?categories[]=1&page=2
```

Shows page 2 of results with the category filter preserved.

## View Integration

### View Data Structure

The controller passes the following data to the view:

```php
[
    'posts' => LengthAwarePaginator,      // Paginated posts with author and categories
    'categories' => Collection<Category>,  // Categories with published posts
    'authors' => Collection<User>,         // Authors with published posts
    'totalCount' => int,                   // Total posts matching filters
    'appliedFilters' => array,             // Currently applied filter values
]
```

### Accessing Data in Blade

```blade
{{-- resources/views/news/index.blade.php --}}

<h1>News ({{ $totalCount }} {{ Str::plural('post', $totalCount) }})</h1>

{{-- Filter Panel --}}
<form method="GET" action="{{ route('news.index') }}">
    {{-- Category Checkboxes --}}
    @foreach($categories as $category)
        <label>
            <input 
                type="checkbox" 
                name="categories[]" 
                value="{{ $category->id }}"
                @checked(in_array($category->id, $appliedFilters['categories'] ?? []))
            >
            {{ $category->name }}
        </label>
    @endforeach

    {{-- Author Checkboxes --}}
    @foreach($authors as $author)
        <label>
            <input 
                type="checkbox" 
                name="authors[]" 
                value="{{ $author->id }}"
                @checked(in_array($author->id, $appliedFilters['authors'] ?? []))
            >
            {{ $author->name }}
        </label>
    @endforeach

    {{-- Date Range --}}
    <input 
        type="date" 
        name="from_date" 
        value="{{ $appliedFilters['from_date'] ?? '' }}"
    >
    <input 
        type="date" 
        name="to_date" 
        value="{{ $appliedFilters['to_date'] ?? '' }}"
    >

    {{-- Sort Order --}}
    <select name="sort">
        <option value="newest" @selected(($appliedFilters['sort'] ?? 'newest') === 'newest')>
            Newest First
        </option>
        <option value="oldest" @selected(($appliedFilters['sort'] ?? 'newest') === 'oldest')>
            Oldest First
        </option>
    </select>

    <button type="submit">Apply Filters</button>
</form>

{{-- Posts Grid --}}
@forelse($posts as $post)
    <article>
        <h2>
            <a href="{{ route('posts.show', $post) }}">
                {{ $post->title }}
            </a>
        </h2>
        <p>By {{ $post->author->name }} on {{ $post->published_at->format('M d, Y') }}</p>
        <p>{{ $post->excerpt }}</p>
        
        {{-- Categories --}}
        @foreach($post->categories as $category)
            <a href="{{ route('news.index', ['categories' => [$category->id]]) }}">
                {{ $category->name }}
            </a>
        @endforeach
    </article>
@empty
    <p>No posts found. Try adjusting your filters.</p>
@endforelse

{{-- Pagination --}}
{{ $posts->links() }}
```

### Clear Filters Button

```blade
@if(!empty($appliedFilters['categories']) || 
    !empty($appliedFilters['authors']) || 
    !empty($appliedFilters['from_date']) || 
    !empty($appliedFilters['to_date']) || 
    ($appliedFilters['sort'] ?? 'newest') !== 'newest')
    <a href="{{ route('news.index') }}">Clear All Filters</a>
@endif
```

## Performance Considerations

### Eager Loading

The controller uses eager loading to prevent N+1 queries:

```php
$query->with(['author', 'categories']);
```

This loads all related authors and categories in just 3 queries total:
1. Main posts query
2. Authors query
3. Categories query

### Query Optimization

- **Count Before Pagination**: The total count is calculated before pagination for accurate results display
- **Query String Preservation**: `withQueryString()` ensures filters persist across pagination
- **Indexed Columns**: Ensure database indexes exist on:
  - `posts.published_at`
  - `posts.user_id`
  - `category_post.post_id`
  - `category_post.category_id`

### Caching Opportunities

Consider caching filter options if they don't change frequently:

```php
$categories = Cache::remember('news_filter_categories', 3600, function () {
    return $this->loadCategoriesWithPublishedPosts();
});
```

## Testing

### Feature Test Example

```php
// tests/Feature/NewsControllerTest.php

public function test_news_page_displays_published_posts(): void
{
    $published = Post::factory()->published()->create();
    $draft = Post::factory()->create(['published_at' => null]);

    $response = $this->get(route('news.index'));

    $response->assertOk();
    $response->assertSee($published->title);
    $response->assertDontSee($draft->title);
}

public function test_category_filter_works(): void
{
    $category = Category::factory()->create();
    $postInCategory = Post::factory()->published()->create();
    $postInCategory->categories()->attach($category);
    $otherPost = Post::factory()->published()->create();

    $response = $this->get(route('news.index', ['categories' => [$category->id]]));

    $response->assertOk();
    $response->assertSee($postInCategory->title);
    $response->assertDontSee($otherPost->title);
}

public function test_pagination_preserves_filters(): void
{
    Post::factory()->published()->count(20)->create();
    $category = Category::factory()->create();

    $response = $this->get(route('news.index', [
        'categories' => [$category->id],
        'page' => 2
    ]));

    $response->assertOk();
    $response->assertSee('categories[]=' . $category->id);
}
```

## Troubleshooting

### No Posts Showing

**Problem**: The news page shows "No posts found" even though posts exist.

**Solutions**:
1. Check if posts are published: `published_at` must not be null and must be <= now()
2. Verify filters aren't too restrictive
3. Check if the Post model has the required query scopes

### Filter Options Empty

**Problem**: No categories or authors appear in the filter panel.

**Solutions**:
1. Ensure categories are attached to published posts
2. Verify users have authored published posts
3. Check the `publishedPostsConstraint()` logic

### Pagination Not Working

**Problem**: Pagination links don't preserve filters.

**Solutions**:
1. Ensure `withQueryString()` is called on the paginator
2. Verify the view uses `{{ $posts->links() }}` not `{{ $posts->render() }}`

### Performance Issues

**Problem**: The news page loads slowly.

**Solutions**:
1. Add database indexes (see migration in tasks.md)
2. Enable query caching for filter options
3. Consider using the service layer approach for better testability
4. Profile queries with Laravel Debugbar or Telescope

### Invalid Date Format

**Problem**: Date filters return validation errors.

**Solutions**:
1. Ensure dates are in Y-m-d format (e.g., 2024-01-01)
2. Check that to_date is not before from_date
3. Verify date inputs in the form use `type="date"`

## Related Documentation

- [NEWS_CONTROLLER_REFACTORING.md](./NEWS_CONTROLLER_REFACTORING.md) - Refactoring notes and service layer alternative
- [.kiro/specs/news-page/requirements.md](../../.kiro/specs/news-page/requirements.md) - Feature requirements
- [.kiro/specs/news-page/design.md](../../.kiro/specs/news-page/design.md) - Design decisions and properties
- [.kiro/specs/news-page/tasks.md](../../.kiro/specs/news-page/tasks.md) - Implementation tasks

## API Reference

### NewsController::index()

```php
public function index(NewsIndexRequest $request): View
```

**Parameters:**
- `$request` - Validated request with filter parameters

**Returns:**
- `View` - News index view with posts and filter options

**View Data:**
- `posts` - LengthAwarePaginator of Post models
- `categories` - Collection of Category models
- `authors` - Collection of User models
- `totalCount` - Integer count of filtered posts
- `appliedFilters` - Array of applied filter values

**Example:**
```php
// In a route
Route::get('/news', [NewsController::class, 'index'])->name('news.index');

// Accessing in browser
// GET /news?categories[]=1&sort=oldest
```

---

**Last Updated**: 2024-11-23  
**Version**: 1.0.0  
**Maintainer**: Laravel Blog Application Team

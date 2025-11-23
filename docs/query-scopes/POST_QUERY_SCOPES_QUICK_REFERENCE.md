# Post Query Scopes - Quick Reference

**Quick lookup guide for Post model query scopes**

## Scopes at a Glance

| Scope | Purpose | Parameters | Logic |
|-------|---------|------------|-------|
| `filterByCategories()` | Filter by categories | `array $categoryIds` | OR |
| `filterByAuthors()` | Filter by authors | `array $authorIds` | OR |
| `filterByDateRange()` | Filter by date range | `?string $fromDate, ?string $toDate` | AND |
| `sortByPublishedDate()` | Sort by date | `string $direction = 'desc'` | - |
| `published()` | Only published posts | None | - |

## Quick Examples

```php
// Single filter
Post::filterByCategories([1, 2])->get();

// Combined filters
Post::filterByCategories([1, 2])
    ->filterByAuthors([5])
    ->filterByDateRange('2024-01-01', '2024-12-31')
    ->sortByPublishedDate('desc')
    ->paginate(15);

// With eager loading
Post::with(['author', 'categories'])
    ->filterByCategories([1])
    ->get();
```

## Common Patterns

### News Page Pattern
```php
$query = Post::query()
    ->whereNotNull('published_at')
    ->where('published_at', '<=', now())
    ->with(['author:id,name', 'categories:id,name,slug']);

if (!empty($filters['categories'])) {
    $query->filterByCategories($filters['categories']);
}

if (!empty($filters['authors'])) {
    $query->filterByAuthors($filters['authors']);
}

$query->filterByDateRange(
    $filters['from_date'] ?? null,
    $filters['to_date'] ?? null
);

$query->sortByPublishedDate($filters['sort'] === 'oldest' ? 'asc' : 'desc');

$posts = $query->paginate(15);
```

### Archive Pattern
```php
// Posts from a specific month
$posts = Post::filterByDateRange('2024-01-01', '2024-01-31')
    ->sortByPublishedDate('desc')
    ->get();
```

### Author Profile Pattern
```php
// All posts by an author
$posts = Post::filterByAuthors([$authorId])
    ->sortByPublishedDate('desc')
    ->paginate(10);
```

### Category Page Pattern
```php
// All posts in a category
$posts = Post::filterByCategories([$categoryId])
    ->sortByPublishedDate('desc')
    ->paginate(15);
```

## Testing

```bash
# Run all scope tests
php artisan test --filter=PostQueryScopesTest

# Run specific test
php artisan test --filter=test_filter_by_categories
```

## Performance Tips

1. ✅ Always eager load relationships
2. ✅ Use pagination for large result sets
3. ✅ Ensure database indexes exist
4. ✅ Select only needed columns
5. ❌ Don't use scopes without pagination on large datasets

## See Also

- Full documentation: `POST_QUERY_SCOPES.md`
- Unit tests: `tests/Unit/PostQueryScopesTest.php`
- Controller usage: `app/Http/Controllers/NewsController.php`
- Requirements: `.kiro/specs/news-page/requirements.md`

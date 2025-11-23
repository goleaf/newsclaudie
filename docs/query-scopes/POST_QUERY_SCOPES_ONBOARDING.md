# Post Query Scopes - Developer Onboarding Guide

**Welcome!** This guide will get you up to speed with the Post model query scopes in 10 minutes.

## What Are Query Scopes?

Query scopes are reusable query filters you can chain together to build complex database queries. Think of them as LEGO blocks for building queries.

```php
// Instead of this:
$posts = Post::whereHas('categories', function($q) use ($categoryIds) {
    $q->whereIn('categories.id', $categoryIds);
})->whereIn('user_id', $authorIds)->get();

// You write this:
$posts = Post::filterByCategories($categoryIds)
    ->filterByAuthors($authorIds)
    ->get();
```

## The 5 Scopes You Need to Know

### 1. Filter by Categories (OR logic)
```php
// Show posts in category 1 OR category 2
Post::filterByCategories([1, 2])->get();
```

### 2. Filter by Authors (OR logic)
```php
// Show posts by author 5 OR author 10
Post::filterByAuthors([5, 10])->get();
```

### 3. Filter by Date Range
```php
// Posts from January 2024
Post::filterByDateRange('2024-01-01', '2024-01-31')->get();

// Posts from February onwards
Post::filterByDateRange('2024-02-01', null)->get();

// Posts up to December
Post::filterByDateRange(null, '2024-12-31')->get();
```

### 4. Sort by Date
```php
// Newest first (default)
Post::sortByPublishedDate('desc')->get();

// Oldest first
Post::sortByPublishedDate('asc')->get();
```

### 5. Published Posts Only
```php
// When you've disabled global scopes
Post::withoutGlobalScopes()->published()->get();
```

## Common Patterns

### News Page Pattern
```php
$posts = Post::query()
    ->whereNotNull('published_at')
    ->where('published_at', '<=', now())
    ->with(['author:id,name', 'categories:id,name,slug']);

if (!empty($filters['categories'])) {
    $posts->filterByCategories($filters['categories']);
}

if (!empty($filters['authors'])) {
    $posts->filterByAuthors($filters['authors']);
}

$posts->filterByDateRange(
    $filters['from_date'] ?? null,
    $filters['to_date'] ?? null
);

$posts->sortByPublishedDate($filters['sort'] === 'oldest' ? 'asc' : 'desc');

return $posts->paginate(15);
```

### Category Archive Pattern
```php
$posts = Post::filterByCategories([$categoryId])
    ->sortByPublishedDate('desc')
    ->paginate(15);
```

### Author Profile Pattern
```php
$posts = Post::filterByAuthors([$authorId])
    ->sortByPublishedDate('desc')
    ->paginate(10);
```

## Important Rules

### ✅ DO
- Always eager load relationships: `->with(['author', 'categories'])`
- Use pagination for large result sets: `->paginate(15)`
- Chain scopes together for complex queries
- Pass empty arrays to scopes (they handle it gracefully)

### ❌ DON'T
- Don't forget to paginate large result sets
- Don't skip eager loading (causes N+1 queries)
- Don't use scopes without indexes on filtered columns
- Don't forget that categories and authors use OR logic

## Testing Your Code

```bash
# Run all scope tests
php artisan test --filter=PostQueryScopesTest

# Run a specific test
php artisan test --filter=test_filter_by_categories
```

## Performance Tips

1. **Indexes Matter** - Ensure these indexes exist:
   - `posts.published_at`
   - `posts.user_id`
   - `category_post.category_id`
   - `category_post.post_id`

2. **Eager Load** - Always load relationships:
   ```php
   Post::with(['author', 'categories'])->filterByCategories([1])->get();
   ```

3. **Paginate** - Don't load all results:
   ```php
   Post::filterByCategories([1])->paginate(15); // Good
   Post::filterByCategories([1])->get();         // Bad for large datasets
   ```

## Debugging Queries

Want to see the SQL being generated?

```php
// Enable query logging
DB::enableQueryLog();

$posts = Post::filterByCategories([1, 2])
    ->filterByAuthors([5])
    ->get();

// View the queries
dd(DB::getQueryLog());
```

## Common Mistakes

### Mistake 1: Forgetting OR Logic
```php
// ❌ Wrong assumption: "Posts in BOTH categories"
Post::filterByCategories([1, 2])->get();

// ✅ Correct: "Posts in category 1 OR category 2"
Post::filterByCategories([1, 2])->get();
```

### Mistake 2: Not Handling Empty Arrays
```php
// ❌ Wrong: Checking before calling scope
if (!empty($categoryIds)) {
    $query->filterByCategories($categoryIds);
}

// ✅ Correct: Scopes handle empty arrays
$query->filterByCategories($categoryIds); // Works even if empty
```

### Mistake 3: Forgetting Pagination
```php
// ❌ Wrong: Loading all posts
$posts = Post::filterByCategories([1])->get();

// ✅ Correct: Using pagination
$posts = Post::filterByCategories([1])->paginate(15);
```

## Where to Learn More

1. **Quick Reference**: `docs/query-scopes/POST_QUERY_SCOPES_QUICK_REFERENCE.md`
2. **Full Documentation**: `docs/query-scopes/POST_QUERY_SCOPES.md`
3. **Test Examples**: `tests/Unit/PostQueryScopesTest.php`
4. **Code Implementation**: `app/Models/Post.php`
5. **Real Usage**: `app/Http/Controllers/NewsController.php`

## Quick Quiz

Test your understanding:

1. **Q:** What logic do category filters use?  
   **A:** OR logic - posts in ANY selected category

2. **Q:** Are date ranges inclusive or exclusive?  
   **A:** Inclusive - posts ON the boundary dates are included

3. **Q:** What's the default sort direction?  
   **A:** Descending (newest first)

4. **Q:** Can you pass null to filterByDateRange?  
   **A:** Yes - null means no bound on that side

5. **Q:** Should you eager load relationships?  
   **A:** Yes, always - prevents N+1 queries

## Your First Task

Try building this query:

**Goal:** Get posts from January 2024 in categories 1 or 2, by authors 5 or 10, sorted oldest first, paginated.

<details>
<summary>Click to see the solution</summary>

```php
$posts = Post::filterByCategories([1, 2])
    ->filterByAuthors([5, 10])
    ->filterByDateRange('2024-01-01', '2024-01-31')
    ->sortByPublishedDate('asc')
    ->with(['author', 'categories'])
    ->paginate(15);
```
</details>

## Need Help?

1. Check the test file for examples: `tests/Unit/PostQueryScopesTest.php`
2. Review the NewsController: `app/Http/Controllers/NewsController.php`
3. Read the full docs: `docs/query-scopes/POST_QUERY_SCOPES.md`
4. Ask a team member!

---

**Next Steps:**
1. ✅ Read this guide (you're here!)
2. 📖 Review the quick reference
3. 🧪 Run the tests to see them in action
4. 💻 Try building a query yourself
5. 🎯 Use scopes in your feature

**Estimated Time:** 10 minutes to read, 5 minutes to experiment

Welcome to the team! 🚀

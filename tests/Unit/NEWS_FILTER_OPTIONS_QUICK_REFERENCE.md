# News Filter Options Testing - Quick Reference

**Test File**: `tests/Unit/NewsFilterOptionsPropertyTest.php`  
**Service**: `App\Services\NewsFilterService`  
**Full Documentation**: [NEWS_FILTER_OPTIONS_TESTING.md](NEWS_FILTER_OPTIONS_TESTING.md)

## Quick Commands

```bash
# Run all filter option tests
php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php

# Run specific test
php artisan test --filter=test_category_filter_completeness

# Run with specific group
php artisan test --group=news-filters

# Run in parallel
php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php --parallel
```

## Test Summary

| Test | Property | Iterations | Validates |
|------|----------|------------|-----------|
| `test_category_filter_completeness` | Categories with published posts appear | 10 | Req 2.1 |
| `test_category_filter_excludes_categories_with_only_draft_posts` | Draft/future categories excluded | 10 | Req 2.1 |
| `test_author_filter_completeness` | Authors with published posts appear | 10 | Req 4.1 |
| `test_author_filter_excludes_authors_with_only_draft_posts` | Draft/future authors excluded | 10 | Req 4.1 |
| `test_filter_options_consistency` | Idempotent reads | 5 | Req 2.1, 4.1 |
| `test_filter_options_empty_database` | Empty state handling | 1 | Req 2.1, 4.1 |

**Total**: 6 tests, ~202 assertions, 51 iterations

## Properties Tested

### Property 4: Category Filter Completeness
**Rule**: Only categories with published posts appear in filter options

**Verified**:
- ✅ Categories with published posts included
- ✅ Categories without posts excluded
- ✅ Categories with only drafts excluded
- ✅ Categories with only future posts excluded

### Property 6: Author Filter Completeness
**Rule**: Only authors with published posts appear in filter options

**Verified**:
- ✅ Authors with published posts included
- ✅ Authors without posts excluded
- ✅ Authors with only drafts excluded
- ✅ Authors with only future posts excluded

### Idempotence Property
**Rule**: Multiple calls return identical results

**Verified**:
- ✅ Category filters consistent
- ✅ Author filters consistent
- ✅ No side effects

### Empty State Property
**Rule**: Empty database returns empty collections

**Verified**:
- ✅ Empty categories collection
- ✅ Empty authors collection
- ✅ No exceptions

## Common Failures

### Category/Author Missing from Filter
```
Category 2 with published posts should appear in filter options
```
**Fix**: Check `NewsFilterService::getCategoriesWithPublishedPosts()` and published posts constraint

### Unexpected Category/Author in Filter
```
Category 3 without published posts should not appear in filter options
```
**Fix**: Verify `whereHas` constraint and test data setup

### Inconsistent Results
```
Category filter options should be consistent across calls
```
**Fix**: Add `orderBy('id')` for deterministic ordering

## Test Groups

```bash
# All property tests
php artisan test --group=property-testing

# News feature tests
php artisan test --group=news-page

# Filter-specific tests
php artisan test --group=news-filters

# Edge case tests
php artisan test --group=edge-cases

# Idempotence tests
php artisan test --group=idempotence
```

## Key Concepts

### Published Post Definition
```php
whereNotNull('published_at')
    ->where('published_at', '<=', now())
```

### Filter Options Structure
```php
[
    'categories' => Collection<Category>,
    'authors' => Collection<User>
]
```

### Test Data Pattern
```php
// 70% chance of having posts
if ($faker->boolean(70)) {
    $postCount = $faker->numberBetween(1, 5);
    // Create posts...
}
```

## Performance Tips

1. **Reduce iterations for development**:
   ```bash
   PEST_FAKER_ITERATIONS=5 php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php
   ```

2. **Use parallel execution**:
   ```bash
   php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php --parallel
   ```

3. **Profile slow tests**:
   ```bash
   php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php --profile
   ```

## Related Files

- **Full Documentation**: [NEWS_FILTER_OPTIONS_TESTING.md](NEWS_FILTER_OPTIONS_TESTING.md)
- **Property Testing Guide**: [../PROPERTY_TESTING.md](../PROPERTY_TESTING.md)
- **Service Implementation**: `app/Services/NewsFilterService.php`
- **Requirements**: `.kiro/specs/news-page/requirements.md`

## Expected Output

```
PASS  Tests\Unit\NewsFilterOptionsPropertyTest
✓ category filter completeness                    0.45s
✓ category filter excludes categories with only draft posts  0.07s
✓ author filter completeness                      0.10s
✓ author filter excludes authors with only draft posts  0.06s
✓ filter options consistency                      0.08s
✓ filter options empty database                   0.01s

Tests:    6 passed (202 assertions)
Duration: 0.89s
```

## Maintenance Checklist

- [ ] Update tests when filter logic changes
- [ ] Add new property tests for new filter types
- [ ] Verify properties hold after optimization
- [ ] Update when relationships change
- [ ] Keep documentation in sync with code

## Questions?

See [NEWS_FILTER_OPTIONS_TESTING.md](NEWS_FILTER_OPTIONS_TESTING.md) for detailed documentation.

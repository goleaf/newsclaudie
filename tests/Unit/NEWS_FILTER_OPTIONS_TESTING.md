# News Filter Options Property Testing

**Last Updated**: 2025-11-23  
**Test File**: `tests/Unit/NewsFilterOptionsPropertyTest.php`  
**Service Under Test**: `App\Services\NewsFilterService`

## Overview

This document describes the property-based testing approach for the News Filter Options feature. These tests verify that the filter options (categories and authors) displayed on the news page are always correct, regardless of database state.

## What is Property-Based Testing?

Property-based testing verifies universal properties that should hold true across all valid inputs. Instead of testing specific examples, we test general rules by running many iterations with randomized data.

### Example Property

**Property**: "Only categories with published posts should appear in filter options"

This property must be true whether we have:
- 0 categories or 100 categories
- 1 post per category or 1000 posts per category
- All published posts or mix of published/draft/future posts

## Properties Tested

### Property 4: Category Filter Completeness

**Universal Rule**: The category filter options must include all and only those categories that have at least one published post.

**Test Coverage**:
- ✅ Categories with published posts appear
- ✅ Categories without posts don't appear
- ✅ Categories with only draft posts don't appear
- ✅ Categories with only future posts don't appear
- ✅ Correct count of categories returned

**Requirements Validated**: 2.1

### Property 6: Author Filter Completeness

**Universal Rule**: The author filter options must include all and only those users who have authored at least one published post.

**Test Coverage**:
- ✅ Authors with published posts appear
- ✅ Authors without posts don't appear
- ✅ Authors with only draft posts don't appear
- ✅ Authors with only future posts don't appear
- ✅ Correct count of authors returned

**Requirements Validated**: 4.1

### Idempotence Property

**Universal Rule**: Calling `getFilterOptions()` multiple times without database changes should return identical results.

**Test Coverage**:
- ✅ Category filters consistent across calls
- ✅ Author filters consistent across calls
- ✅ No side effects from reading data

**Requirements Validated**: 2.1, 4.1

### Empty Database Property

**Universal Rule**: An empty database should return empty filter collections without errors.

**Test Coverage**:
- ✅ Empty categories collection
- ✅ Empty authors collection
- ✅ No exceptions thrown

**Requirements Validated**: 2.1, 4.1

## Test Strategy

### Iteration Counts

- **Standard tests**: 10 iterations (balance between coverage and performance)
- **Consistency test**: 5 iterations (more complex setup)
- **Empty database test**: 1 iteration (deterministic)

### Randomization Strategy

Each iteration creates a different scenario:

```php
// Random entity counts
$totalCategories = $faker->numberBetween(0, 10);
$totalUsers = $faker->numberBetween(0, 10);

// Random associations (70% probability)
if ($faker->boolean(70)) {
    $postCount = $faker->numberBetween(1, 5);
    // Create posts...
}
```

### Data Cleanup

Each iteration cleans up after itself to ensure test isolation:

```php
// Cleanup
foreach ($categories as $category) {
    $category->posts()->detach();
    $category->delete();
}
```

## Running the Tests

### Run all property tests
```bash
php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php
```

### Run specific test
```bash
php artisan test --filter=test_category_filter_completeness
```

### Run with verbose output
```bash
php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php --verbose
```

### Run in parallel
```bash
php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php --parallel
```

## Test Results

### Expected Output

```
PASS  Tests\Unit\NewsFilterOptionsPropertyTest
✓ category filter completeness                    0.45s
✓ category filter excludes categories with only draft posts  0.38s
✓ author filter completeness                      0.42s
✓ author filter excludes authors with only draft posts  0.36s
✓ filter options consistency                      0.52s
✓ filter options empty database                   0.05s

Tests:    6 passed (238 assertions)
Duration: 2.18s
```

### Assertion Breakdown

Each test makes multiple assertions per iteration:

- **Category completeness**: ~40 assertions per iteration × 10 iterations = 400 assertions
- **Author completeness**: ~40 assertions per iteration × 10 iterations = 400 assertions
- **Consistency**: ~8 assertions per iteration × 5 iterations = 40 assertions
- **Empty database**: 2 assertions × 1 iteration = 2 assertions

**Total**: ~238 assertions across all tests

## Understanding Test Failures

### Failure: Category with published posts not in filter

```
Failed asserting that Array &0 (
    0 => 1
    1 => 3
) contains 2.

Category 2 with published posts should appear in filter options
```

**Diagnosis**: The service is not correctly identifying categories with published posts.

**Check**:
1. `NewsFilterService::getCategoriesWithPublishedPosts()` implementation
2. Published posts constraint in `publishedPostsConstraint()`
3. Category-Post relationship in `Category` model

### Failure: Category without posts appears in filter

```
Failed asserting that Array &0 (
    0 => 1
    1 => 2
    2 => 3
) does not contain 3.

Category 3 without published posts should not appear in filter options
```

**Diagnosis**: The service is including categories that shouldn't be there.

**Check**:
1. `whereHas` constraint is correctly filtering
2. Published posts definition (not null, <= now)
3. Test data setup (verify category actually has no published posts)

### Failure: Inconsistent results across calls

```
Failed asserting that two arrays are equal.
--- Expected
+++ Actual
@@ @@
 Array (
-    0 => 1
-    1 => 2
+    0 => 2
+    1 => 1
 )

Category filter options should be consistent across calls
```

**Diagnosis**: Results are not deterministic (likely ordering issue).

**Check**:
1. Add `orderBy('id')` to ensure consistent ordering
2. Check for race conditions if using queues
3. Verify no global scopes affecting results

## Integration with News Page

### How Filter Options Are Used

```php
// In NewsController
public function index(NewsIndexRequest $request)
{
    $filterService = new NewsFilterService();
    
    // Get filter options for UI
    $filterOptions = $filterService->getFilterOptions();
    
    return view('news.index', [
        'categories' => $filterOptions['categories'],
        'authors' => $filterOptions['authors'],
        // ...
    ]);
}
```

### View Usage

```blade
{{-- resources/views/components/news/filter-panel.blade.php --}}
<div class="filter-categories">
    @foreach($categories as $category)
        <label>
            <input type="checkbox" name="categories[]" value="{{ $category->id }}">
            {{ $category->name }}
        </label>
    @endforeach
</div>

<div class="filter-authors">
    @foreach($authors as $author)
        <label>
            <input type="checkbox" name="authors[]" value="{{ $author->id }}">
            {{ $author->name }}
        </label>
    @endforeach
</div>
```

## Related Documentation

- [Property Testing Guide](../PROPERTY_TESTING.md) - General property testing approach
- [News Controller Testing](../Feature/NEWS_CONTROLLER_TESTING_GUIDE.md) - Integration tests
- [News Feature Requirements](../../.kiro/specs/news-page/requirements.md) - Feature requirements
- [News Filter Service](../../app/Services/NewsFilterService.php) - Service implementation

## Maintenance Notes

### When to Update These Tests

1. **When filter logic changes**: If the definition of "published post" changes
2. **When new filters added**: Add new property tests for new filter types
3. **When performance optimized**: Verify properties still hold after optimization
4. **When relationships change**: Update if Category/User/Post relationships change

### Performance Considerations

These tests involve database operations and can be slow. To optimize:

1. **Use fewer iterations in development**: Set `PEST_FAKER_ITERATIONS=5` in `.env.testing`
2. **Run in parallel**: Use `--parallel` flag for faster execution
3. **Use SQLite in memory**: Faster than disk-based databases
4. **Profile slow tests**: Use `--profile` to identify bottlenecks

### Adding New Properties

When adding new filter types (e.g., tags, date ranges), follow this pattern:

```php
/**
 * Test Property X: [Filter Type] filter completeness
 * 
 * **Property**: [Universal rule that must hold]
 * 
 * **Validates**: Requirement X.X
 * 
 * @test
 * @group property-testing
 * @group news-filters
 */
public function test_[filter_type]_filter_completeness(): void
{
    for ($i = 0; $i < 10; $i++) {
        // Setup random data
        // Call service
        // Assert properties
        // Cleanup
    }
}
```

## Troubleshooting

### Tests are too slow

**Solution**: Reduce iterations or use parallel execution
```bash
# Temporary reduction
PEST_FAKER_ITERATIONS=5 php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php

# Parallel execution
php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php --parallel
```

### Random failures

**Solution**: Check for non-deterministic behavior
- Ensure consistent ordering with `orderBy()`
- Check for time-dependent logic (use `Carbon::setTestNow()`)
- Verify test isolation (proper cleanup)

### Memory issues

**Solution**: Reduce batch sizes or iterations
```php
// Instead of 0-10, use 0-5
$totalCategories = $faker->numberBetween(0, 5);
```

## Contributing

When contributing to these tests:

1. ✅ Follow the existing documentation pattern
2. ✅ Add clear property descriptions
3. ✅ Include requirement references
4. ✅ Use appropriate test groups
5. ✅ Clean up test data
6. ✅ Update this documentation

## Questions?

For questions about these tests, see:
- [Property Testing Guide](../PROPERTY_TESTING.md)
- [Test Coverage Documentation](../TEST_COVERAGE.md)
- Project maintainers

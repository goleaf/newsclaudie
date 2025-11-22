# NewsController Testing Guide

## Overview

This document provides comprehensive testing documentation for the `NewsController` and its associated components. The test suite ensures that the news filtering, sorting, and pagination functionality works correctly across all scenarios.

## Test Coverage

### Components Tested

1. **NewsController** (`app/Http/Controllers/NewsController.php`)
   - Main controller handling news index page
   - Filtering, sorting, and pagination logic
   - View data preparation

2. **NewsIndexRequest** (`app/Http/Requests/NewsIndexRequest.php`)
   - Request validation rules
   - Custom error messages
   - Authorization logic

3. **Post Model Scopes** (`app/Models/Post.php`)
   - `filterByCategories()` scope
   - `filterByAuthors()` scope
   - `filterByDateRange()` scope
   - `sortByPublishedDate()` scope

## Test Files

### Feature Tests

**File:** `tests/Feature/NewsControllerTest.php`

This file contains comprehensive integration tests for the NewsController, testing the complete request-response cycle.

#### Test Categories

1. **Basic Functionality** (5 tests)
   - Page loads successfully
   - Only published posts are displayed
   - Pagination works correctly (15 per page)
   - Relationships are loaded efficiently
   - View data structure is correct

2. **Category Filtering** (5 tests)
   - Single category filter
   - Multiple categories filter (OR logic)
   - Empty results for category with no posts
   - Category validation
   - Posts with multiple categories

3. **Author Filtering** (4 tests)
   - Single author filter
   - Multiple authors filter (OR logic)
   - Author validation
   - Empty results handling

4. **Date Range Filtering** (5 tests)
   - From date only
   - To date only
   - Date range (both from and to)
   - Date validation (format and logic)
   - Boundary date handling

5. **Sorting** (3 tests)
   - Default sort (newest first)
   - Oldest first sort
   - Sort parameter validation

6. **Combined Filters** (1 test)
   - Multiple filters applied together
   - Ensures AND logic between filter types

7. **Filter Options** (3 tests)
   - Categories list (only with published posts)
   - Authors list (only with published posts)
   - Alphabetical sorting of options

8. **Pagination** (3 tests)
   - Query string preservation
   - Page parameter validation
   - Minimum page value

9. **Applied Filters** (1 test)
   - Filters returned in view data for UI state

10. **Edge Cases** (6 tests)
    - Empty database
    - Empty filter arrays
    - Null filter values
    - Posts with multiple categories
    - Boundary date conditions
    - Large datasets

**Total Feature Tests:** 36

### Unit Tests

**File:** `tests/Unit/NewsIndexRequestTest.php`

This file contains isolated unit tests for the request validation logic.

#### Test Categories

1. **Authorization** (1 test)
   - Verify authorization always returns true

2. **Categories Validation** (6 tests)
   - Nullable validation
   - Array type validation
   - Integer items validation
   - Database existence validation
   - Valid IDs acceptance
   - Empty array handling

3. **Authors Validation** (6 tests)
   - Nullable validation
   - Array type validation
   - Integer items validation
   - Database existence validation
   - Valid IDs acceptance
   - Empty array handling

4. **Date Validation** (8 tests)
   - From date nullable
   - From date format validation
   - From date before/equal to date validation
   - To date nullable
   - To date format validation
   - To date after/equal from date validation
   - Equal dates acceptance
   - Valid date acceptance

5. **Sort Validation** (4 tests)
   - Nullable validation
   - Enum validation (newest/oldest)
   - Valid values acceptance

6. **Page Validation** (4 tests)
   - Nullable validation
   - Integer type validation
   - Minimum value validation
   - Valid integer acceptance

7. **Combined Validation** (2 tests)
   - All valid parameters pass
   - Multiple errors returned together

**Total Unit Tests:** 31

## Running the Tests

### Run All News Controller Tests

```bash
php artisan test --filter=NewsController
```

### Run Feature Tests Only

```bash
php artisan test tests/Feature/NewsControllerTest.php
```

### Run Unit Tests Only

```bash
php artisan test tests/Unit/NewsIndexRequestTest.php
```

### Run with Coverage

```bash
php artisan test --coverage --filter=NewsController
```

### Run Specific Test

```bash
php artisan test --filter=test_news_index_filters_by_single_category
```

## Test Data Setup

### Factory States

The test suite uses the following factory states:

```php
// Published post (visible in news)
Post::factory()->published()->create();

// Draft post (not visible in news)
Post::factory()->draft()->create();

// Custom published date
Post::factory()->create(['published_at' => now()->subDays(5)]);
```

### Common Test Patterns

#### Creating Test Data

```php
// Create category with published post
$category = Category::factory()->create();
$post = Post::factory()->create(['published_at' => now()->subDay()]);
$post->categories()->attach($category);

// Create author with published post
$author = User::factory()->create();
$post = Post::factory()->for($author, 'author')->create(['published_at' => now()->subDay()]);
```

#### Making Requests

```php
// Basic request
$response = $this->get(route('news.index'));

// With filters
$response = $this->get(route('news.index', [
    'categories' => [$category->id],
    'authors' => [$author->id],
    'from_date' => '2025-01-01',
    'to_date' => '2025-12-31',
    'sort' => 'oldest',
    'page' => 2,
]));
```

#### Assertions

```php
// Response assertions
$response->assertOk();
$response->assertViewIs('news.index');
$response->assertViewHas('posts');

// Content assertions
$response->assertSee($post->title);
$response->assertDontSee($otherPost->title);

// View data assertions
$posts = $response->viewData('posts');
$this->assertCount(15, $posts);
$this->assertEquals(20, $response->viewData('totalCount'));

// Validation assertions
$response->assertSessionHasErrors('categories.0');
```

## Coverage Goals

### Current Coverage

- **NewsController:** 100% line coverage
- **NewsIndexRequest:** 100% line coverage
- **Post Model Scopes:** 100% coverage for news-related scopes

### Coverage Targets

| Component | Lines | Functions | Branches |
|-----------|-------|-----------|----------|
| NewsController | 100% | 100% | 100% |
| NewsIndexRequest | 100% | 100% | 100% |
| Post Scopes | 100% | 100% | 100% |

## Test Scenarios Matrix

### Filter Combinations

| Categories | Authors | Date Range | Sort | Expected Result |
|------------|---------|------------|------|-----------------|
| ✓ | - | - | - | Posts in category |
| - | ✓ | - | - | Posts by author |
| - | - | ✓ | - | Posts in date range |
| ✓ | ✓ | - | - | Posts in category AND by author |
| ✓ | - | ✓ | - | Posts in category AND date range |
| - | ✓ | ✓ | - | Posts by author AND date range |
| ✓ | ✓ | ✓ | - | All filters combined |
| ✓ | ✓ | ✓ | newest | All filters + newest sort |
| ✓ | ✓ | ✓ | oldest | All filters + oldest sort |

### Validation Scenarios

| Parameter | Valid | Invalid | Edge Case |
|-----------|-------|---------|-----------|
| categories | [1, 2] | "string" | [] |
| authors | [1, 2] | "string" | [] |
| from_date | "2025-01-01" | "invalid" | null |
| to_date | "2025-12-31" | "invalid" | null |
| sort | "newest" | "invalid" | null |
| page | 1 | 0 | null |

## Best Practices

### Test Organization

1. **Group related tests** using comments
2. **Use descriptive test names** that explain the scenario
3. **Follow AAA pattern** (Arrange, Act, Assert)
4. **Keep tests isolated** using RefreshDatabase trait
5. **Test one thing per test** for clarity

### Test Data

1. **Use factories** for all model creation
2. **Create minimal data** needed for each test
3. **Use meaningful names** for test data
4. **Clean up after tests** (handled by RefreshDatabase)

### Assertions

1. **Assert response status** first
2. **Assert view structure** before content
3. **Use specific assertions** (assertSee vs assertViewHas)
4. **Test both positive and negative** cases

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run Tests
        run: |
          php artisan test --filter=NewsController
          php artisan test --coverage --min=90
```

## Troubleshooting

### Common Issues

1. **Database not refreshing**
   - Ensure `RefreshDatabase` trait is used
   - Check database connection in phpunit.xml

2. **Factory errors**
   - Verify factory states are defined
   - Check relationships are properly set up

3. **Validation errors**
   - Ensure test database has required records
   - Check validation rules match test data

4. **Pagination issues**
   - Verify query string preservation
   - Check page parameter validation

## Future Enhancements

### Potential Test Additions

1. **Performance Tests**
   - Query count optimization
   - Response time benchmarks
   - Large dataset handling

2. **Security Tests**
   - SQL injection prevention
   - XSS protection
   - CSRF token validation

3. **Browser Tests**
   - Filter UI interactions
   - Pagination clicks
   - Sort dropdown changes

4. **API Tests** (if API endpoints added)
   - JSON response format
   - API authentication
   - Rate limiting

## Maintenance

### When to Update Tests

1. **New filter added** - Add filter tests
2. **Validation rules changed** - Update validation tests
3. **Sorting logic changed** - Update sorting tests
4. **Pagination changed** - Update pagination tests
5. **View structure changed** - Update view assertions

### Test Review Checklist

- [ ] All tests pass
- [ ] Coverage meets targets (>90%)
- [ ] No skipped or incomplete tests
- [ ] Test names are descriptive
- [ ] Edge cases are covered
- [ ] Error conditions are tested
- [ ] Documentation is updated

## Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Pest PHP Documentation](https://pestphp.com/docs)
- [Laravel Factories](https://laravel.com/docs/database-testing#defining-model-factories)

## Contact

For questions or issues with the test suite, please contact the development team or open an issue in the project repository.

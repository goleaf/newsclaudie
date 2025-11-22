# NewsController Test Commands - Quick Reference

## Quick Start

```bash
# Run all NewsController tests
php artisan test --filter=NewsController

# Run with coverage report
php artisan test --filter=NewsController --coverage

# Run in parallel (faster)
php artisan test --filter=NewsController --parallel
```

## Individual Test Files

```bash
# Feature tests only
php artisan test tests/Feature/NewsControllerTest.php

# Unit tests only
php artisan test tests/Unit/NewsIndexRequestTest.php
```

## Specific Test Groups

```bash
# Basic functionality tests
php artisan test --filter="test_news_index_page_loads_successfully|test_news_index_displays_only_published_posts"

# Category filtering tests
php artisan test --filter="test_news_index_filters_by.*category"

# Author filtering tests
php artisan test --filter="test_news_index_filters_by.*author"

# Date filtering tests
php artisan test --filter="test_news_index_filters_by.*date"

# Sorting tests
php artisan test --filter="test_news_index_sorts"

# Validation tests
php artisan test --filter="test_news_index_validates"

# Pagination tests
php artisan test --filter="test_news_index_pagination"
```

## Coverage Reports

```bash
# Generate HTML coverage report
php artisan test --filter=NewsController --coverage-html coverage-report

# Check minimum coverage threshold
php artisan test --filter=NewsController --coverage --min=90

# Coverage for specific file
php artisan test --filter=NewsController --coverage --path=app/Http/Controllers/NewsController.php
```

## Debugging

```bash
# Run with verbose output
php artisan test --filter=NewsController -v

# Stop on first failure
php artisan test --filter=NewsController --stop-on-failure

# Run specific test method
php artisan test --filter=test_news_index_filters_by_single_category

# Show test execution time
php artisan test --filter=NewsController --profile
```

## Continuous Integration

```bash
# CI-friendly output
php artisan test --filter=NewsController --teamcity

# JUnit XML output
php artisan test --filter=NewsController --log-junit results.xml

# Run with no output (exit code only)
php artisan test --filter=NewsController --quiet
```

## Test Database

```bash
# Refresh test database before running
php artisan migrate:fresh --env=testing
php artisan test --filter=NewsController

# Seed test database
php artisan db:seed --env=testing
php artisan test --filter=NewsController
```

## Watch Mode (requires fswatch or similar)

```bash
# Auto-run tests on file changes (macOS)
fswatch -o app/Http/Controllers/NewsController.php tests/Feature/NewsControllerTest.php | xargs -n1 -I{} php artisan test --filter=NewsController

# Using entr (cross-platform)
ls app/Http/Controllers/NewsController.php tests/Feature/NewsControllerTest.php | entr php artisan test --filter=NewsController
```

## Performance Testing

```bash
# Run tests multiple times to check consistency
for i in {1..10}; do php artisan test --filter=NewsController; done

# Measure execution time
time php artisan test --filter=NewsController

# Profile slow tests
php artisan test --filter=NewsController --profile --order-by=slowest
```

## Common Workflows

### Before Committing
```bash
php artisan test --filter=NewsController --stop-on-failure
php artisan test --filter=NewsController --coverage --min=90
```

### During Development
```bash
php artisan test --filter=test_news_index_filters_by_single_category -v
```

### Full Test Suite
```bash
php artisan test tests/Feature/NewsControllerTest.php tests/Unit/NewsIndexRequestTest.php --coverage
```

### Pre-Deployment
```bash
php artisan migrate:fresh --env=testing
php artisan test --filter=NewsController --parallel --coverage --min=95
```

## Test Count Summary

```bash
# Count total tests
php artisan test --filter=NewsController --list-tests | wc -l

# Show test structure
php artisan test --filter=NewsController --list-tests
```

## Expected Output

```
PASS  Tests\Feature\NewsControllerTest
✓ news index page loads successfully
✓ news index displays only published posts
✓ news index paginates results with 15 per page
... (36 tests)

PASS  Tests\Unit\NewsIndexRequestTest
✓ authorize returns true
✓ categories can be null
✓ categories must be array
... (31 tests)

Tests:    67 passed (36 feature, 31 unit)
Duration: 2.34s
```

## Troubleshooting Commands

```bash
# Clear test cache
php artisan test --clear-cache

# Recreate test database
php artisan migrate:fresh --env=testing --seed

# Check test configuration
cat phpunit.xml | grep -A 5 "testsuites"

# Verify factory definitions
php artisan tinker
>>> Post::factory()->published()->make()
```

## Tips

1. **Use `--filter` for faster iteration** during development
2. **Run full suite before committing** to catch integration issues
3. **Check coverage regularly** to maintain quality
4. **Use `--stop-on-failure`** to fix issues one at a time
5. **Profile tests** to identify slow tests that need optimization

## Environment Variables

```bash
# Use different test database
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=NewsController

# Disable parallel execution
PARALLEL_PROCESSES=1 php artisan test --filter=NewsController

# Set test timeout
TEST_TIMEOUT=60 php artisan test --filter=NewsController
```

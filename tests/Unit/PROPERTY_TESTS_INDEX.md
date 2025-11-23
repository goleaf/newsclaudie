# Property-Based Tests Index

## Overview

This document provides a comprehensive index of all property-based tests in the project, organized by feature and property. Property-based testing verifies universal properties across many random inputs, providing higher confidence than example-based tests.

**Last Updated:** 2025-11-23

## What is Property-Based Testing?

Property-based testing verifies that certain properties (invariants) hold true for all valid inputs, rather than testing specific examples. For instance:

- **Example-based:** "Creating a post with title 'Test' should persist it"
- **Property-based:** "For ANY valid post data, creating the post should persist it correctly" (tested with 10+ random inputs)

See [PROPERTY_TESTING.md](../PROPERTY_TESTING.md) for the complete guide.

## Test Summary

| Feature | Test File | Properties | Assertions | Duration | Status |
|---------|-----------|-----------|-----------|----------|--------|
| News Filtering | `NewsFilterOptionsPropertyTest` | 2 | ~238 | ~0.36s | ✅ |
| News Filtering | `NewsClearFiltersPropertyTest` | 2 | ~343 | ~1.27s | ✅ |
| News Display | `NewsViewRenderingPropertyTest` | 3 | ~226 | ~0.22s | ✅ |
| News Navigation | `NewsLocaleAwareNavigationPropertyTest` | 1 | 115 | ~0.08s | ✅ |
| Comment Management | `CommentStatusFilterPropertyTest` | 1 | ~495 | ~0.95s | ✅ |
| Comment Management | `CommentInlineEditPropertyTest` | 1 | ~150 | ~0.40s | ✅ |
| Post Management | `PostPersistencePropertyTest` | 1 | ~55 | ~0.30s | ✅ |
| Category Management | `CategoryBadgeDisplayPropertyTest` | 1 | ~60 | ~0.25s | ✅ |
| Category Management | `CategoryRelationshipSyncPropertyTest` | 1 | ~70 | ~0.35s | ✅ |

**Total:** 9 test files, 13 properties, ~1,752 assertions

## Admin Livewire CRUD Feature

### Property 1: Data Persistence Round-Trip

**Definition:** For any resource and any valid data, creating or updating the resource should result in the data being persisted to the database and displayed correctly.

#### Post Persistence
- **File:** `tests/Unit/PostPersistencePropertyTest.php`
- **Tests:** 5 test methods
- **Iterations:** 10 per test (5 for timestamp test)
- **Assertions:** ~55
- **Validates:** Requirements 1.4
- **Documentation:** 
  - [Full Guide](POST_PERSISTENCE_PROPERTY_TESTING.md)
  - [Quick Reference](POST_PERSISTENCE_QUICK_REFERENCE.md)

**Test Coverage:**
1. Post creation with random data (title, slug, body, description, featured_image, tags, published_at)
2. Post updates with data integrity verification
3. Null optional fields handling
4. Automatic timestamp management (created_at, updated_at)
5. JSON array field serialization (tags)

### Property 3: Relationship Synchronization

**Definition:** For any post and any set of categories, assigning categories to the post should correctly sync the many-to-many relationship in both directions.

#### Category Relationship Sync
- **File:** `tests/Unit/CategoryRelationshipSyncPropertyTest.php`
- **Tests:** 6 test methods
- **Iterations:** 5-10 per test
- **Assertions:** ~70
- **Validates:** Requirements 1.7, 11.3, 11.5
- **Documentation:** Inline in test file

**Test Coverage:**
1. Bidirectional relationship synchronization
2. Updating relationships (replacing old with new)
3. Removing all category associations
4. Incremental category addition
5. Isolation (changes to one post don't affect others)
6. Relationship persistence across post updates

### Property 33: Category Badge Display

**Definition:** For any post with associated categories, the table view should display badges for all associated categories.

#### Category Badge Display
- **File:** `tests/Unit/CategoryBadgeDisplayPropertyTest.php`
- **Tests:** 6 test methods
- **Iterations:** 5-10 per test
- **Assertions:** ~60
- **Validates:** Requirements 1.7, 11.4
- **Documentation:** Inline in test file

**Test Coverage:**
1. All associated categories display as badges
2. Posts with no categories have empty collection
3. Categories are eager loadable (N+1 prevention)
4. Category order is consistent across loads
5. Badge rendering data is complete (id, name, slug)
6. All categories displayable without truncation

## Comment Management Feature

### Property 11: Status Filter Accuracy

**Definition:** For any comment status filter value, the filtered results should only include comments with that status.

#### Comment Status Filter
- **File:** `tests/Unit/CommentStatusFilterPropertyTest.php`
- **Tests:** 3 test methods
- **Iterations:** 10-15 per test
- **Assertions:** ~495
- **Validates:** Requirements 3.2
- **Documentation:**
  - [Full Guide](COMMENT_STATUS_FILTER_TESTING.md)
  - [Quick Reference](COMMENT_STATUS_FILTER_QUICK_REFERENCE.md)

**Test Coverage:**
1. Status filter returns only matching comments (Approved, Pending, Rejected)
2. withStatus scope filters correctly with enum values
3. Empty results return empty collection (not null)

### Property 1: Data Persistence Round-Trip (Inline Edit)

**Definition:** For any comment, inline editing should persist changes correctly.

#### Comment Inline Edit
- **File:** `tests/Unit/CommentInlineEditPropertyTest.php`
- **Tests:** 5 test methods
- **Iterations:** 10 per test
- **Assertions:** ~150
- **Validates:** Requirements 3.3
- **Documentation:** Inline in test file

**Test Coverage:**
1. Inline edit content persistence
2. Inline edit status persistence
3. Multiple sequential inline edits
4. Empty content edge case
5. Timestamp updates (updated_at changes, created_at preserved)

## News Feature

### News Filter Options

#### Property 4: Category Filter Completeness
**Definition:** Only categories with published posts appear in filter options.

#### Property 5: Filter Options Idempotence
**Definition:** Calling getFilterOptions() multiple times returns identical results.

- **File:** `tests/Unit/NewsFilterOptionsPropertyTest.php`
- **Tests:** 6 test methods
- **Iterations:** 10 per test
- **Assertions:** ~238
- **Duration:** ~0.36s
- **Documentation:**
  - [Full Guide](NEWS_FILTER_OPTIONS_TESTING.md)
  - [Quick Reference](NEWS_FILTER_OPTIONS_QUICK_REFERENCE.md)

**Test Coverage:**
1. Category filter completeness (only categories with published posts)
2. Filter options idempotence (multiple calls return same results)
3. Empty state handling (no categories when no published posts)
4. Category ordering consistency
5. Post count accuracy in filter options
6. Edge cases (no posts, all unpublished, mixed states)

### News Clear Filters

#### Property 13: Filter Reset Completeness
**Definition:** Clearing all filters should display the complete unfiltered collection.

#### Property 14: URL Query String Persistence
**Definition:** Filter state should be reflected in URL query parameters.

- **File:** `tests/Unit/NewsClearFiltersPropertyTest.php`
- **Tests:** 6 test methods
- **Iterations:** 10 per test
- **Assertions:** ~343
- **Duration:** ~1.27s
- **Documentation:**
  - [Full Guide](NEWS_CLEAR_FILTERS_TESTING.md)
  - [Quick Reference](NEWS_CLEAR_FILTERS_QUICK_REFERENCE.md)

**Test Coverage:**
1. Clear filters resets all filter parameters
2. Clear filters returns all published posts
3. Clear filters updates URL query string
4. Clear filters maintains sort order
5. Clear filters resets pagination
6. Clear filters is idempotent

### News View Rendering

#### Property 2: Required Fields Display
**Definition:** All news cards must display required fields (title, author, date, categories).

#### Property 3: Post Detail Links
**Definition:** All news cards must link to the correct post detail page.

#### Property 4: Lazy Loading Images
**Definition:** Featured images should use lazy loading for performance.

- **File:** `tests/Unit/NewsViewRenderingPropertyTest.php`
- **Tests:** 5 test methods
- **Iterations:** 10 per test
- **Assertions:** ~226
- **Duration:** ~0.22s
- **Documentation:**
  - [Full Guide](NEWS_VIEW_RENDERING_TESTING.md)
  - [Quick Reference](NEWS_VIEW_RENDERING_QUICK_REFERENCE.md)

**Test Coverage:**
1. Required fields display (title, author, date)
2. Category badges render correctly
3. Post detail links are correct
4. Featured images use lazy loading
5. Edge cases (no categories, no featured image)

### News Locale-Aware Navigation

#### Property: Locale-Aware Navigation Links
**Definition:** Navigation links should use translated text based on current locale.

- **File:** `tests/Unit/NewsLocaleAwareNavigationPropertyTest.php`
- **Tests:** 4 test methods
- **Iterations:** 10 per test
- **Assertions:** 115
- **Duration:** ~0.08s
- **Documentation:**
  - [Full Guide](NEWS_LOCALE_AWARE_NAVIGATION_TESTING.md)
  - [Quick Reference](NEWS_LOCALE_AWARE_NAVIGATION_QUICK_REFERENCE.md)

**Test Coverage:**
1. News link uses translated text
2. Locale switching updates navigation
3. Fallback to English when translation missing
4. Multiple locale switches are idempotent

## Running Property-Based Tests

### Run All Property Tests
```bash
php artisan test tests/Unit/ --filter=Property
```

### Run Specific Feature Tests
```bash
# Admin CRUD tests
php artisan test tests/Unit/PostPersistencePropertyTest.php
php artisan test tests/Unit/CategoryBadgeDisplayPropertyTest.php
php artisan test tests/Unit/CategoryRelationshipSyncPropertyTest.php

# Comment tests
php artisan test tests/Unit/CommentStatusFilterPropertyTest.php
php artisan test tests/Unit/CommentInlineEditPropertyTest.php

# News tests
php artisan test tests/Unit/NewsFilterOptionsPropertyTest.php
php artisan test tests/Unit/NewsClearFiltersPropertyTest.php
php artisan test tests/Unit/NewsViewRenderingPropertyTest.php
php artisan test tests/Unit/NewsLocaleAwareNavigationPropertyTest.php
```

### Run with Parallel Execution
```bash
php artisan test tests/Unit/ --filter=Property --parallel
```

## Property Testing Standards

### Iteration Counts

- **Standard:** 100 iterations (default for property-based tests)
- **Database Operations:** 10 iterations (reduced for performance)
- **With sleep():** 5 iterations (reduced due to time delays)

### Documentation Requirements

Each property test should have:
1. **Full Documentation:** Detailed guide in `*_TESTING.md`
2. **Quick Reference:** Summary in `*_QUICK_REFERENCE.md`
3. **Inline Comments:** DocBlocks and inline explanations
4. **Task Tracking:** Marked as documented in tasks.md

### Test Structure

```php
/**
 * Test Property X: Property Name
 * 
 * Property: For any [input], [expected behavior] should [result].
 * 
 * Test Strategy:
 * - Step 1
 * - Step 2
 * - Step 3
 * 
 * Iterations: N
 * 
 * @return void
 */
public function test_property_name(): void
{
    for ($i = 0; $i < N; $i++) {
        $faker = fake();
        
        // Generate random test data
        // Execute operation
        // Assert property holds
        
        // Cleanup
    }
}
```

## Related Documentation

- [Property Testing Guide](../PROPERTY_TESTING.md) - Complete guide to property-based testing
- [Test Coverage](../../docs/testing/TEST_COVERAGE.md) - Overall test coverage inventory
- [Admin CRUD Design](../../.kiro/specs/admin-livewire-crud/design.md) - Design with all properties
- [Admin CRUD Requirements](../../.kiro/specs/admin-livewire-crud/requirements.md) - Requirements document
- [Admin CRUD Tasks](../../.kiro/specs/admin-livewire-crud/tasks.md) - Implementation tasks

## Contributing

When adding new property-based tests:

1. Define the property clearly in the design document
2. Create the test file with comprehensive DocBlocks
3. Write full documentation in `*_TESTING.md`
4. Create quick reference in `*_QUICK_REFERENCE.md`
5. Update this index
6. Update `docs/testing/TEST_COVERAGE.md`
7. Mark as documented in `tasks.md`

## Changelog

### 2025-11-23
- Added PostPersistencePropertyTest documentation
- Updated test summary with latest counts
- Added Admin Livewire CRUD section
- Reorganized by feature for better navigation

### 2025-11-22
- Initial index created
- Documented News and Comment property tests
- Established documentation standards

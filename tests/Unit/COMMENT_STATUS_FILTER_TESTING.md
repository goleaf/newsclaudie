# Comment Status Filter Property Testing

**Last Updated**: 2025-11-23  
**Test File**: `tests/Unit/CommentStatusFilterPropertyTest.php`  
**Component Under Test**: `app/Models/Comment.php` (query scopes)

## Overview

This document describes the property-based testing approach for the Comment Status Filtering feature. These tests verify that comment status filters (Approved, Pending, Rejected) work correctly across all possible data combinations and edge cases.

## What is Property-Based Testing?

Property-based testing verifies universal properties that should hold true across all valid inputs. Instead of testing specific examples, we test general rules by running many iterations with randomized data.

### Example Property

**Property**: "Filtering comments by status should return only comments with that exact status"

This property must be true whether:
- There are 1 or 100 comments
- Comments are distributed evenly or unevenly across statuses
- The database is empty or full
- Multiple filters are applied simultaneously

## Properties Tested

### Property 11: Status Filter Accuracy

**Universal Rule**: For any comment status filter (Approved, Pending, Rejected), the system should return only comments with that exact status and exclude all others.

**Test Coverage**:
- ✅ Approved filter returns only approved comments
- ✅ Pending filter returns only pending comments
- ✅ Rejected filter returns only rejected comments
- ✅ No filter returns all comments
- ✅ Each filtered comment has correct status
- ✅ Filtered comments don't have other statuses
- ✅ Count matches expected number
- ✅ Empty results return empty collection (not null)
- ✅ withStatus scope works with enum values
- ✅ withStatus(null) returns all comments

**Requirements Validated**: 3.2

## Test Strategy

### Comment Statuses

The tests verify behavior for all three statuses:
- **Approved**: Comments that have been approved for display
- **Pending**: Comments awaiting moderation
- **Rejected**: Comments that have been rejected

### Iteration Counts

- **Basic filter test**: 10 iterations with random comment distributions
- **withStatus scope test**: 10 iterations with fixed distribution
- **Empty results test**: 5 iterations (edge case)

### Randomization Strategy

Each iteration creates random numbers of comments per status:

```php
// Random comment counts per status
$approvedCount = $faker->numberBetween(1, 5);
$pendingCount = $faker->numberBetween(1, 5);
$rejectedCount = $faker->numberBetween(1, 5);
```

### Query Scopes Tested

```php
// Model scopes
Comment::approved()  // Returns only approved comments
Comment::pending()   // Returns only pending comments
Comment::rejected()  // Returns only rejected comments
Comment::withStatus(CommentStatus::Approved)  // Flexible status filter
Comment::withStatus(null)  // Returns all comments
```

## Test Methods

### 1. test_status_filter_returns_only_matching_comments()

**Purpose**: Verify that each status filter returns only comments with that status

**Iterations**: 10

**Properties Verified**:
1. Approved filter returns exact count of approved comments
2. All filtered approved comments have approved status
3. Filtered approved comments don't have pending/rejected status
4. Pending filter returns exact count of pending comments
5. All filtered pending comments have pending status
6. Filtered pending comments don't have approved/rejected status
7. Rejected filter returns exact count of rejected comments
8. All filtered rejected comments have rejected status
9. Filtered rejected comments don't have approved/pending status
10. No filter returns all comments (sum of all statuses)

**Assertions per iteration**: ~40  
**Total assertions**: ~400

### 2. test_with_status_scope_filters_correctly()

**Purpose**: Verify that the withStatus scope correctly filters by enum value or returns all when null

**Iterations**: 10

**Properties Verified**:
1. withStatus(Approved) returns exactly 1 approved comment
2. Returned comment has approved status
3. withStatus(Pending) returns exactly 1 pending comment
4. Returned comment has pending status
5. withStatus(Rejected) returns exactly 1 rejected comment
6. Returned comment has rejected status
7. withStatus(null) returns all 3 comments

**Assertions per iteration**: 7  
**Total assertions**: 70

### 3. test_status_filter_returns_empty_collection_when_no_matches()

**Purpose**: Verify graceful handling when no comments match the filter

**Iterations**: 5

**Properties Verified**:
1. Pending filter returns empty collection (count = 0)
2. Empty result is a collection (not null)
3. Rejected filter returns empty collection (count = 0)
4. Empty result is a collection (not null)
5. Approved filter returns the one existing comment

**Assertions per iteration**: 5  
**Total assertions**: 25

## Running the Tests

### Run all comment status filter tests
```bash
php artisan test tests/Unit/CommentStatusFilterPropertyTest.php
```

### Run specific test
```bash
php artisan test --filter=test_status_filter_returns_only_matching_comments
```

### Run with verbose output
```bash
php artisan test tests/Unit/CommentStatusFilterPropertyTest.php --verbose
```

### Run by group
```bash
# All property tests
php artisan test --group=property-testing

# All admin CRUD tests
php artisan test --group=admin-livewire-crud
```

## Test Results

### Expected Output

```
PASS  Tests\Unit\CommentStatusFilterPropertyTest
✓ status filter returns only matching comments    0.45s
✓ with status scope filters correctly            0.38s
✓ status filter returns empty collection when no matches  0.12s

Tests:    3 passed (495 assertions)
Duration: 0.95s
```

### Assertion Breakdown

- **Basic filter test**: ~40 assertions × 10 iterations = ~400 assertions
- **withStatus scope test**: 7 assertions × 10 iterations = 70 assertions
- **Empty results test**: 5 assertions × 5 iterations = 25 assertions

**Total**: ~495 assertions across all tests

## Understanding Test Failures

### Failure: Wrong status returned

```
Failed asserting that false is true.

All filtered comments should be approved
```

**Diagnosis**: Filter is returning comments with wrong status.

**Check**:
1. `Comment::approved()` scope uses correct enum value
2. Database has correct status values
3. No global scopes interfering with query
4. Enum casting is working correctly

### Failure: Wrong count

```
Failed asserting that 3 matches expected 2.

Approved filter should return exact count of approved comments
```

**Diagnosis**: Filter is returning more/fewer comments than expected.

**Check**:
1. Test cleanup is working (no leftover comments)
2. Factory is creating correct number of comments
3. Status is being set correctly during creation
4. No duplicate comments being created

### Failure: Empty collection not returned

```
Failed asserting that null is an instance of class "Illuminate\Support\Collection".

Result should be empty collection
```

**Diagnosis**: Query is returning null instead of empty collection.

**Check**:
1. Using `->get()` not `->first()` or `->find()`
2. Eloquent query builder returns collection by default
3. No custom query modifications returning null

### Failure: withStatus(null) doesn't return all

```
Failed asserting that 2 matches expected 3.

withStatus(null) should return all comments
```

**Diagnosis**: withStatus scope not handling null correctly.

**Check**:
1. Scope implementation: `return $status ? $query->where('status', $status) : $query;`
2. Null check is working correctly
3. No other scopes being applied

## Integration with Application

### Comment Model Scopes

```php
// app/Models/Comment.php

public function scopeApproved(Builder $query): Builder
{
    return $query->where('status', CommentStatus::Approved);
}

public function scopePending(Builder $query): Builder
{
    return $query->where('status', CommentStatus::Pending);
}

public function scopeRejected(Builder $query): Builder
{
    return $query->where('status', CommentStatus::Rejected);
}

public function scopeWithStatus(Builder $query, ?CommentStatus $status): Builder
{
    return $status ? $query->where('status', $status) : $query;
}
```

### Comment Status Enum

```php
// app/Enums/CommentStatus.php

enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

### Usage in Controllers/Livewire

```php
// Get only approved comments
$approvedComments = Comment::approved()->get();

// Get only pending comments
$pendingComments = Comment::pending()->get();

// Get comments by dynamic status
$comments = Comment::withStatus($statusFilter)->get();

// Get all comments (no filter)
$allComments = Comment::withStatus(null)->get();
```

### Usage in Admin Interface

```php
// resources/views/livewire/admin/comments/index.blade.php

// Filter by status from request
$comments = Comment::query()
    ->withStatus($this->statusFilter)
    ->with(['user', 'post'])
    ->latest()
    ->paginate($this->perPage);
```

## Related Documentation

- [Quick Reference](COMMENT_STATUS_FILTER_QUICK_REFERENCE.md) - Fast-access commands and tips
- [Property Testing Guide](../PROPERTY_TESTING.md) - General property testing approach
- [Admin CRUD Requirements](../../.kiro/specs/admin-livewire-crud/requirements.md) - Feature requirements
- [Test Coverage](../../docs/testing/TEST_COVERAGE.md) - Overall test coverage

## Maintenance Notes

### When to Update These Tests

1. **When adding new statuses**: Add to CommentStatus enum and update test assertions
2. **When changing scope logic**: Update test expectations for new behavior
3. **When adding combined filters**: Add tests for status + other filters
4. **When changing default status**: Update test data creation

### Adding New Status Values

To add a new status (e.g., Spam):

1. **Add to enum**: `app/Enums/CommentStatus.php`
```php
enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Spam = 'spam';  // New status
}
```

2. **Add scope**: `app/Models/Comment.php`
```php
public function scopeSpam(Builder $query): Builder
{
    return $query->where('status', CommentStatus::Spam);
}
```

3. **Update tests**: Add spam comments to test data and verify filtering

### Performance Considerations

These tests are moderately fast (~1s total) because they:
- Use database transactions (RefreshDatabase)
- Create minimal test data
- Clean up after each iteration
- Use simple queries

### Common Issues

**Issue**: Tests fail after adding new status
**Solution**: Update test to include new status in "all comments" count

**Issue**: Cleanup not working properly
**Solution**: Ensure all relationships are detached before deleting

**Issue**: Random failures in CI
**Solution**: Check for race conditions in parallel test execution

## Troubleshooting

### Database not cleaning up

```bash
# Clear test database
php artisan migrate:fresh --env=testing

# Run tests with fresh database
php artisan test tests/Unit/CommentStatusFilterPropertyTest.php
```

### Enum casting not working

```bash
# Check Comment model has correct cast
# In app/Models/Comment.php:
protected $casts = [
    'status' => CommentStatus::class,
];
```

### Factory creating wrong status

```bash
# Check factory default
# In database/factories/CommentFactory.php:
'status' => CommentStatus::Pending,
```

## Contributing

When contributing to these tests:

1. ✅ Follow the existing documentation pattern
2. ✅ Add clear property descriptions
3. ✅ Include requirement references
4. ✅ Use appropriate test groups
5. ✅ Clean up test data properly
6. ✅ Update this documentation
7. ✅ Add examples to quick reference

## Questions?

For questions about these tests, see:
- [Quick Reference](COMMENT_STATUS_FILTER_QUICK_REFERENCE.md)
- [Property Testing Guide](../PROPERTY_TESTING.md)
- [Test Coverage Documentation](../../docs/testing/TEST_COVERAGE.md)
- Project maintainers

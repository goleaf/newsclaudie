# Comment Status Filter - Quick Reference

**Test File**: `tests/Unit/CommentStatusFilterPropertyTest.php`  
**Full Documentation**: [COMMENT_STATUS_FILTER_TESTING.md](COMMENT_STATUS_FILTER_TESTING.md)

## Quick Commands

```bash
# Run all comment status filter tests
php artisan test tests/Unit/CommentStatusFilterPropertyTest.php

# Run specific test
php artisan test --filter=test_status_filter_returns_only_matching_comments
php artisan test --filter=test_with_status_scope_filters_correctly
php artisan test --filter=test_status_filter_returns_empty_collection_when_no_matches

# Run with verbose output
php artisan test tests/Unit/CommentStatusFilterPropertyTest.php --verbose

# Run by group
php artisan test --group=property-testing
php artisan test --group=admin-livewire-crud
```

## Test Summary

| Test | Iterations | Assertions | Purpose |
|------|-----------|-----------|---------|
| Basic status filtering | 10 | ~400 | Verify each status filter returns only matching comments |
| withStatus scope | 10 | 70 | Verify flexible status filtering with enum |
| Empty results | 5 | 25 | Verify graceful handling of no matches |
| **TOTAL** | **25** | **~495** | **Complete coverage** |

## Comment Statuses

| Status | Enum Value | Scope Method | Description |
|--------|-----------|--------------|-------------|
| Pending | `CommentStatus::Pending` | `Comment::pending()` | Awaiting moderation |
| Approved | `CommentStatus::Approved` | `Comment::approved()` | Approved for display |
| Rejected | `CommentStatus::Rejected` | `Comment::rejected()` | Rejected by moderator |

## Properties Tested

### Property 11: Status Filter Accuracy

**Rule**: Status filters return only comments with that exact status

**Validates**: Requirement 3.2

**Coverage**:
- ✅ Approved filter accuracy
- ✅ Pending filter accuracy
- ✅ Rejected filter accuracy
- ✅ No filter returns all
- ✅ Empty results handling
- ✅ withStatus scope flexibility

## Quick Troubleshooting

### Wrong status returned
```bash
# Check scope implementation
cat app/Models/Comment.php | grep "scopeApproved"

# Verify enum values
cat app/Enums/CommentStatus.php
```

### Wrong count
```bash
# Check for leftover test data
php artisan tinker
>>> Comment::count()

# Fresh test database
php artisan migrate:fresh --env=testing
```

### Enum casting not working
```bash
# Verify model cast
cat app/Models/Comment.php | grep "protected \$casts"

# Should see:
# 'status' => CommentStatus::class,
```

## Common Assertions

```php
// Status check
$this->assertTrue($comment->isApproved());
$this->assertFalse($comment->isPending());

// Count check
$this->assertCount($expectedCount, $filteredComments);

// Empty collection
$this->assertTrue($results->isEmpty());
$this->assertCount(0, $results);

// Collection type
$this->assertInstanceOf(Collection::class, $results);
```

## Usage Examples

### Basic Filtering

```php
// Get approved comments
$approved = Comment::approved()->get();

// Get pending comments
$pending = Comment::pending()->get();

// Get rejected comments
$rejected = Comment::rejected()->get();
```

### Flexible Filtering

```php
// Filter by status variable
$status = CommentStatus::Approved;
$comments = Comment::withStatus($status)->get();

// Get all comments (no filter)
$allComments = Comment::withStatus(null)->get();
```

### In Livewire Components

```php
// resources/views/livewire/admin/comments/index.blade.php

public function render()
{
    $comments = Comment::query()
        ->withStatus($this->statusFilter)  // null or CommentStatus enum
        ->with(['user', 'post'])
        ->latest()
        ->paginate($this->perPage);
    
    return view('livewire.admin.comments.index', [
        'comments' => $comments,
    ]);
}
```

## Test Data

### Creating Test Comments

```php
// Create approved comment
$approved = Comment::create([
    'user_id' => $user->id,
    'post_id' => $post->id,
    'content' => 'Test comment',
    'status' => CommentStatus::Approved,
]);

// Create pending comment
$pending = Comment::create([
    'user_id' => $user->id,
    'post_id' => $post->id,
    'content' => 'Test comment',
    'status' => CommentStatus::Pending,
]);

// Create rejected comment
$rejected = Comment::create([
    'user_id' => $user->id,
    'post_id' => $post->id,
    'content' => 'Test comment',
    'status' => CommentStatus::Rejected,
]);
```

### Random Test Data

```php
$faker = fake();

// Random status
$status = $faker->randomElement([
    CommentStatus::Pending,
    CommentStatus::Approved,
    CommentStatus::Rejected,
]);

// Random count
$count = $faker->numberBetween(1, 5);
```

## Performance

- **Total duration**: ~0.95s
- **Fastest test**: 0.12s (empty results)
- **Slowest test**: 0.45s (basic filtering)
- **Average**: 0.32s per test

## Related Files

- **Test**: `tests/Unit/CommentStatusFilterPropertyTest.php`
- **Model**: `app/Models/Comment.php`
- **Enum**: `app/Enums/CommentStatus.php`
- **Livewire**: `resources/views/livewire/admin/comments/index.blade.php`
- **Factory**: `database/factories/CommentFactory.php`

## Documentation

- [Full Testing Guide](COMMENT_STATUS_FILTER_TESTING.md)
- [Property Testing Guide](../PROPERTY_TESTING.md)
- [Test Coverage](../../docs/TEST_COVERAGE.md)
- [Admin CRUD Requirements](../../.kiro/specs/admin-livewire-crud/requirements.md)

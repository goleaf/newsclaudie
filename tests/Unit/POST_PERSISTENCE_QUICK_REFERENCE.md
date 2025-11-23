# Post Persistence Property Testing - Quick Reference

## Test File
`tests/Unit/PostPersistencePropertyTest.php`

## Property Tested
**Property 1: Data persistence round-trip**  
For any post and any valid data, creating or updating the post should result in the data being persisted to the database and displayed correctly.

## Test Summary

| Test Method | Iterations | Purpose |
|------------|-----------|---------|
| `test_post_creation_persistence_round_trip()` | 10 | Verifies post creation with random data persists correctly |
| `test_post_update_persistence_round_trip()` | 10 | Verifies post updates persist and old data is removed |
| `test_post_persistence_with_null_optional_fields()` | 10 | Verifies null optional fields are handled correctly |
| `test_post_persistence_with_timestamps()` | 5 | Verifies automatic timestamp management |
| `test_post_persistence_with_tags_array()` | 10 | Verifies JSON array field serialization |

**Total Assertions:** ~55 per test run

## Quick Commands

```bash
# Run all post persistence tests
php artisan test tests/Unit/PostPersistencePropertyTest.php

# Run specific test
php artisan test --filter=test_post_creation_persistence_round_trip

# Run with verbose output
php artisan test tests/Unit/PostPersistencePropertyTest.php --verbose

# Run in parallel
php artisan test tests/Unit/PostPersistencePropertyTest.php --parallel
```

## Key Code Patterns

### Bypass Global Scope
```php
Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
```

### Access Raw Attributes (Bypass Accessors)
```php
$retrievedPost->getAttributes()['description']  // Raw DB value
$retrievedPost->description                      // Accessor value
```

### Use Factory with Overrides
```php
$post = Post::factory()->create([
    'user_id' => $user->id,
    'title' => $postTitle,
    'slug' => $postSlug,
]);
```

### Cleanup Pattern
```php
// Cleanup
$post->delete();
$user->delete();
```

## Test Data Ranges

| Field | Type | Range/Options |
|-------|------|---------------|
| Title | String | 2-5 random words, capitalized |
| Slug | String | Auto-generated from title |
| Body | String | 2-5 random paragraphs |
| Description | String/Null | Optional sentence |
| Featured Image | String/Null | Optional image URL |
| Tags | Array/Null | 1-3 tags from predefined list |
| Published At | DateTime/Null | Between -1 year and +1 month |

## Common Assertions

```php
// Database persistence
$this->assertDatabaseHas('posts', ['title' => $postTitle]);

// Retrieval by ID
$this->assertSame($postTitle, $retrievedPost->title);

// Retrieval by slug
$this->assertNotNull($foundBySlug);

// Null handling
$this->assertNull($retrievedPost->getAttributes()['description']);

// Array handling
$this->assertIsArray($retrievedPost->tags);
$this->assertEquals($tags, $retrievedPost->tags);

// Timestamp comparison (with tolerance)
$this->assertEquals($expected, $actual, "message", 1);
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Post not found" | Use `withoutGlobalScope(PublishedScope::class)` |
| Optional field assertion fails | Use `getAttributes()` for raw values |
| Timestamp comparison fails | Add 1-second tolerance to assertion |
| Slow execution | Reduce iterations or use `--parallel` |

## Related Files

- **Full Documentation:** `tests/Unit/POST_PERSISTENCE_PROPERTY_TESTING.md`
- **Property Testing Guide:** `tests/PROPERTY_TESTING.md`
- **Design Document:** `.kiro/specs/admin-livewire-crud/design.md`
- **Requirements:** `.kiro/specs/admin-livewire-crud/requirements.md`

## Validates

- **Feature:** admin-livewire-crud
- **Property:** Property 1 - Data persistence round-trip
- **Requirements:** 1.4

## Last Updated
2025-11-23

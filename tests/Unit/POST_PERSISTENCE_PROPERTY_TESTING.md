# Post Persistence Property Testing Documentation

## Overview

This document provides comprehensive documentation for the property-based tests that verify post data persistence (round-trip) operations in the admin Livewire CRUD interface.

**Test File:** `tests/Unit/PostPersistencePropertyTest.php`  
**Feature:** admin-livewire-crud  
**Property:** Property 1 - Data persistence round-trip  
**Validates:** Requirements 1.4  
**Last Updated:** 2025-11-23

## Purpose

These tests verify that post data can be reliably created, updated, and retrieved from the database with complete data integrity. The tests use property-based testing to ensure the persistence operations work correctly across a wide range of random inputs.

## Property Definition

**Property 1: Data persistence round-trip**

> For any post and any valid data, creating or updating the post should result in the data being persisted to the database and displayed correctly in the table view.

This property ensures that:
- Data written to the database can be read back identically
- Updates to existing posts persist correctly
- Optional fields (null values) are handled properly
- Timestamps are managed automatically
- JSON fields (tags) are serialized and deserialized correctly

## Test Coverage

### Test 1: Post Creation Persistence Round-Trip

**Method:** `test_post_creation_persistence_round_trip()`  
**Iterations:** 10 (reduced for database operations)

#### What It Tests

Verifies that creating a post with random data results in:
1. The post being stored in the database with exact field values
2. The post being retrievable by ID with all data intact
3. The post being findable by slug
4. Proper handling of optional fields (description, featured_image, tags)
5. Correct handling of model accessors for null values

#### Test Data Generation

- **Title:** 2-5 random words, capitalized
- **Slug:** Auto-generated from title using `Str::slug()`
- **Body:** 2-5 random paragraphs
- **Description:** Optional sentence (may be null)
- **Featured Image:** Optional image URL (may be null)
- **Tags:** Optional array of 1-3 tags from predefined list
- **Published At:** Optional datetime between -1 year and +1 month

#### Key Assertions

```php
// Database persistence
$this->assertDatabaseHas('posts', [
    'title' => $postTitle,
    'slug' => $postSlug,
    'body' => $postBody,
]);

// Retrieval by ID
$retrievedPost = Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
$this->assertSame($postTitle, $retrievedPost->title);

// Retrieval by slug
$foundBySlug = Post::withoutGlobalScope(PublishedScope::class)
    ->where('slug', $postSlug)
    ->first();
$this->assertSame($post->id, $foundBySlug->id);
```

#### Special Handling

**Model Accessors:** The Post model has accessors that provide default values for null fields:
- `description` accessor returns truncated body if null
- `featured_image` accessor returns default image if null

To test actual database values, the test uses `getAttributes()` to access raw values:

```php
if ($postDescription !== null) {
    $this->assertSame($postDescription, $retrievedPost->description);
} else {
    $this->assertNull($retrievedPost->getAttributes()['description']);
}
```

### Test 2: Post Update Persistence Round-Trip

**Method:** `test_post_update_persistence_round_trip()`  
**Iterations:** 10

#### What It Tests

Verifies that updating an existing post:
1. Persists the new values to the database
2. Removes the old values from the database
3. Returns updated values on retrieval
4. Maintains data integrity throughout the update

#### Test Flow

1. Create initial post with random data
2. Verify initial data is persisted
3. Generate new random data for update
4. Update the post
5. Verify new data is in database
6. Verify old data is no longer in database
7. Verify retrieval returns updated data

#### Key Assertions

```php
// Initial persistence
$this->assertDatabaseHas('posts', [
    'id' => $post->id,
    'title' => $initialTitle,
]);

// Update persistence
$post->update([
    'title' => $updatedTitle,
    'slug' => $updatedSlug,
    'body' => $updatedBody,
    'description' => $updatedDescription,
]);

// New data exists
$this->assertDatabaseHas('posts', [
    'id' => $post->id,
    'title' => $updatedTitle,
]);

// Old data removed
$this->assertDatabaseMissing('posts', [
    'id' => $post->id,
    'title' => $initialTitle,
]);
```

### Test 3: Post Persistence with Null Optional Fields

**Method:** `test_post_persistence_with_null_optional_fields()`  
**Iterations:** 10

#### What It Tests

Verifies that posts with null optional fields:
1. Store null values correctly in the database
2. Retrieve null values correctly
3. Handle model accessors appropriately

#### Optional Fields Tested

- `description` (string, nullable)
- `featured_image` (string, nullable)
- `tags` (JSON array, nullable)
- `published_at` (datetime, nullable)

#### Key Assertions

```php
// Database has null values
$this->assertDatabaseHas('posts', [
    'id' => $post->id,
    'description' => null,
    'featured_image' => null,
    'tags' => null,
    'published_at' => null,
]);

// Retrieved post has null values (checking raw attributes)
$this->assertNull($retrievedPost->getAttributes()['description']);
$this->assertNull($retrievedPost->tags);
$this->assertNull($retrievedPost->published_at);
```

### Test 4: Post Persistence with Timestamps

**Method:** `test_post_persistence_with_timestamps()`  
**Iterations:** 5 (reduced due to sleep() call)

#### What It Tests

Verifies that Laravel's automatic timestamp management:
1. Sets `created_at` on creation
2. Sets `updated_at` on creation
3. Keeps `created_at` unchanged on updates
4. Updates `updated_at` on updates
5. Maintains timestamp accuracy

#### Test Flow

1. Create post
2. Verify `created_at` and `updated_at` are set
3. Verify they are equal (within 1 second tolerance)
4. Wait 1 second
5. Update post
6. Verify `created_at` unchanged
7. Verify `updated_at` is newer

#### Key Assertions

```php
// Timestamps set on creation
$this->assertNotNull($post->created_at);
$this->assertNotNull($post->updated_at);

// Equal on creation (1 second tolerance)
$this->assertEquals(
    $post->created_at->timestamp,
    $post->updated_at->timestamp,
    "created_at and updated_at should be equal on creation",
    1
);

// created_at unchanged after update
$this->assertEquals(
    $originalCreatedAt->timestamp,
    $post->fresh()->created_at->timestamp
);

// updated_at newer after update
$this->assertGreaterThan(
    $originalUpdatedAt->timestamp,
    $post->fresh()->updated_at->timestamp
);
```

### Test 5: Post Persistence with Tags Array

**Method:** `test_post_persistence_with_tags_array()`  
**Iterations:** 10

#### What It Tests

Verifies that the `tags` JSON field:
1. Stores arrays correctly as JSON
2. Retrieves arrays correctly from JSON
3. Maintains array integrity on updates
4. Handles array element order

#### Test Data

Tags are randomly selected from predefined lists:
- Initial: `['php', 'laravel', 'testing', 'livewire', 'tailwind', 'vue', 'alpine']`
- Update: `['react', 'typescript', 'nodejs', 'docker']`

#### Key Assertions

```php
// Tags stored and retrieved as array
$this->assertIsArray($retrievedPost->tags);
$this->assertEquals($tags, $retrievedPost->tags);
$this->assertSame(count($tags), count($retrievedPost->tags));

// Tags update correctly
$post->update(['tags' => $newTags]);
$retrievedPost = Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
$this->assertEquals($newTags, $retrievedPost->tags);
```

## Important Implementation Details

### Global Scopes

The Post model has a `PublishedScope` that filters out unpublished posts by default. All tests must bypass this scope to test all posts:

```php
Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
```

### Model Factories

Tests use `Post::factory()->create()` to leverage factory defaults and ensure consistent test data:

```php
$post = Post::factory()->create([
    'user_id' => $user->id,
    'title' => $postTitle,
    'slug' => $postSlug,
    // ... other fields
]);
```

### Cleanup

Each test iteration includes cleanup to prevent database pollution:

```php
// Cleanup
$post->delete();
$user->delete();
```

### Iteration Count

Database tests use fewer iterations (5-10) compared to the standard 100 iterations for property-based tests to balance thoroughness with performance:

```php
// Run fewer iterations for database tests
for ($i = 0; $i < 10; $i++) {
    // Test logic
}
```

## Property-Based Testing Strategy

### Why Property-Based Testing?

Traditional example-based tests verify specific inputs:
```php
// Example-based: Tests one specific case
$post = Post::create(['title' => 'Test Post', 'slug' => 'test-post']);
$this->assertDatabaseHas('posts', ['title' => 'Test Post']);
```

Property-based tests verify universal properties across many random inputs:
```php
// Property-based: Tests 10 random cases
for ($i = 0; $i < 10; $i++) {
    $title = fake()->words(3, true);
    $post = Post::create(['title' => $title, 'slug' => Str::slug($title)]);
    $this->assertDatabaseHas('posts', ['title' => $title]);
}
```

### Benefits

1. **Broader Coverage:** Tests many different input combinations
2. **Edge Case Discovery:** Random generation finds unexpected edge cases
3. **Confidence:** Passing 10+ iterations provides high confidence
4. **Regression Prevention:** Catches bugs that specific examples might miss

### Trade-offs

1. **Performance:** More iterations = longer test execution
2. **Debugging:** Failures may be harder to reproduce
3. **Complexity:** Requires careful test data generation

## Running the Tests

### Run All Post Persistence Tests

```bash
php artisan test tests/Unit/PostPersistencePropertyTest.php
```

### Run Specific Test

```bash
php artisan test --filter=test_post_creation_persistence_round_trip
```

### Run with Verbose Output

```bash
php artisan test tests/Unit/PostPersistencePropertyTest.php --verbose
```

### Run in Parallel

```bash
php artisan test tests/Unit/PostPersistencePropertyTest.php --parallel
```

## Expected Results

All tests should pass with output similar to:

```
PASS  Tests\Unit\PostPersistencePropertyTest
✓ post creation persistence round trip (10 iterations)
✓ post update persistence round trip (10 iterations)
✓ post persistence with null optional fields (10 iterations)
✓ post persistence with timestamps (5 iterations)
✓ post persistence with tags array (10 iterations)

Tests:    5 passed (55 assertions)
Duration: 2.34s
```

## Troubleshooting

### Test Failures

If a test fails, check:

1. **Database State:** Ensure migrations are up to date
2. **Factory Definitions:** Verify `PostFactory` generates valid data
3. **Model Accessors:** Check if accessors interfere with assertions
4. **Global Scopes:** Ensure scopes are bypassed where needed
5. **Cleanup:** Verify cleanup code runs even on failures

### Common Issues

**Issue:** "Post not found" errors  
**Solution:** Ensure `withoutGlobalScope(PublishedScope::class)` is used

**Issue:** Assertion failures on optional fields  
**Solution:** Use `getAttributes()` to access raw database values

**Issue:** Timestamp comparison failures  
**Solution:** Use timestamp comparison with tolerance (1 second)

**Issue:** Slow test execution  
**Solution:** Reduce iterations or run tests in parallel

## Related Documentation

- [Property Testing Guide](../PROPERTY_TESTING.md) - General property-based testing guide
- [Admin CRUD Design](../../.kiro/specs/admin-livewire-crud/design.md) - Design document with all properties
- [Admin CRUD Requirements](../../.kiro/specs/admin-livewire-crud/requirements.md) - Requirements document
- [Admin CRUD Tasks](../../.kiro/specs/admin-livewire-crud/tasks.md) - Implementation tasks

## Changelog

### 2025-11-23
- Simplified test structure by removing redundant test cases
- Improved handling of model accessors in assertions
- Updated to use `Post::factory()->create()` consistently
- Added proper handling of `PublishedScope` throughout
- Reduced test complexity while maintaining property coverage
- Updated documentation to reflect simplified approach

### Initial Version
- Created comprehensive property-based tests for post persistence
- Implemented 5 test methods covering creation, updates, null values, timestamps, and tags
- Established 10-iteration standard for database tests
- Added proper cleanup and scope handling

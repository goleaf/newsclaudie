<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Scopes\PublishedScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-Based Tests for Post Data Persistence
 * 
 * These tests verify universal properties for post data persistence (round-trip).
 * Property-based testing ensures that data persistence works correctly across a wide
 * range of random inputs, providing higher confidence than example-based tests.
 * 
 * Feature: admin-livewire-crud, Property 1: Data persistence round-trip
 * Validates: Requirements 1.4
 * 
 * Property Definition:
 * For any post and any valid data, creating or updating the post should result in
 * the data being persisted to the database and displayed correctly in the table view.
 * 
 * Test Coverage:
 * - Post creation with random data (10 iterations)
 * - Post updates with data integrity (10 iterations)
 * - Null optional fields handling (10 iterations)
 * - Automatic timestamp management (5 iterations)
 * - JSON array field serialization (10 iterations)
 * 
 * Total Assertions: ~55 per test run
 * 
 * Documentation:
 * - Full Guide: tests/Unit/POST_PERSISTENCE_PROPERTY_TESTING.md
 * - Quick Reference: tests/Unit/POST_PERSISTENCE_QUICK_REFERENCE.md
 * 
 * @see \App\Models\Post
 * @see \App\Scopes\PublishedScope
 * @see tests/PROPERTY_TESTING.md
 */
final class PostPersistencePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Property 1: Data persistence round-trip (creation)
     * 
     * Verifies that creating a post with random data results in the data being
     * correctly persisted to the database and retrievable without data loss.
     * 
     * Property: For any post and any valid data, creating the post should result
     * in the data being persisted to the database and displayed correctly.
     * 
     * Test Strategy:
     * - Generate random post data (title, slug, body, description, featured_image, tags, published_at)
     * - Create post using factory with generated data
     * - Verify data exists in database with exact values
     * - Verify post is retrievable by ID with all fields intact
     * - Verify post is findable by slug
     * - Handle model accessors that provide default values for null fields
     * 
     * Iterations: 10 (reduced for database operations)
     * 
     * @return void
     */
    public function test_post_creation_persistence_round_trip(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user for the post
            $user = User::factory()->create();
            
            // Generate random post data
            $postTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $postSlug = Str::slug($postTitle);
            $postBody = $faker->paragraphs($faker->numberBetween(2, 5), true);
            $postDescription = $faker->optional()->sentence();
            $postFeaturedImage = $faker->optional()->imageUrl();
            $postTags = $faker->optional()->randomElements(['php', 'laravel', 'testing', 'livewire', 'tailwind'], $faker->numberBetween(1, 3));
            $postPublishedAt = $faker->optional()->dateTimeBetween('-1 year', '+1 month');
            
            // Property: Create a post with specific data
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'title' => $postTitle,
                'slug' => $postSlug,
                'body' => $postBody,
                'description' => $postDescription,
                'featured_image' => $postFeaturedImage,
                'tags' => $postTags,
                'published_at' => $postPublishedAt,
            ]);
            
            // Property: Post should exist in database with exact data
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'user_id' => $user->id,
                'title' => $postTitle,
                'slug' => $postSlug,
                'body' => $postBody,
                'description' => $postDescription,
                'featured_image' => $postFeaturedImage,
            ]);
            
            // Property: Retrieving the post should return the same data
            // Note: Must bypass PublishedScope which filters unpublished posts by default
            $retrievedPost = Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
            $this->assertNotNull($retrievedPost, "Post should be retrievable by ID");
            $this->assertSame($post->id, $retrievedPost->id, "Retrieved post should have same ID");
            $this->assertSame($postTitle, $retrievedPost->title, "Retrieved post should have same title");
            $this->assertSame($postSlug, $retrievedPost->slug, "Retrieved post should have same slug");
            $this->assertSame($postBody, $retrievedPost->body, "Retrieved post should have same body");
            
            // Property: Optional fields should persist correctly
            // Note: Post model has accessors that provide default values for null fields:
            // - description accessor returns truncated body if null
            // - featured_image accessor returns default image if null
            // To test actual database values, we use getAttributes() to bypass accessors
            if ($postDescription !== null) {
                $this->assertSame($postDescription, $retrievedPost->description, "Retrieved post should have same description");
            } else {
                $this->assertNull($retrievedPost->getAttributes()['description'], "Retrieved post should have null description in database");
            }
            
            if ($postFeaturedImage !== null) {
                $this->assertSame($postFeaturedImage, $retrievedPost->featured_image, "Retrieved post should have same featured image");
            } else {
                $this->assertNull($retrievedPost->getAttributes()['featured_image'], "Retrieved post should have null featured_image in database");
            }
            
            // Property: JSON fields (tags) should serialize/deserialize correctly
            $this->assertEquals($postTags, $retrievedPost->tags, "Retrieved post should have same tags");
            
            // Property: Finding by slug should return the same post
            $foundBySlug = Post::withoutGlobalScope(PublishedScope::class)->where('slug', $postSlug)->first();
            $this->assertNotNull($foundBySlug, "Post should be findable by slug");
            $this->assertSame($post->id, $foundBySlug->id, "Post found by slug should have same ID");
            $this->assertSame($postTitle, $foundBySlug->title, "Post found by slug should have same title");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Test Property 1: Data persistence round-trip (update)
     * 
     * Verifies that updating an existing post persists the changes correctly
     * and removes old data from the database.
     * 
     * Property: For any post, updating the post data should persist the changes
     * and return the updated data on retrieval.
     * 
     * Test Strategy:
     * - Create initial post with random data
     * - Verify initial data is persisted
     * - Generate new random data for update
     * - Update the post
     * - Verify new data exists in database
     * - Verify old data no longer exists in database
     * - Verify retrieval returns updated data
     * 
     * Iterations: 10
     * 
     * @return void
     */
    public function test_post_update_persistence_round_trip(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create initial post
            $user = User::factory()->create();
            $initialTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $initialSlug = Str::slug($initialTitle);
            $initialBody = $faker->paragraphs(3, true);
            
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'title' => $initialTitle,
                'slug' => $initialSlug,
                'body' => $initialBody,
            ]);
            
            // Property: Initial data should be persisted
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'title' => $initialTitle,
                'slug' => $initialSlug,
            ]);
            
            // Generate new data for update
            $updatedTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $updatedSlug = Str::slug($updatedTitle);
            $updatedBody = $faker->paragraphs(3, true);
            $updatedDescription = $faker->sentence();
            
            // Property: Update the post
            $post->update([
                'title' => $updatedTitle,
                'slug' => $updatedSlug,
                'body' => $updatedBody,
                'description' => $updatedDescription,
            ]);
            
            // Property: Updated data should be persisted in database
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'title' => $updatedTitle,
                'slug' => $updatedSlug,
                'body' => $updatedBody,
                'description' => $updatedDescription,
            ]);
            
            // Property: Old data should no longer exist
            $this->assertDatabaseMissing('posts', [
                'id' => $post->id,
                'title' => $initialTitle,
                'slug' => $initialSlug,
            ]);
            
            // Property: Retrieving the post should return updated data
            $retrievedPost = Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
            $this->assertSame($updatedTitle, $retrievedPost->title, "Retrieved post should have updated title");
            $this->assertSame($updatedSlug, $retrievedPost->slug, "Retrieved post should have updated slug");
            $this->assertSame($updatedBody, $retrievedPost->body, "Retrieved post should have updated body");
            $this->assertSame($updatedDescription, $retrievedPost->description, "Retrieved post should have updated description");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Test Property 1: Data persistence round-trip (null optional fields)
     * 
     * Verifies that posts with null optional fields store and retrieve null
     * values correctly, handling model accessors appropriately.
     * 
     * Property: For any post with null optional fields, the null values should
     * be persisted and retrieved correctly.
     * 
     * Test Strategy:
     * - Create post with all optional fields set to null
     * - Verify null values are stored in database
     * - Verify null values are retrieved correctly
     * - Use getAttributes() to bypass model accessors that provide defaults
     * 
     * Optional Fields Tested:
     * - description (string, nullable) - accessor returns truncated body if null
     * - featured_image (string, nullable) - accessor returns default image if null
     * - tags (JSON array, nullable)
     * - published_at (datetime, nullable)
     * 
     * Iterations: 10
     * 
     * @return void
     */
    public function test_post_persistence_with_null_optional_fields(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create post with null optional fields
            $user = User::factory()->create();
            $postTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $postSlug = Str::slug($postTitle);
            $postBody = $faker->paragraphs(3, true);
            
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'title' => $postTitle,
                'slug' => $postSlug,
                'body' => $postBody,
                'description' => null,
                'featured_image' => null,
                'tags' => null,
                'published_at' => null,
            ]);
            
            // Property: Post should be persisted with null optional fields
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'title' => $postTitle,
                'slug' => $postSlug,
                'description' => null,
                'featured_image' => null,
                'tags' => null,
                'published_at' => null,
            ]);
            
            // Property: Retrieved post should have null optional fields
            $retrievedPost = Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
            
            // Note: description has an accessor that returns truncated body if null
            // So we check the raw attribute value instead
            $this->assertNull($retrievedPost->getAttributes()['description'], "Retrieved post should have null description in database");
            $this->assertNull($retrievedPost->tags, "Retrieved post should have null tags");
            $this->assertNull($retrievedPost->published_at, "Retrieved post should have null published_at");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Test Property 1: Data persistence round-trip (timestamps)
     * 
     * Verifies that Laravel's automatic timestamp management works correctly
     * for post creation and updates.
     * 
     * Property: For any post, created_at and updated_at timestamps should be
     * automatically managed and persisted correctly.
     * 
     * Test Strategy:
     * - Create post and verify both timestamps are set
     * - Verify created_at and updated_at are equal on creation (within 1 second)
     * - Wait 1 second to ensure timestamp difference
     * - Update post
     * - Verify created_at remains unchanged
     * - Verify updated_at is newer than original
     * 
     * Iterations: 5 (reduced due to sleep() call)
     * 
     * @return void
     */
    public function test_post_persistence_with_timestamps(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Property: created_at should be set
            $this->assertNotNull($post->created_at, "created_at should be set");
            
            // Property: updated_at should be set
            $this->assertNotNull($post->updated_at, "updated_at should be set");
            
            // Property: created_at and updated_at should be equal on creation
            $this->assertEquals(
                $post->created_at->timestamp,
                $post->updated_at->timestamp,
                "created_at and updated_at should be equal on creation",
                1  // Allow 1 second difference
            );
            
            // Store original timestamps
            $originalCreatedAt = $post->created_at;
            $originalUpdatedAt = $post->updated_at;
            
            // Wait a moment to ensure timestamp difference
            sleep(1);
            
            // Property: Update the post
            $post->update(['title' => ucwords($faker->words(3, true))]);
            
            // Property: created_at should remain unchanged
            $this->assertEquals(
                $originalCreatedAt->timestamp,
                $post->fresh()->created_at->timestamp,
                "created_at should remain unchanged after update"
            );
            
            // Property: updated_at should be newer than original
            $this->assertGreaterThan(
                $originalUpdatedAt->timestamp,
                $post->fresh()->updated_at->timestamp,
                "updated_at should be newer after update"
            );
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Test Property 1: Data persistence round-trip (tags array)
     * 
     * Verifies that the tags JSON field correctly serializes and deserializes
     * array data, maintaining data integrity through create and update operations.
     * 
     * Property: For any post with tags array, the tags should be persisted and
     * retrieved as an array correctly.
     * 
     * Test Strategy:
     * - Create post with random array of tags
     * - Verify tags are stored as JSON and retrieved as array
     * - Verify array contents match original
     * - Update tags with new array
     * - Verify updated tags are persisted correctly
     * 
     * Tag Options:
     * - Initial: php, laravel, testing, livewire, tailwind, vue, alpine
     * - Update: react, typescript, nodejs, docker
     * 
     * Iterations: 10
     * 
     * @return void
     */
    public function test_post_persistence_with_tags_array(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create post with tags
            $user = User::factory()->create();
            $tags = $faker->randomElements(['php', 'laravel', 'testing', 'livewire', 'tailwind', 'vue', 'alpine'], $faker->numberBetween(1, 4));
            
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'tags' => $tags,
            ]);
            
            // Property: Tags should be persisted as JSON
            $retrievedPost = Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
            $this->assertIsArray($retrievedPost->tags, "Tags should be retrieved as array");
            $this->assertEquals($tags, $retrievedPost->tags, "Retrieved tags should match original tags");
            $this->assertSame(count($tags), count($retrievedPost->tags), "Tag count should match");
            
            // Property: Update tags
            $newTags = $faker->randomElements(['react', 'typescript', 'nodejs', 'docker'], $faker->numberBetween(1, 3));
            $post->update(['tags' => $newTags]);
            
            $retrievedPost = Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
            $this->assertEquals($newTags, $retrievedPost->tags, "Updated tags should match");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }
}

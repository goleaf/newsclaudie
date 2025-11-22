<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-Based Tests for Post Data Persistence
 * 
 * These tests verify universal properties for post data persistence (round-trip).
 */
final class PostPersistencePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip
     * Validates: Requirements 1.4
     * 
     * For any post and any valid data, creating or updating the post
     * should result in the data being persisted to the database and displayed
     * correctly in the table view.
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
            $postSlug = Str::slug($postTitle . '-' . Str::random(8));
            $postBody = $faker->paragraphs(3, true);
            $postDescription = $faker->optional()->sentence();
            $postFeaturedImage = $faker->optional()->imageUrl();
            $postTags = $faker->optional()->randomElements(
                ['php', 'laravel', 'testing', 'livewire', 'tailwind', 'alpine'],
                $faker->numberBetween(0, 3)
            );
            $postPublishedAt = $faker->optional(0.7)->dateTimeThisYear();
            
            // Property: Create a post with specific data
            $post = Post::withoutGlobalScopes()->create([
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
            ]);
            
            // Property: Retrieving the post should return the same data
            $retrievedPost = Post::withoutGlobalScopes()->find($post->id);
            $this->assertNotNull($retrievedPost, "Post should be retrievable by ID");
            $this->assertSame($post->id, $retrievedPost->id, "Retrieved post should have same ID");
            $this->assertSame($postTitle, $retrievedPost->title, "Retrieved post should have same title");
            $this->assertSame($postSlug, $retrievedPost->slug, "Retrieved post should have same slug");
            $this->assertSame($postBody, $retrievedPost->body, "Retrieved post should have same body");
            
            // Note: description has an accessor that returns truncated body when null
            // So we compare the raw database value
            if ($postDescription === null) {
                $this->assertNull($retrievedPost->getRawOriginal('description'), "Retrieved post should have null description in database");
            } else {
                $this->assertSame($postDescription, $retrievedPost->description, "Retrieved post should have same description");
            }
            
            // Note: featured_image has an accessor that returns default image when null
            // So we compare the raw database value
            if ($postFeaturedImage === null) {
                $this->assertNull($retrievedPost->getRawOriginal('featured_image'), "Retrieved post should have null featured_image in database");
            } else {
                $this->assertSame($postFeaturedImage, $retrievedPost->featured_image, "Retrieved post should have same featured_image");
            }
            
            $this->assertEquals($postTags, $retrievedPost->tags, "Retrieved post should have same tags");
            $this->assertSame($user->id, $retrievedPost->user_id, "Retrieved post should have same user_id");
            
            // Property: Finding by slug should return the same post
            $foundBySlug = Post::withoutGlobalScopes()->where('slug', $postSlug)->first();
            $this->assertNotNull($foundBySlug, "Post should be findable by slug");
            $this->assertSame($post->id, $foundBySlug->id, "Post found by slug should have same ID");
            $this->assertSame($postTitle, $foundBySlug->title, "Post found by slug should have same title");
            
            // Property: Timestamps should be set
            $this->assertNotNull($retrievedPost->created_at, "created_at should be set");
            $this->assertNotNull($retrievedPost->updated_at, "updated_at should be set");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (update)
     * Validates: Requirements 1.4
     * 
     * For any post, updating the post data should persist the changes
     * and return the updated data on retrieval.
     */
    public function test_post_update_persistence_round_trip(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create initial post
            $user = User::factory()->create();
            $initialTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $initialSlug = Str::slug($initialTitle . '-' . Str::random(8));
            $initialBody = $faker->paragraphs(3, true);
            $initialDescription = $faker->sentence();
            
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => $initialTitle,
                'slug' => $initialSlug,
                'body' => $initialBody,
                'description' => $initialDescription,
                'tags' => ['initial', 'test'],
                'published_at' => $faker->dateTimeThisYear(),
            ]);
            
            // Property: Initial data should be persisted
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'title' => $initialTitle,
                'slug' => $initialSlug,
            ]);
            
            // Generate new data for update
            $updatedTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $updatedSlug = Str::slug($updatedTitle . '-' . Str::random(8));
            $updatedBody = $faker->paragraphs(3, true);
            $updatedDescription = $faker->sentence();
            $updatedTags = ['updated', 'modified'];
            
            // Property: Update the post
            $post->update([
                'title' => $updatedTitle,
                'slug' => $updatedSlug,
                'body' => $updatedBody,
                'description' => $updatedDescription,
                'tags' => $updatedTags,
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
            $retrievedPost = Post::withoutGlobalScopes()->find($post->id);
            $this->assertSame($updatedTitle, $retrievedPost->title, "Retrieved post should have updated title");
            $this->assertSame($updatedSlug, $retrievedPost->slug, "Retrieved post should have updated slug");
            $this->assertSame($updatedBody, $retrievedPost->body, "Retrieved post should have updated body");
            $this->assertSame($updatedDescription, $retrievedPost->description, "Retrieved post should have updated description");
            $this->assertEquals($updatedTags, $retrievedPost->tags, "Retrieved post should have updated tags");
            
            // Property: Finding by new slug should work
            $foundByNewSlug = Post::withoutGlobalScopes()->where('slug', $updatedSlug)->first();
            $this->assertNotNull($foundByNewSlug, "Post should be findable by new slug");
            $this->assertSame($post->id, $foundByNewSlug->id, "Post found by new slug should have same ID");
            
            // Property: Finding by old slug should return nothing
            $foundByOldSlug = Post::withoutGlobalScopes()->where('slug', $initialSlug)->first();
            $this->assertNull($foundByOldSlug, "Post should not be findable by old slug");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (partial update)
     * Validates: Requirements 1.4
     * 
     * For any post, updating only some fields should persist those changes
     * while keeping other fields unchanged.
     */
    public function test_post_partial_update_persistence(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create initial post
            $user = User::factory()->create();
            $initialTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $initialSlug = Str::slug($initialTitle . '-' . Str::random(8));
            $initialBody = $faker->paragraphs(3, true);
            $initialDescription = $faker->sentence();
            $initialTags = ['initial', 'test'];
            
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => $initialTitle,
                'slug' => $initialSlug,
                'body' => $initialBody,
                'description' => $initialDescription,
                'tags' => $initialTags,
                'published_at' => $faker->dateTimeThisYear(),
            ]);
            
            // Property: Update only the description
            $updatedDescription = $faker->sentence();
            $post->update([
                'description' => $updatedDescription,
            ]);
            
            // Property: Description should be updated
            $retrievedPost = Post::withoutGlobalScopes()->find($post->id);
            $this->assertSame($updatedDescription, $retrievedPost->description, "Description should be updated");
            
            // Property: Other fields should remain unchanged
            $this->assertSame($initialTitle, $retrievedPost->title, "Title should remain unchanged");
            $this->assertSame($initialSlug, $retrievedPost->slug, "Slug should remain unchanged");
            $this->assertSame($initialBody, $retrievedPost->body, "Body should remain unchanged");
            $this->assertEquals($initialTags, $retrievedPost->tags, "Tags should remain unchanged");
            
            // Property: Update only the tags
            $updatedTags = ['updated', 'modified', 'new'];
            $post->update([
                'tags' => $updatedTags,
            ]);
            
            // Property: Tags should be updated
            $retrievedPost = Post::withoutGlobalScopes()->find($post->id);
            $this->assertEquals($updatedTags, $retrievedPost->tags, "Tags should be updated");
            
            // Property: Description should remain from previous update
            $this->assertSame($updatedDescription, $retrievedPost->description, "Description should remain from previous update");
            
            // Property: Other fields should still be unchanged
            $this->assertSame($initialTitle, $retrievedPost->title, "Title should still be unchanged");
            $this->assertSame($initialSlug, $retrievedPost->slug, "Slug should still be unchanged");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (null optional fields)
     * Validates: Requirements 1.4
     * 
     * For any post with null optional fields, the null values should be
     * persisted and retrieved correctly.
     */
    public function test_post_persistence_with_null_optional_fields(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create post with null optional fields
            $user = User::factory()->create();
            $postTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $postSlug = Str::slug($postTitle . '-' . Str::random(8));
            $postBody = $faker->paragraphs(3, true);
            
            $post = Post::withoutGlobalScopes()->create([
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
                'published_at' => null,
            ]);
            
            // Property: Retrieved post should have null optional fields
            $retrievedPost = Post::withoutGlobalScopes()->find($post->id);
            $this->assertNull($retrievedPost->getRawOriginal('description'), "Retrieved post should have null description");
            $this->assertNull($retrievedPost->getRawOriginal('featured_image'), "Retrieved post should have null featured_image");
            $this->assertNull($retrievedPost->published_at, "Retrieved post should have null published_at");
            
            // Property: Update to add optional fields
            $newDescription = $faker->sentence();
            $newFeaturedImage = $faker->imageUrl();
            $newTags = ['new', 'tags'];
            $newPublishedAt = $faker->dateTimeThisYear();
            
            $post->update([
                'description' => $newDescription,
                'featured_image' => $newFeaturedImage,
                'tags' => $newTags,
                'published_at' => $newPublishedAt,
            ]);
            
            $retrievedPost = Post::withoutGlobalScopes()->find($post->id);
            $this->assertSame($newDescription, $retrievedPost->description, "Description should be updated from null");
            $this->assertSame($newFeaturedImage, $retrievedPost->featured_image, "Featured image should be updated from null");
            $this->assertEquals($newTags, $retrievedPost->tags, "Tags should be updated from null");
            $this->assertNotNull($retrievedPost->published_at, "Published at should be updated from null");
            
            // Property: Update back to null
            $post->update([
                'description' => null,
                'featured_image' => null,
                'tags' => null,
                'published_at' => null,
            ]);
            
            $retrievedPost = Post::withoutGlobalScopes()->find($post->id);
            $this->assertNull($retrievedPost->getRawOriginal('description'), "Description should be updated back to null");
            $this->assertNull($retrievedPost->getRawOriginal('featured_image'), "Featured image should be updated back to null");
            $this->assertNull($retrievedPost->published_at, "Published at should be updated back to null");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (timestamps)
     * Validates: Requirements 1.4
     * 
     * For any post, created_at and updated_at timestamps should be
     * automatically managed and persisted correctly.
     */
    public function test_post_persistence_with_timestamps(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create post
            $user = User::factory()->create();
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
            ]);
            
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
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (tags array)
     * Validates: Requirements 1.4
     * 
     * For any post with tags array, the tags should be persisted and retrieved
     * correctly as an array (not as JSON string).
     */
    public function test_post_persistence_with_tags_array(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create post with various tag configurations
            $user = User::factory()->create();
            
            // Test with empty array
            $post1 = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
                'tags' => [],
            ]);
            
            $retrieved1 = Post::withoutGlobalScopes()->find($post1->id);
            $this->assertIsArray($retrieved1->tags, "Tags should be an array");
            $this->assertEmpty($retrieved1->tags, "Empty tags array should be preserved");
            
            // Test with single tag
            $post2 = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
                'tags' => ['single-tag'],
            ]);
            
            $retrieved2 = Post::withoutGlobalScopes()->find($post2->id);
            $this->assertIsArray($retrieved2->tags, "Tags should be an array");
            $this->assertCount(1, $retrieved2->tags, "Single tag should be preserved");
            $this->assertEquals(['single-tag'], $retrieved2->tags, "Single tag value should match");
            
            // Test with multiple tags
            $multipleTags = $faker->randomElements(
                ['php', 'laravel', 'testing', 'livewire', 'tailwind', 'alpine', 'pest'],
                $faker->numberBetween(2, 5)
            );
            
            $post3 = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
                'tags' => $multipleTags,
            ]);
            
            $retrieved3 = Post::withoutGlobalScopes()->find($post3->id);
            $this->assertIsArray($retrieved3->tags, "Tags should be an array");
            $this->assertEquals($multipleTags, $retrieved3->tags, "Multiple tags should be preserved");
            
            // Cleanup
            $post1->delete();
            $post2->delete();
            $post3->delete();
            $user->delete();
        }
    }
}

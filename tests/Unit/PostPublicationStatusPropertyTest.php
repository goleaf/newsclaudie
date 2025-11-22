<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-Based Tests for Post Publication Status Toggle
 * 
 * These tests verify universal properties for toggling post publication status.
 */
final class PostPublicationStatusPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 27: Publication status toggle
     * Validates: Requirements 1.6
     * 
     * For any post, toggling the publication status should update the published_at
     * field and reflect the change in the table display.
     */
    public function test_post_publication_status_toggle_updates_published_at(): void
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

            // Property: Create a draft post (published_at is null)
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => $postTitle,
                'slug' => $postSlug,
                'body' => $postBody,
                'published_at' => null,
            ]);

            // Property: Draft post should have null published_at
            $this->assertNull($post->published_at, "Draft post should have null published_at");
            $this->assertFalse($post->isPublished(), "Draft post should not be published");

            // Property: Publish the post (set published_at to now)
            $publishTime = now();
            $post->forceFill(['published_at' => $publishTime])->save();

            // Property: Published post should have non-null published_at
            $post->refresh();
            $this->assertNotNull($post->published_at, "Published post should have non-null published_at");
            $this->assertTrue($post->isPublished(), "Published post should be published");
            $this->assertEquals(
                $publishTime->timestamp,
                $post->published_at->timestamp,
                "Published post should have the correct published_at timestamp",
                1  // Allow 1 second difference
            );

            // Property: Unpublish the post (set published_at back to null)
            $post->forceFill(['published_at' => null])->save();

            // Property: Unpublished post should have null published_at again
            $post->refresh();
            $this->assertNull($post->published_at, "Unpublished post should have null published_at");
            $this->assertFalse($post->isPublished(), "Unpublished post should not be published");

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 27: Publication status toggle (multiple toggles)
     * Validates: Requirements 1.6
     * 
     * For any post, toggling the publication status multiple times should
     * correctly update the published_at field each time.
     */
    public function test_post_publication_status_multiple_toggles(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
                'published_at' => null,
            ]);

            // Property: Start as draft
            $this->assertNull($post->published_at, "Post should start as draft");
            $this->assertFalse($post->isPublished(), "Post should not be published initially");

            // Property: Toggle to published
            $firstPublishTime = now();
            $post->forceFill(['published_at' => $firstPublishTime])->save();
            $post->refresh();
            $this->assertNotNull($post->published_at, "Post should be published after first toggle");
            $this->assertTrue($post->isPublished(), "Post should be published after first toggle");

            // Property: Toggle back to draft
            $post->forceFill(['published_at' => null])->save();
            $post->refresh();
            $this->assertNull($post->published_at, "Post should be draft after second toggle");
            $this->assertFalse($post->isPublished(), "Post should not be published after second toggle");

            // Wait a moment to ensure timestamp difference
            sleep(1);

            // Property: Toggle to published again with different timestamp
            $secondPublishTime = now();
            $post->forceFill(['published_at' => $secondPublishTime])->save();
            $post->refresh();
            $this->assertNotNull($post->published_at, "Post should be published after third toggle");
            $this->assertTrue($post->isPublished(), "Post should be published after third toggle");
            $this->assertGreaterThan(
                $firstPublishTime->timestamp,
                $post->published_at->timestamp,
                "Second publish time should be later than first"
            );

            // Property: Toggle back to draft again
            $post->forceFill(['published_at' => null])->save();
            $post->refresh();
            $this->assertNull($post->published_at, "Post should be draft after fourth toggle");
            $this->assertFalse($post->isPublished(), "Post should not be published after fourth toggle");

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 27: Publication status toggle (with future date)
     * Validates: Requirements 1.6
     * 
     * For any post with a future published_at date, the post should not be
     * considered published until that date passes.
     */
    public function test_post_publication_status_with_future_date(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
                'published_at' => null,
            ]);

            // Property: Set published_at to a future date
            $futureDate = now()->addDays($faker->numberBetween(1, 30));
            $post->forceFill(['published_at' => $futureDate])->save();
            $post->refresh();

            // Property: Post should have non-null published_at
            $this->assertNotNull($post->published_at, "Post should have non-null published_at");

            // Property: Post should not be considered published (future date)
            $this->assertFalse($post->isPublished(), "Post with future published_at should not be published");

            // Property: Set published_at to a past date
            $pastDate = now()->subDays($faker->numberBetween(1, 30));
            $post->forceFill(['published_at' => $pastDate])->save();
            $post->refresh();

            // Property: Post should be considered published (past date)
            $this->assertTrue($post->isPublished(), "Post with past published_at should be published");

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 27: Publication status toggle (database persistence)
     * Validates: Requirements 1.6
     * 
     * For any post, toggling the publication status should persist the change
     * to the database and be retrievable after refresh.
     */
    public function test_post_publication_status_toggle_persists_to_database(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
                'published_at' => null,
            ]);

            $postId = $post->id;

            // Property: Draft post should be persisted with null published_at
            $this->assertDatabaseHas('posts', [
                'id' => $postId,
                'published_at' => null,
            ]);

            // Property: Publish the post
            $publishTime = now();
            $post->forceFill(['published_at' => $publishTime])->save();

            // Property: Published post should be persisted with non-null published_at
            $this->assertDatabaseHas('posts', [
                'id' => $postId,
            ]);

            // Property: Retrieve post from database should have published_at
            $retrievedPost = Post::withoutGlobalScopes()->find($postId);
            $this->assertNotNull($retrievedPost->published_at, "Retrieved post should have published_at");
            $this->assertTrue($retrievedPost->isPublished(), "Retrieved post should be published");

            // Property: Unpublish the post
            $post->forceFill(['published_at' => null])->save();

            // Property: Unpublished post should be persisted with null published_at
            $this->assertDatabaseHas('posts', [
                'id' => $postId,
                'published_at' => null,
            ]);

            // Property: Retrieve post from database should have null published_at
            $retrievedPost = Post::withoutGlobalScopes()->find($postId);
            $this->assertNull($retrievedPost->published_at, "Retrieved post should have null published_at");
            $this->assertFalse($retrievedPost->isPublished(), "Retrieved post should not be published");

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 27: Publication status toggle (preserves other fields)
     * Validates: Requirements 1.6
     * 
     * For any post, toggling the publication status should only update the
     * published_at field and leave all other fields unchanged.
     */
    public function test_post_publication_status_toggle_preserves_other_fields(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post with specific data
            $user = User::factory()->create();
            $postTitle = ucwords($faker->words(3, true));
            $postSlug = Str::slug($faker->words(3, true) . '-' . Str::random(8));
            $postBody = $faker->paragraphs(3, true);
            $postDescription = $faker->sentence();
            $postTags = ['test', 'property', 'toggle'];

            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => $postTitle,
                'slug' => $postSlug,
                'body' => $postBody,
                'description' => $postDescription,
                'tags' => $postTags,
                'published_at' => null,
            ]);

            // Property: Publish the post
            $post->forceFill(['published_at' => now()])->save();
            $post->refresh();

            // Property: All other fields should remain unchanged
            $this->assertSame($postTitle, $post->title, "Title should remain unchanged after publish");
            $this->assertSame($postSlug, $post->slug, "Slug should remain unchanged after publish");
            $this->assertSame($postBody, $post->body, "Body should remain unchanged after publish");
            $this->assertSame($postDescription, $post->description, "Description should remain unchanged after publish");
            $this->assertEquals($postTags, $post->tags, "Tags should remain unchanged after publish");
            $this->assertSame($user->id, $post->user_id, "User ID should remain unchanged after publish");

            // Property: Unpublish the post
            $post->forceFill(['published_at' => null])->save();
            $post->refresh();

            // Property: All other fields should still remain unchanged
            $this->assertSame($postTitle, $post->title, "Title should remain unchanged after unpublish");
            $this->assertSame($postSlug, $post->slug, "Slug should remain unchanged after unpublish");
            $this->assertSame($postBody, $post->body, "Body should remain unchanged after unpublish");
            $this->assertSame($postDescription, $post->description, "Description should remain unchanged after unpublish");
            $this->assertEquals($postTags, $post->tags, "Tags should remain unchanged after unpublish");
            $this->assertSame($user->id, $post->user_id, "User ID should remain unchanged after unpublish");

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 27: Publication status toggle (bulk operations)
     * Validates: Requirements 1.6
     * 
     * For any set of posts, toggling the publication status in bulk should
     * update all posts correctly.
     */
    public function test_post_publication_status_bulk_toggle(): void
    {
        // Run fewer iterations for bulk operations
        for ($i = 0; $i < 3; $i++) {
            $faker = fake();
            
            // Create a user
            $user = User::factory()->create();
            
            // Create random number of draft posts
            $postCount = $faker->numberBetween(2, 5);
            $posts = [];
            
            for ($j = 0; $j < $postCount; $j++) {
                $posts[] = Post::withoutGlobalScopes()->create([
                    'user_id' => $user->id,
                    'title' => ucwords($faker->words(3, true)),
                    'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                    'body' => $faker->paragraphs(3, true),
                    'published_at' => null,
                ]);
            }

            // Property: All posts should start as drafts
            foreach ($posts as $post) {
                $this->assertNull($post->published_at, "Post should start as draft");
                $this->assertFalse($post->isPublished(), "Post should not be published initially");
            }

            // Property: Publish all posts
            $publishTime = now();
            foreach ($posts as $post) {
                $post->forceFill(['published_at' => $publishTime])->save();
            }

            // Property: All posts should be published
            foreach ($posts as $post) {
                $post->refresh();
                $this->assertNotNull($post->published_at, "Post should be published");
                $this->assertTrue($post->isPublished(), "Post should be published");
            }

            // Property: Unpublish all posts
            foreach ($posts as $post) {
                $post->forceFill(['published_at' => null])->save();
            }

            // Property: All posts should be drafts again
            foreach ($posts as $post) {
                $post->refresh();
                $this->assertNull($post->published_at, "Post should be draft");
                $this->assertFalse($post->isPublished(), "Post should not be published");
            }

            // Cleanup
            foreach ($posts as $post) {
                $post->delete();
            }
            $user->delete();
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Scopes\PublishedScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Post Publication Status Toggle
 * 
 * These tests verify universal properties for post publication status operations.
 * 
 * Feature: admin-livewire-crud, Property 27: Publication status toggle
 * Validates: Requirements 1.6
 */
final class PostPublicationStatusPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * For any post, toggling the publication status should update the
     * published_at field and reflect the change in the table display.
     */
    public function test_post_publication_status_toggle_updates_published_at(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and draft post (published_at = null)
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'published_at' => null,
            ]);

            // Property: Draft post should have null published_at
            $this->assertNull($post->published_at, "Draft post should have null published_at");
            $this->assertFalse($post->isPublished(), "Draft post should not be published");

            // Property: Publish the post (set published_at to now)
            $publishedAt = now();
            $post->forceFill(['published_at' => $publishedAt])->save();

            // Property: Published post should have published_at set
            $post->refresh();
            $this->assertNotNull($post->published_at, "Published post should have published_at set");
            $this->assertTrue($post->isPublished(), "Published post should be published");
            $this->assertEquals(
                $publishedAt->timestamp,
                $post->published_at->timestamp,
                "Published post should have correct published_at timestamp",
                1  // Allow 1 second difference
            );

            // Property: Unpublish the post (set published_at to null)
            $post->forceFill(['published_at' => null])->save();

            // Property: Unpublished post should have null published_at
            $post->refresh();
            $this->assertNull($post->published_at, "Unpublished post should have null published_at");
            $this->assertFalse($post->isPublished(), "Unpublished post should not be published");

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * For any post, publishing a draft should make it visible in published scope.
     */
    public function test_publishing_draft_makes_post_visible_in_published_scope(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and draft post
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'published_at' => null,
            ]);

            // Property: Draft post should not be visible in default (published) scope
            $this->assertNull(
                Post::find($post->id),
                "Draft post should not be visible in published scope"
            );

            // Property: Draft post should be visible without published scope
            $this->assertNotNull(
                Post::withoutGlobalScope(PublishedScope::class)->find($post->id),
                "Draft post should be visible without published scope"
            );

            // Property: Publish the post
            $post->forceFill(['published_at' => now()->subDay()])->save();

            // Property: Published post should be visible in default (published) scope
            $this->assertNotNull(
                Post::find($post->id),
                "Published post should be visible in published scope"
            );

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * For any post, unpublishing a published post should make it invisible
     * in published scope.
     */
    public function test_unpublishing_post_makes_it_invisible_in_published_scope(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and published post
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'published_at' => now()->subDay(),
            ]);

            // Property: Published post should be visible in default (published) scope
            $this->assertNotNull(
                Post::find($post->id),
                "Published post should be visible in published scope"
            );

            // Property: Unpublish the post
            $post->forceFill(['published_at' => null])->save();

            // Property: Unpublished post should not be visible in default (published) scope
            $this->assertNull(
                Post::find($post->id),
                "Unpublished post should not be visible in published scope"
            );

            // Property: Unpublished post should still be visible without published scope
            $this->assertNotNull(
                Post::withoutGlobalScope(PublishedScope::class)->find($post->id),
                "Unpublished post should be visible without published scope"
            );

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * For any post, toggling publication status multiple times should
     * correctly update the published_at field each time.
     */
    public function test_multiple_publication_status_toggles(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and draft post
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'published_at' => null,
            ]);

            // Property: Start as draft
            $this->assertFalse($post->isPublished(), "Post should start as draft");

            // Property: Toggle 1 - Publish
            $publishedAt1 = now();
            $post->forceFill(['published_at' => $publishedAt1])->save();
            $post->refresh();
            $this->assertTrue($post->isPublished(), "Post should be published after toggle 1");

            // Property: Toggle 2 - Unpublish
            $post->forceFill(['published_at' => null])->save();
            $post->refresh();
            $this->assertFalse($post->isPublished(), "Post should be unpublished after toggle 2");

            // Property: Toggle 3 - Publish again
            $publishedAt2 = now();
            $post->forceFill(['published_at' => $publishedAt2])->save();
            $post->refresh();
            $this->assertTrue($post->isPublished(), "Post should be published after toggle 3");

            // Property: Toggle 4 - Unpublish again
            $post->forceFill(['published_at' => null])->save();
            $post->refresh();
            $this->assertFalse($post->isPublished(), "Post should be unpublished after toggle 4");

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * For any post, setting published_at to a future date should make
     * the post scheduled (not yet published).
     */
    public function test_future_published_at_makes_post_scheduled(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post with future published_at
            $user = User::factory()->create();
            $futureDate = now()->addDays($faker->numberBetween(1, 30));
            
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'published_at' => $futureDate,
            ]);

            // Property: Post with future published_at should not be published yet
            $this->assertFalse($post->isPublished(), "Post with future published_at should not be published yet");

            // Property: Post should not be visible in default (published) scope
            $this->assertNull(
                Post::find($post->id),
                "Scheduled post should not be visible in published scope"
            );

            // Property: Post should be visible without published scope
            $this->assertNotNull(
                Post::withoutGlobalScope(PublishedScope::class)->find($post->id),
                "Scheduled post should be visible without published scope"
            );

            // Property: Change published_at to past date
            $post->forceFill(['published_at' => now()->subDay()])->save();
            $post->refresh();

            // Property: Post should now be published
            $this->assertTrue($post->isPublished(), "Post with past published_at should be published");

            // Property: Post should now be visible in default (published) scope
            $this->assertNotNull(
                Post::find($post->id),
                "Published post should be visible in published scope"
            );

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * For any post, the isPublished() method should correctly reflect
     * the publication status based on published_at.
     */
    public function test_is_published_method_reflects_publication_status(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user
            $user = User::factory()->create();

            // Property: Draft post (null published_at)
            $draftPost = Post::factory()->create([
                'user_id' => $user->id,
                'published_at' => null,
            ]);
            $this->assertFalse($draftPost->isPublished(), "Draft post should return false for isPublished()");

            // Property: Published post (past published_at)
            $publishedPost = Post::factory()->create([
                'user_id' => $user->id,
                'published_at' => now()->subDays($faker->numberBetween(1, 30)),
            ]);
            $this->assertTrue($publishedPost->isPublished(), "Published post should return true for isPublished()");

            // Property: Scheduled post (future published_at)
            $scheduledPost = Post::factory()->create([
                'user_id' => $user->id,
                'published_at' => now()->addDays($faker->numberBetween(1, 30)),
            ]);
            $this->assertFalse($scheduledPost->isPublished(), "Scheduled post should return false for isPublished()");

            // Cleanup
            $draftPost->delete();
            $publishedPost->delete();
            $scheduledPost->delete();
            $user->delete();
        }
    }
}

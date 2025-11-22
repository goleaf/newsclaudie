<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublishedScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2025-01-01 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guests_only_see_published_posts(): void
    {
        $author = User::factory()->create(['is_author' => true]);

        $published = $this->makePost([
            'user_id' => $author->id,
            'title' => 'Live now',
            'slug' => 'live-now',
            'body' => 'Body',
            'published_at' => now()->subDay(),
        ]);

        $scheduled = $this->makePost([
            'user_id' => $author->id,
            'title' => 'Future',
            'slug' => 'future-post',
            'body' => 'Body',
            'published_at' => now()->addDay(),
        ]);

        $visibleSlugs = Post::pluck('slug')->all();

        $this->assertContains($published->slug, $visibleSlugs);
        $this->assertNotContains($scheduled->slug, $visibleSlugs);
    }

    public function test_authors_see_published_posts_and_their_own_drafts(): void
    {
        $author = User::factory()->create(['is_author' => true]);
        $otherAuthor = User::factory()->create(['is_author' => true]);

        $published = $this->makePost([
            'user_id' => $otherAuthor->id,
            'title' => 'Community News',
            'slug' => 'community-news',
            'body' => 'Body',
            'published_at' => now()->subDay(),
        ]);

        $ownDraft = $this->makePost([
            'user_id' => $author->id,
            'title' => 'My Draft',
            'slug' => 'my-draft',
            'body' => 'Body',
            'published_at' => now()->addDays(2),
        ]);

        $hiddenDraft = $this->makePost([
            'user_id' => $otherAuthor->id,
            'title' => 'Other Draft',
            'slug' => 'other-draft',
            'body' => 'Body',
            'published_at' => now()->addDays(2),
        ]);

        $this->actingAs($author);

        $visibleSlugs = Post::pluck('slug')->all();

        $this->assertContains($published->slug, $visibleSlugs);
        $this->assertContains($ownDraft->slug, $visibleSlugs);
        $this->assertNotContains($hiddenDraft->slug, $visibleSlugs);
    }

    public function test_admins_see_all_posts(): void
    {
        $admin = User::factory()->create([
            'is_author' => false,
            'is_admin' => true,
        ]);

        $post = $this->makePost([
            'user_id' => $admin->id,
            'title' => 'Internal Memo',
            'slug' => 'internal-memo',
            'body' => 'Body',
            'published_at' => now()->addWeek(),
        ]);

        $this->actingAs($admin);

        $visibleSlugs = Post::pluck('slug')->all();

        $this->assertContains($post->slug, $visibleSlugs);
    }

    private function makePost(array $attributes): Post
    {
        return Post::withoutGlobalScopes()->forceCreate(array_merge([
            'description' => 'desc',
            'featured_image' => null,
            'tags' => [],
        ], $attributes));
    }
}


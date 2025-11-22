<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminPostFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_posts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $match = Post::factory()->create([
            'title' => 'Breaking Search Story',
            'slug' => 'breaking-search-story',
            'published_at' => now(),
        ]);

        $other = Post::factory()->create([
            'title' => 'Unrelated Draft',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.posts.index', ['search' => 'breaking']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee($other->title);
    }

    public function test_admin_can_combine_status_and_author_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $authorA = User::factory()->create(['is_author' => true, 'name' => 'Alice Writer']);
        $authorB = User::factory()->create(['is_author' => true, 'name' => 'Bob Reporter']);

        $publishedByA = Post::factory()->create([
            'user_id' => $authorA->id,
            'title' => 'Published by Alice',
            'published_at' => now(),
        ]);

        $draftByA = Post::factory()->create([
            'user_id' => $authorA->id,
            'title' => 'Draft by Alice',
            'published_at' => null,
        ]);

        $publishedByB = Post::factory()->create([
            'user_id' => $authorB->id,
            'title' => 'Published by Bob',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.posts.index', [
                'status' => 'published',
                'author' => $authorA->id,
            ]))
            ->assertOk()
            ->assertSee($publishedByA->title)
            ->assertDontSee($draftByA->title)
            ->assertDontSee($publishedByB->title);
    }
}

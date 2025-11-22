<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PostShowCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_page_displays_attached_categories_with_filter_links(): void
    {
        $category = Category::factory()->create(['name' => 'Policy']);

        $post = Post::factory()->create(['published_at' => now()]);
        $post->categories()->attach($category);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSeeText('Policy')
            ->assertSee(route('posts.index', ['category' => $category->slug]));
    }
}


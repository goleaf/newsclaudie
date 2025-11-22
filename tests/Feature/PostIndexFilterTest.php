<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PostIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_filter_posts_by_category(): void
    {
        $categoryA = Category::factory()->create(['name' => 'Investigations']);
        $categoryB = Category::factory()->create(['name' => 'Culture']);

        $postInA = Post::factory()->create(['published_at' => now()]);
        $postInB = Post::factory()->create(['published_at' => now()]);

        $postInA->categories()->attach($categoryA);
        $postInB->categories()->attach($categoryB);

        $this->get(route('posts.index', ['category' => $categoryA->slug]))
            ->assertOk()
            ->assertSee($postInA->title)
            ->assertSee(__('posts.filtered_by_category', ['category' => $categoryA->name]))
            ->assertDontSee($postInB->title);
    }

    public function test_invalid_category_filter_sets_validation_error(): void
    {
        $response = $this->get(route('posts.index', ['category' => 'missing-category']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('category');
    }
}


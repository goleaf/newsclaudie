<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NewsControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_rate_limits_requests(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id]);

        // Make 61 requests (assuming 60/min limit)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->get(route('news.index'));
        }

        $response->assertStatus(429); // Too Many Requests
    }

    /** @test */
    public function it_validates_category_filter_input(): void
    {
        $response = $this->get(route('news.index', [
            'categories' => ['invalid', 'not-an-id'],
        ]));

        $response->assertSessionHasErrors('categories.0');
    }

    /** @test */
    public function it_limits_pagination_depth(): void
    {
        $response = $this->get(route('news.index', ['page' => 10000]));

        $response->assertSessionHasErrors('page');
    }

    /** @test */
    public function it_prevents_parameter_pollution(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        Post::factory()->published()->create([
            'user_id' => $user->id,
        ])->categories()->attach($category);

        $response = $this->get(route('news.index', [
            'categories' => [$category->id],
            '__method' => 'DELETE',
            '_token' => 'malicious',
        ]));

        // Verify malicious params are not in pagination links
        $response->assertDontSee('__method');
        $response->assertDontSee('_token');
    }

    /** @test */
    public function it_does_not_expose_sensitive_author_data(): void
    {
        $user = User::factory()->create(['email' => 'secret@example.com']);
        Post::factory()->published()->create(['user_id' => $user->id]);

        $response = $this->get(route('news.index'));

        $response->assertDontSee('secret@example.com');
    }

    /** @test */
    public function it_limits_filter_array_sizes(): void
    {
        $categories = Category::factory()->count(15)->create();

        $response = $this->get(route('news.index', [
            'categories' => $categories->pluck('id')->toArray(),
        ]));

        $response->assertSessionHasErrors('categories');
    }

    /** @test */
    public function it_limits_author_filter_array_sizes(): void
    {
        $users = User::factory()->count(15)->create();

        $response = $this->get(route('news.index', [
            'authors' => $users->pluck('id')->toArray(),
        ]));

        $response->assertSessionHasErrors('authors');
    }

    /** @test */
    public function it_prevents_future_date_filtering(): void
    {
        $response = $this->get(route('news.index', [
            'from_date' => now()->addDays(10)->format('Y-m-d'),
        ]));

        $response->assertSessionHasErrors('from_date');
    }

    /** @test */
    public function it_validates_sort_parameter(): void
    {
        $response = $this->get(route('news.index', [
            'sort' => 'malicious_value',
        ]));

        $response->assertSessionHasErrors('sort');
    }

    /** @test */
    public function it_only_shows_published_posts(): void
    {
        $user = User::factory()->create();
        
        // Create published post
        $publishedPost = Post::factory()->published()->create(['user_id' => $user->id]);
        
        // Create draft post
        $draftPost = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => null,
        ]);
        
        // Create future scheduled post
        $futurePost = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => now()->addDays(5),
        ]);

        $response = $this->get(route('news.index'));

        $response->assertSee($publishedPost->title);
        $response->assertDontSee($draftPost->title);
        $response->assertDontSee($futurePost->title);
    }

    /** @test */
    public function it_caches_filter_options(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id])
            ->categories()->attach($category);

        // First request - should cache
        $this->get(route('news.index'));

        // Verify cache exists
        $this->assertTrue(cache()->has('news.filter.categories'));
        $this->assertTrue(cache()->has('news.filter.authors'));
    }

    /** @test */
    public function it_limits_filter_options_to_prevent_resource_exhaustion(): void
    {
        // Create more than 100 categories with published posts
        $user = User::factory()->create();
        
        for ($i = 0; $i < 105; $i++) {
            $category = Category::factory()->create();
            Post::factory()->published()->create(['user_id' => $user->id])
                ->categories()->attach($category);
        }

        $response = $this->get(route('news.index'));

        // Should only load 100 categories max
        $categories = $response->viewData('categories');
        $this->assertLessThanOrEqual(100, $categories->count());
    }
}

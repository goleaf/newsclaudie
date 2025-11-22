<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\NewsFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NewsFilterServiceTest extends TestCase
{
    use RefreshDatabase;

    private NewsFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NewsFilterService();
    }

    public function test_get_filtered_posts_returns_only_published_posts(): void
    {
        // Arrange
        $published = Post::factory()->published()->create();
        $draft = Post::factory()->create(['published_at' => null]);

        // Act
        $result = $this->service->getFilteredPosts([]);

        // Assert
        $this->assertCount(1, $result['posts']);
        $this->assertEquals($published->id, $result['posts'][0]->id);
        $this->assertEquals(1, $result['totalCount']);
    }

    public function test_get_filtered_posts_applies_category_filter(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $postInCategory = Post::factory()->published()->create();
        $postInCategory->categories()->attach($category);
        $otherPost = Post::factory()->published()->create();

        // Act
        $result = $this->service->getFilteredPosts([
            'categories' => [$category->id],
        ]);

        // Assert
        $this->assertCount(1, $result['posts']);
        $this->assertEquals($postInCategory->id, $result['posts'][0]->id);
    }

    public function test_get_filtered_posts_applies_author_filter(): void
    {
        // Arrange
        $author = User::factory()->create();
        $postByAuthor = Post::factory()->published()->for($author, 'author')->create();
        $otherPost = Post::factory()->published()->create();

        // Act
        $result = $this->service->getFilteredPosts([
            'authors' => [$author->id],
        ]);

        // Assert
        $this->assertCount(1, $result['posts']);
        $this->assertEquals($postByAuthor->id, $result['posts'][0]->id);
    }

    public function test_get_filtered_posts_applies_date_range_filter(): void
    {
        // Arrange
        $oldPost = Post::factory()->published()->create([
            'published_at' => now()->subDays(10),
        ]);
        $recentPost = Post::factory()->published()->create([
            'published_at' => now()->subDays(2),
        ]);

        // Act
        $result = $this->service->getFilteredPosts([
            'from_date' => now()->subDays(5)->format('Y-m-d'),
        ]);

        // Assert
        $this->assertCount(1, $result['posts']);
        $this->assertEquals($recentPost->id, $result['posts'][0]->id);
    }

    public function test_get_filtered_posts_sorts_by_newest_first_by_default(): void
    {
        // Arrange
        $older = Post::factory()->published()->create([
            'published_at' => now()->subDays(5),
        ]);
        $newer = Post::factory()->published()->create([
            'published_at' => now()->subDays(1),
        ]);

        // Act
        $result = $this->service->getFilteredPosts([]);

        // Assert
        $this->assertEquals($newer->id, $result['posts'][0]->id);
        $this->assertEquals($older->id, $result['posts'][1]->id);
    }

    public function test_get_filtered_posts_sorts_by_oldest_first_when_specified(): void
    {
        // Arrange
        $older = Post::factory()->published()->create([
            'published_at' => now()->subDays(5),
        ]);
        $newer = Post::factory()->published()->create([
            'published_at' => now()->subDays(1),
        ]);

        // Act
        $result = $this->service->getFilteredPosts(['sort' => 'oldest']);

        // Assert
        $this->assertEquals($older->id, $result['posts'][0]->id);
        $this->assertEquals($newer->id, $result['posts'][1]->id);
    }

    public function test_get_filter_options_returns_only_categories_with_published_posts(): void
    {
        // Arrange
        $categoryWithPost = Category::factory()->create();
        $post = Post::factory()->published()->create();
        $post->categories()->attach($categoryWithPost);
        
        $emptyCategory = Category::factory()->create();

        // Act
        $options = $this->service->getFilterOptions();

        // Assert
        $this->assertCount(1, $options['categories']);
        $this->assertEquals($categoryWithPost->id, $options['categories'][0]->id);
    }

    public function test_get_filter_options_returns_only_authors_with_published_posts(): void
    {
        // Arrange
        $authorWithPost = User::factory()->create();
        Post::factory()->published()->for($authorWithPost, 'author')->create();
        
        $authorWithoutPost = User::factory()->create();

        // Act
        $options = $this->service->getFilterOptions();

        // Assert
        $this->assertCount(1, $options['authors']);
        $this->assertEquals($authorWithPost->id, $options['authors'][0]->id);
    }

    public function test_pagination_uses_15_items_per_page(): void
    {
        // Arrange
        Post::factory()->published()->count(20)->create();

        // Act
        $result = $this->service->getFilteredPosts([]);

        // Assert
        $this->assertCount(15, $result['posts']);
        $this->assertEquals(20, $result['totalCount']);
    }
}

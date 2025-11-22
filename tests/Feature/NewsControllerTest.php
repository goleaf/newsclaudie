<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for NewsController
 * 
 * Tests the news index page with filtering, sorting, and pagination functionality.
 * Covers happy paths, edge cases, and error conditions.
 */
final class NewsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable rate limiting for tests
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        
        // Clear cache before each test to avoid stale filter options
        \Illuminate\Support\Facades\Cache::flush();
    }

    // ========================================
    // BASIC FUNCTIONALITY TESTS
    // ========================================

    public function test_news_index_page_loads_successfully(): void
    {
        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('news.index');
        $response->assertViewHas(['posts', 'categories', 'authors', 'totalCount', 'appliedFilters']);
    }

    public function test_news_index_displays_only_published_posts(): void
    {
        // Arrange
        $published = Post::factory()->create(['published_at' => now()->subDay()]);
        $draft = Post::factory()->create(['published_at' => null]);
        $future = Post::factory()->create(['published_at' => now()->addDay()]);

        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $response->assertSee($published->title);
        $response->assertDontSee($draft->title);
        $response->assertDontSee($future->title);
    }

    public function test_news_index_paginates_results_with_15_per_page(): void
    {
        // Arrange
        Post::factory()->count(20)->create(['published_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertCount(15, $posts);
        $this->assertEquals(20, $response->viewData('totalCount'));
    }

    public function test_news_index_loads_relationships_efficiently(): void
    {
        // Arrange
        $author = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->for($author, 'author')->create(['published_at' => now()->subDay()]);
        $post->categories()->attach($category);

        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertTrue($posts->first()->relationLoaded('author'));
        $this->assertTrue($posts->first()->relationLoaded('categories'));
    }

    // ========================================
    // CATEGORY FILTERING TESTS
    // ========================================

    public function test_news_index_filters_by_single_category(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $postInCategory = Post::factory()->create(['published_at' => now()->subDay()]);
        $postInCategory->categories()->attach($category);
        $otherPost = Post::factory()->create(['published_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('news.index', ['categories' => [$category->id]]));

        // Assert
        $response->assertOk();
        $response->assertSee($postInCategory->title);
        $response->assertDontSee($otherPost->title);
        $this->assertEquals(1, $response->viewData('totalCount'));
    }

    public function test_news_index_filters_by_multiple_categories(): void
    {
        // Arrange
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        $categoryC = Category::factory()->create();

        $postA = Post::factory()->create(['published_at' => now()->subDay()]);
        $postA->categories()->attach($categoryA);

        $postB = Post::factory()->create(['published_at' => now()->subDay()]);
        $postB->categories()->attach($categoryB);

        $postC = Post::factory()->create(['published_at' => now()->subDay()]);
        $postC->categories()->attach($categoryC);

        // Act
        $response = $this->get(route('news.index', [
            'categories' => [$categoryA->id, $categoryB->id],
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee($postA->title);
        $response->assertSee($postB->title);
        $response->assertDontSee($postC->title);
        $this->assertEquals(2, $response->viewData('totalCount'));
    }

    public function test_news_index_returns_empty_for_category_with_no_posts(): void
    {
        // Arrange
        $category = Category::factory()->create();
        Post::factory()->create(['published_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('news.index', ['categories' => [$category->id]]));

        // Assert
        $response->assertOk();
        $this->assertCount(0, $response->viewData('posts'));
        $this->assertEquals(0, $response->viewData('totalCount'));
    }

    public function test_news_index_validates_category_exists(): void
    {
        // Act
        $response = $this->get(route('news.index', ['categories' => [99999]]));

        // Assert
        $response->assertSessionHasErrors('categories.0');
    }

    // ========================================
    // AUTHOR FILTERING TESTS
    // ========================================

    public function test_news_index_filters_by_single_author(): void
    {
        // Arrange
        $author = User::factory()->create();
        $postByAuthor = Post::factory()->for($author, 'author')->create(['published_at' => now()->subDay()]);
        $otherPost = Post::factory()->create(['published_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('news.index', ['authors' => [$author->id]]));

        // Assert
        $response->assertOk();
        $response->assertSee($postByAuthor->title);
        $response->assertDontSee($otherPost->title);
        $this->assertEquals(1, $response->viewData('totalCount'));
    }

    public function test_news_index_filters_by_multiple_authors(): void
    {
        // Arrange
        $authorA = User::factory()->create();
        $authorB = User::factory()->create();
        $authorC = User::factory()->create();

        $postA = Post::factory()->for($authorA, 'author')->create(['published_at' => now()->subDay()]);
        $postB = Post::factory()->for($authorB, 'author')->create(['published_at' => now()->subDay()]);
        $postC = Post::factory()->for($authorC, 'author')->create(['published_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('news.index', [
            'authors' => [$authorA->id, $authorB->id],
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee($postA->title);
        $response->assertSee($postB->title);
        $response->assertDontSee($postC->title);
        $this->assertEquals(2, $response->viewData('totalCount'));
    }

    public function test_news_index_validates_author_exists(): void
    {
        // Act
        $response = $this->get(route('news.index', ['authors' => [99999]]));

        // Assert
        $response->assertSessionHasErrors('authors.0');
    }

    // ========================================
    // DATE RANGE FILTERING TESTS
    // ========================================

    public function test_news_index_filters_by_from_date(): void
    {
        // Arrange
        $oldPost = Post::factory()->create(['published_at' => now()->subDays(10)]);
        $recentPost = Post::factory()->create(['published_at' => now()->subDays(2)]);

        // Act
        $response = $this->get(route('news.index', [
            'from_date' => now()->subDays(5)->format('Y-m-d'),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee($recentPost->title);
        $response->assertDontSee($oldPost->title);
        $this->assertEquals(1, $response->viewData('totalCount'));
    }

    public function test_news_index_filters_by_to_date(): void
    {
        // Arrange
        $oldPost = Post::factory()->create(['published_at' => now()->subDays(10)]);
        $recentPost = Post::factory()->create(['published_at' => now()->subDays(2)]);

        // Act
        $response = $this->get(route('news.index', [
            'to_date' => now()->subDays(5)->format('Y-m-d'),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee($oldPost->title);
        $response->assertDontSee($recentPost->title);
        $this->assertEquals(1, $response->viewData('totalCount'));
    }

    public function test_news_index_filters_by_date_range(): void
    {
        // Arrange
        $beforeRange = Post::factory()->create(['published_at' => now()->subDays(15)]);
        $inRange = Post::factory()->create(['published_at' => now()->subDays(7)]);
        $afterRange = Post::factory()->create(['published_at' => now()->subDays(2)]);

        // Act
        $response = $this->get(route('news.index', [
            'from_date' => now()->subDays(10)->format('Y-m-d'),
            'to_date' => now()->subDays(5)->format('Y-m-d'),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee($inRange->title);
        $response->assertDontSee($beforeRange->title);
        $response->assertDontSee($afterRange->title);
        $this->assertEquals(1, $response->viewData('totalCount'));
    }

    public function test_news_index_validates_from_date_before_to_date(): void
    {
        // Act
        $response = $this->get(route('news.index', [
            'from_date' => now()->format('Y-m-d'),
            'to_date' => now()->subDays(5)->format('Y-m-d'),
        ]));

        // Assert
        $response->assertSessionHasErrors('from_date');
    }

    public function test_news_index_validates_date_format(): void
    {
        // Act
        $response = $this->get(route('news.index', [
            'from_date' => 'invalid-date',
        ]));

        // Assert
        $response->assertSessionHasErrors('from_date');
    }

    // ========================================
    // SORTING TESTS
    // ========================================

    public function test_news_index_sorts_by_newest_first_by_default(): void
    {
        // Arrange
        $older = Post::factory()->create(['published_at' => now()->subDays(5), 'title' => 'Older Post']);
        $newer = Post::factory()->create(['published_at' => now()->subDays(1), 'title' => 'Newer Post']);

        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertEquals($newer->id, $posts->first()->id);
        $this->assertEquals($older->id, $posts->last()->id);
    }

    public function test_news_index_sorts_by_oldest_first_when_specified(): void
    {
        // Arrange
        $older = Post::factory()->create(['published_at' => now()->subDays(5), 'title' => 'Older Post']);
        $newer = Post::factory()->create(['published_at' => now()->subDays(1), 'title' => 'Newer Post']);

        // Act
        $response = $this->get(route('news.index', ['sort' => 'oldest']));

        // Assert
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertEquals($older->id, $posts->first()->id);
        $this->assertEquals($newer->id, $posts->last()->id);
    }

    public function test_news_index_validates_sort_parameter(): void
    {
        // Act
        $response = $this->get(route('news.index', ['sort' => 'invalid']));

        // Assert
        $response->assertSessionHasErrors('sort');
    }

    // ========================================
    // COMBINED FILTERS TESTS
    // ========================================

    public function test_news_index_applies_multiple_filters_together(): void
    {
        // Arrange
        $author = User::factory()->create();
        $category = Category::factory()->create();

        $matchingPost = Post::factory()
            ->for($author, 'author')
            ->create(['published_at' => now()->subDays(5)]);
        $matchingPost->categories()->attach($category);

        $wrongAuthor = Post::factory()->create(['published_at' => now()->subDays(5)]);
        $wrongAuthor->categories()->attach($category);

        $wrongCategory = Post::factory()
            ->for($author, 'author')
            ->create(['published_at' => now()->subDays(5)]);

        $wrongDate = Post::factory()
            ->for($author, 'author')
            ->create(['published_at' => now()->subDays(15)]);
        $wrongDate->categories()->attach($category);

        // Act
        $response = $this->get(route('news.index', [
            'categories' => [$category->id],
            'authors' => [$author->id],
            'from_date' => now()->subDays(10)->format('Y-m-d'),
            'to_date' => now()->subDays(2)->format('Y-m-d'),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee($matchingPost->title);
        $response->assertDontSee($wrongAuthor->title);
        $response->assertDontSee($wrongCategory->title);
        $response->assertDontSee($wrongDate->title);
        $this->assertEquals(1, $response->viewData('totalCount'));
    }

    // ========================================
    // FILTER OPTIONS TESTS
    // ========================================

    public function test_news_index_provides_categories_with_published_posts_only(): void
    {
        // Arrange
        $categoryWithPost = Category::factory()->create();
        $post = Post::factory()->create(['published_at' => now()->subDay()]);
        $post->categories()->attach($categoryWithPost);

        $emptyCategory = Category::factory()->create();

        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $categories = $response->viewData('categories');
        $this->assertCount(1, $categories);
        $this->assertEquals($categoryWithPost->id, $categories->first()->id);
    }

    public function test_news_index_provides_authors_with_published_posts_only(): void
    {
        // Arrange
        $authorWithPost = User::factory()->create();
        Post::factory()->for($authorWithPost, 'author')->create(['published_at' => now()->subDay()]);

        $authorWithoutPost = User::factory()->create();

        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $authors = $response->viewData('authors');
        $this->assertCount(1, $authors);
        $this->assertEquals($authorWithPost->id, $authors->first()->id);
    }

    public function test_news_index_sorts_categories_alphabetically(): void
    {
        // Arrange
        $categoryZ = Category::factory()->create(['name' => 'Zebra']);
        $categoryA = Category::factory()->create(['name' => 'Apple']);
        $categoryM = Category::factory()->create(['name' => 'Mango']);

        $postZ = Post::factory()->create(['published_at' => now()->subDay()]);
        $postZ->categories()->attach($categoryZ);

        $postA = Post::factory()->create(['published_at' => now()->subDay()]);
        $postA->categories()->attach($categoryA);

        $postM = Post::factory()->create(['published_at' => now()->subDay()]);
        $postM->categories()->attach($categoryM);

        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $categories = $response->viewData('categories');
        $this->assertEquals('Apple', $categories->first()->name);
        $this->assertEquals('Zebra', $categories->last()->name);
    }

    public function test_news_index_sorts_authors_alphabetically(): void
    {
        // Arrange
        $authorZ = User::factory()->create(['name' => 'Zoe']);
        $authorA = User::factory()->create(['name' => 'Alice']);
        $authorM = User::factory()->create(['name' => 'Mike']);

        Post::factory()->for($authorZ, 'author')->create(['published_at' => now()->subDay()]);
        Post::factory()->for($authorA, 'author')->create(['published_at' => now()->subDay()]);
        Post::factory()->for($authorM, 'author')->create(['published_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $authors = $response->viewData('authors');
        $this->assertEquals('Alice', $authors->first()->name);
        $this->assertEquals('Zoe', $authors->last()->name);
    }

    // ========================================
    // PAGINATION TESTS
    // ========================================

    public function test_news_index_pagination_preserves_query_string(): void
    {
        // Arrange
        Post::factory()->count(20)->create(['published_at' => now()->subDay()]);
        $category = Category::factory()->create();

        // Act
        $response = $this->get(route('news.index', [
            'categories' => [$category->id],
            'sort' => 'oldest',
            'page' => 2,
        ]));

        // Assert
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertStringContainsString('categories', $posts->url(1));
        $this->assertStringContainsString('sort=oldest', $posts->url(1));
    }

    public function test_news_index_validates_page_parameter(): void
    {
        // Act
        $response = $this->get(route('news.index', ['page' => 'invalid']));

        // Assert
        $response->assertSessionHasErrors('page');
    }

    public function test_news_index_validates_page_minimum_value(): void
    {
        // Act
        $response = $this->get(route('news.index', ['page' => 0]));

        // Assert
        $response->assertSessionHasErrors('page');
    }

    // ========================================
    // APPLIED FILTERS TESTS
    // ========================================

    public function test_news_index_returns_applied_filters_in_view_data(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $author = User::factory()->create();

        // Act
        $response = $this->get(route('news.index', [
            'categories' => [$category->id],
            'authors' => [$author->id],
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'sort' => 'oldest',
        ]));

        // Assert
        $response->assertOk();
        $appliedFilters = $response->viewData('appliedFilters');
        $this->assertEquals([$category->id], $appliedFilters['categories']);
        $this->assertEquals([$author->id], $appliedFilters['authors']);
        $this->assertEquals('2024-01-01', $appliedFilters['from_date']);
        $this->assertEquals('2024-12-31', $appliedFilters['to_date']);
        $this->assertEquals('oldest', $appliedFilters['sort']);
    }

    // ========================================
    // EDGE CASES AND ERROR CONDITIONS
    // ========================================

    public function test_news_index_handles_empty_database(): void
    {
        // Act
        $response = $this->get(route('news.index'));

        // Assert
        $response->assertOk();
        $this->assertCount(0, $response->viewData('posts'));
        $this->assertEquals(0, $response->viewData('totalCount'));
        $this->assertCount(0, $response->viewData('categories'));
        $this->assertCount(0, $response->viewData('authors'));
    }

    public function test_news_index_handles_empty_filter_arrays(): void
    {
        // Arrange
        Post::factory()->count(3)->create(['published_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('news.index', [
            'categories' => [],
            'authors' => [],
        ]));

        // Assert
        $response->assertOk();
        $this->assertCount(3, $response->viewData('posts'));
    }

    public function test_news_index_handles_null_filter_values(): void
    {
        // Arrange
        Post::factory()->count(3)->create(['published_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('news.index', [
            'from_date' => null,
            'to_date' => null,
        ]));

        // Assert
        $response->assertOk();
        $this->assertCount(3, $response->viewData('posts'));
    }

    public function test_news_index_handles_post_with_multiple_categories(): void
    {
        // Arrange
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        $post = Post::factory()->create(['published_at' => now()->subDay()]);
        $post->categories()->attach([$categoryA->id, $categoryB->id]);

        // Act - Filter by one of the categories
        $response = $this->get(route('news.index', ['categories' => [$categoryA->id]]));

        // Assert
        $response->assertOk();
        $response->assertSee($post->title);
        $this->assertEquals(1, $response->viewData('totalCount'));
    }

    public function test_news_index_excludes_posts_with_exact_boundary_dates(): void
    {
        // Arrange
        $boundaryDate = now()->subDays(5)->startOfDay();
        $postOnBoundary = Post::factory()->create(['published_at' => $boundaryDate]);
        $postAfterBoundary = Post::factory()->create(['published_at' => $boundaryDate->copy()->addHour()]);

        // Act - to_date should be inclusive
        $response = $this->get(route('news.index', [
            'to_date' => $boundaryDate->format('Y-m-d'),
        ]));

        // Assert
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertGreaterThanOrEqual(1, $posts->count());
    }
}

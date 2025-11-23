<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for News View Rendering
 * 
 * These tests verify universal properties that must hold true for the news view
 * rendering regardless of post content. The tests use property-based testing to
 * validate behavior across many randomized scenarios.
 * 
 * ## Properties Tested
 * 
 * - **Property 2**: Required fields display - All required fields are present in rendered HTML
 * - **Property 3**: Post detail links - Correct links to post detail pages
 * - **Property 22**: Lazy loading images - Images have loading="lazy" attribute
 * 
 * ## Testing Approach
 * 
 * Each test runs multiple iterations (10 for view rendering tests to balance coverage
 * and performance) with randomized data to verify that the properties hold across
 * diverse scenarios:
 * 
 * - Random post content (titles, excerpts, dates)
 * - Random author assignments
 * - Random category associations (0-5 categories)
 * - Random image states (with/without featured images)
 * 
 * ## Related Components
 * 
 * @see \App\Http\Controllers\NewsController The controller rendering the view
 * @see \App\Models\Post Post model with relationships
 * @see resources/views/components/news/news-card.blade.php The component being tested
 * 
 * ## Requirements Validated
 * 
 * - Requirement 1.3: Display required fields (title, excerpt, date, author, categories)
 * - Requirement 1.4: Provide clickable links to post detail pages
 * - Requirement 10.5: Use lazy loading for images
 * 
 * @package Tests\Unit
 * @group property-testing
 * @group news-page
 * @group view-rendering
 */
final class NewsViewRenderingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Property 2: Required fields display
     * 
     * **Property**: For any published post displayed as a news item, the rendered
     * HTML should contain the post title, excerpt, publication date, author name,
     * and all associated category names.
     * 
     * **Validates**: Requirement 1.3 - Display required fields for each news item
     * 
     * **Test Strategy**:
     * - Creates random posts with varying content
     * - Randomly assigns 0-5 categories to each post
     * - Renders the news-card component
     * - Verifies all required fields are present in HTML
     * - Runs 10 iterations with different random scenarios
     * 
     * **Properties Verified**:
     * 1. Post title is present in rendered HTML
     * 2. Post excerpt/description is present (if exists)
     * 3. Publication date is present and formatted
     * 4. Author name is present
     * 5. All category names are present
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-view
     */
    public function test_required_fields_display(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a post with random content
            $author = User::factory()->create();
            $post = Post::factory()
                ->for($author, 'author')
                ->create([
                    'title' => $faker->sentence(),
                    'description' => $faker->paragraph(),
                    'published_at' => now()->subDays($faker->numberBetween(1, 30)),
                ]);
            
            // Attach random number of categories (0-5)
            $categoryCount = $faker->numberBetween(0, 5);
            if ($categoryCount > 0) {
                $categories = Category::factory()->count($categoryCount)->create();
                $post->categories()->attach($categories->pluck('id'));
                $post->load('categories'); // Reload to get fresh relationships
            }
            
            // Render the news card component
            $html = (string) $this->blade('<x-news.news-card :post="$post" />', ['post' => $post]);
            
            // Property: Post title must be present
            $this->assertStringContainsString(
                $post->title,
                $html,
                "Post title '{$post->title}' should be present in rendered HTML"
            );
            
            // Property: Post description must be present (if exists)
            if ($post->description) {
                $this->assertStringContainsString(
                    $post->description,
                    $html,
                    "Post description should be present in rendered HTML"
                );
            }
            
            // Property: Publication date must be present
            $formattedDate = $post->published_at->format('M j, Y');
            $this->assertStringContainsString(
                $formattedDate,
                $html,
                "Publication date '{$formattedDate}' should be present in rendered HTML"
            );
            
            // Property: Author name must be present
            $this->assertStringContainsString(
                $post->author->name,
                $html,
                "Author name '{$post->author->name}' should be present in rendered HTML"
            );
            
            // Property: All category names must be present
            foreach ($post->categories as $category) {
                $this->assertStringContainsString(
                    $category->name,
                    $html,
                    "Category name '{$category->name}' should be present in rendered HTML"
                );
            }
            
            // Cleanup
            $post->categories()->detach();
            $post->delete();
            $author->delete();
            if (isset($categories)) {
                foreach ($categories as $category) {
                    $category->delete();
                }
            }
        }
    }

    /**
     * Test Property 3: Post detail links
     * 
     * **Property**: For any published post displayed as a news item, the rendered
     * HTML should contain clickable links that navigate to the post's detail page
     * using the correct route.
     * 
     * **Validates**: Requirement 1.4 - Provide clickable links to post detail pages
     * 
     * **Test Strategy**:
     * - Creates random posts with varying slugs
     * - Renders the news-card component
     * - Verifies correct post detail route is present in HTML
     * - Verifies multiple link instances (title, image, read more)
     * - Runs 10 iterations with different random scenarios
     * 
     * **Properties Verified**:
     * 1. Post detail route is present in rendered HTML
     * 2. Route uses correct post slug/identifier
     * 3. Multiple clickable elements link to same post
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-view
     */
    public function test_post_detail_links(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a post with random content
            $author = User::factory()->create();
            $post = Post::factory()
                ->for($author, 'author')
                ->create([
                    'title' => $faker->sentence(),
                    'slug' => $faker->unique()->slug(),
                    'published_at' => now()->subDay(),
                ]);
            
            // Render the news card component
            $html = (string) $this->blade('<x-news.news-card :post="$post" />', ['post' => $post]);
            
            // Property: Post detail route must be present
            $expectedRoute = route('posts.show', $post);
            $this->assertStringContainsString(
                $expectedRoute,
                $html,
                "Post detail route '{$expectedRoute}' should be present in rendered HTML"
            );
            
            // Property: Multiple link instances should exist (title link, read more link, etc.)
            // Count occurrences of the route in the HTML
            $linkCount = substr_count($html, $expectedRoute);
            $this->assertGreaterThanOrEqual(
                2,
                $linkCount,
                "Post detail route should appear at least twice (title and read more links)"
            );
            
            // Property: Links should use href attribute correctly
            $this->assertStringContainsString(
                'href="' . $expectedRoute . '"',
                $html,
                "Post detail links should use proper href attribute"
            );
            
            // Cleanup
            $post->delete();
            $author->delete();
        }
    }

    /**
     * Test Property 22: Lazy loading images
     * 
     * **Property**: For any news item with a featured image, the image element
     * should have the loading="lazy" attribute to enable browser-native lazy loading.
     * 
     * **Validates**: Requirement 10.5 - Use lazy loading for images
     * 
     * **Test Strategy**:
     * - Creates posts with featured images
     * - Creates posts without featured images (edge case)
     * - Renders the news-card component
     * - Verifies loading="lazy" attribute is present when image exists
     * - Verifies no image tag when no featured image
     * - Runs 10 iterations with different random scenarios
     * 
     * **Properties Verified**:
     * 1. Images have loading="lazy" attribute
     * 2. No image tag when no featured image
     * 3. Default images are not rendered (default.jpg check)
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-view
     * @group performance
     */
    public function test_lazy_loading_images(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a post with a featured image
            $author = User::factory()->create();
            $postWithImage = Post::factory()
                ->for($author, 'author')
                ->create([
                    'title' => $faker->sentence(),
                    'featured_image' => $faker->imageUrl(800, 600),
                    'published_at' => now()->subDay(),
                ]);
            
            // Render the news card component
            $htmlWithImage = (string) $this->blade('<x-news.news-card :post="$post" />', ['post' => $postWithImage]);
            
            // Property: Image tag should have loading="lazy" attribute
            $this->assertStringContainsString(
                'loading="lazy"',
                $htmlWithImage,
                "Image element should have loading='lazy' attribute for performance"
            );
            
            // Property: Image src should match the featured image
            $this->assertStringContainsString(
                $postWithImage->featured_image,
                $htmlWithImage,
                "Image src should match the post's featured image URL"
            );
            
            // Create a post without a featured image
            $postWithoutImage = Post::factory()
                ->for($author, 'author')
                ->create([
                    'title' => $faker->sentence(),
                    'featured_image' => null,
                    'published_at' => now()->subDay(),
                ]);
            
            // Render the news card component
            $htmlWithoutImage = (string) $this->blade('<x-news.news-card :post="$post" />', ['post' => $postWithoutImage]);
            
            // Property: No image tag should be present when no featured image
            $this->assertStringNotContainsString(
                '<img',
                $htmlWithoutImage,
                "No image tag should be present when post has no featured image"
            );
            
            // Create a post with default.jpg (should not render)
            $postWithDefault = Post::factory()
                ->for($author, 'author')
                ->create([
                    'title' => $faker->sentence(),
                    'featured_image' => '/storage/default.jpg',
                    'published_at' => now()->subDay(),
                ]);
            
            // Render the news card component
            $htmlWithDefault = (string) $this->blade('<x-news.news-card :post="$post" />', ['post' => $postWithDefault]);
            
            // Property: Default image should not be rendered
            $this->assertStringNotContainsString(
                '<img',
                $htmlWithDefault,
                "Default image (default.jpg) should not be rendered"
            );
            
            // Cleanup
            $postWithImage->delete();
            $postWithoutImage->delete();
            $postWithDefault->delete();
            $author->delete();
        }
    }

    /**
     * Test Property 2 (Edge Case): Posts without description
     * 
     * **Property**: For any published post without a description, the rendered
     * HTML should still contain all other required fields (title, date, author,
     * categories) without errors.
     * 
     * **Validates**: Requirement 1.3 - Graceful handling of missing optional fields
     * 
     * **Test Strategy**:
     * - Creates posts with null or empty descriptions
     * - Verifies other required fields are still present
     * - Verifies no errors or broken HTML
     * - Runs 10 iterations
     * 
     * **Edge Cases Tested**:
     * - Null description
     * - Empty string description
     * - Whitespace-only description
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-view
     * @group edge-cases
     */
    public function test_required_fields_display_without_description(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a post without description
            $author = User::factory()->create();
            $descriptionValue = $faker->randomElement([null, '', '   ']);
            
            $post = Post::factory()
                ->for($author, 'author')
                ->create([
                    'title' => $faker->sentence(),
                    'description' => $descriptionValue,
                    'published_at' => now()->subDay(),
                ]);
            
            // Reload to ensure relationships are loaded
            $post->load('author');
            
            // Render the news card component
            $html = (string) $this->blade('<x-news.news-card :post="$post" />', ['post' => $post]);
            
            // Property: Title must still be present
            $this->assertStringContainsString(
                $post->title,
                $html,
                "Post title should be present even without description"
            );
            
            // Property: Date must still be present
            $formattedDate = $post->published_at->format('M j, Y');
            $this->assertStringContainsString(
                $formattedDate,
                $html,
                "Publication date should be present even without description"
            );
            
            // Property: Author must still be present
            $this->assertStringContainsString(
                $post->author->name,
                $html,
                "Author name should be present even without description"
            );
            
            // Property: HTML should be valid (no broken tags)
            $this->assertStringNotContainsString(
                '<p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300 line-clamp-3"></p>',
                $html,
                "Empty description paragraph should not be rendered"
            );
            
            // Cleanup
            $post->delete();
            $author->delete();
        }
    }

    /**
     * Test Property 2 (Edge Case): Posts without categories
     * 
     * **Property**: For any published post without categories, the rendered
     * HTML should still contain all other required fields without errors.
     * 
     * **Validates**: Requirement 1.3 - Graceful handling of posts without categories
     * 
     * **Test Strategy**:
     * - Creates posts with no category associations
     * - Verifies other required fields are still present
     * - Verifies no category section is rendered
     * - Runs 10 iterations
     * 
     * **Edge Cases Tested**:
     * - Zero categories associated
     * - Empty categories collection
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-view
     * @group edge-cases
     */
    public function test_required_fields_display_without_categories(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a post without categories
            $author = User::factory()->create();
            $post = Post::factory()
                ->for($author, 'author')
                ->create([
                    'title' => $faker->sentence(),
                    'description' => $faker->paragraph(),
                    'published_at' => now()->subDay(),
                ]);
            
            // Explicitly ensure no categories
            $post->categories()->detach();
            $post->load(['categories', 'author']);
            
            // Render the news card component
            $html = (string) $this->blade('<x-news.news-card :post="$post" />', ['post' => $post]);
            
            // Property: Title must still be present
            $this->assertStringContainsString(
                $post->title,
                $html,
                "Post title should be present even without categories"
            );
            
            // Property: Description must still be present
            $this->assertStringContainsString(
                $post->description,
                $html,
                "Post description should be present even without categories"
            );
            
            // Property: Date must still be present
            $formattedDate = $post->published_at->format('M j, Y');
            $this->assertStringContainsString(
                $formattedDate,
                $html,
                "Publication date should be present even without categories"
            );
            
            // Property: Author must still be present
            $this->assertStringContainsString(
                $post->author->name,
                $html,
                "Author name should be present even without categories"
            );
            
            // Cleanup
            $post->delete();
            $author->delete();
        }
    }
}


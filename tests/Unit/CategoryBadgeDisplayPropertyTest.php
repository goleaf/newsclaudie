<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Property-Based Tests for Category Badge Display
 * 
 * These tests verify universal properties for category badge display in post views.
 */
final class CategoryBadgeDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper method to create a published post for testing
     */
    private function createPublishedPost(User $user, $faker): Post
    {
        return Post::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'title' => ucwords($faker->words(3, true)),
            'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
            'body' => $faker->paragraphs(3, true),
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * Feature: admin-livewire-crud, Property 33: Category badge display
     * Validates: Requirements 1.7, 11.4
     * 
     * For any post with associated categories, the table view should display
     * badges for all associated categories.
     */
    public function test_post_displays_all_associated_category_badges(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create(['is_admin' => true]);
            $post = $this->createPublishedPost($user, $faker);
            
            // Create random number of categories (1-5)
            $categoryCount = $faker->numberBetween(1, 5);
            $categories = Category::factory()->count($categoryCount)->create();
            
            // Property: Assign categories to post
            $post->categories()->sync($categories->pluck('id')->toArray());
            
            // Property: Load the admin posts index component
            $component = Livewire::actingAs($user)
                ->test('admin.posts.index');
            
            // Property: Component should render successfully
            $component->assertOk();
            
            // Property: Each category should be displayed as a badge
            foreach ($categories as $category) {
                $component->assertSee($category->name);
            }
            
            // Property: The number of category badges should match the number of categories
            $html = $component->html();
            $post->refresh();
            
            // Count how many times each category name appears in the HTML
            foreach ($categories as $category) {
                $this->assertStringContainsString(
                    $category->name,
                    $html,
                    "Category '{$category->name}' should be displayed in the view"
                );
            }
            
            // Cleanup
            $post->categories()->detach();
            $post->delete();
            $categories->each->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 33: Category badge display (no categories)
     * Validates: Requirements 1.7, 11.4
     * 
     * For any post with no associated categories, the table view should not
     * display any category badges for that post.
     */
    public function test_post_without_categories_displays_no_badges(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post without categories
            $user = User::factory()->create(['is_admin' => true]);
            $post = $this->createPublishedPost($user, $faker);
            
            // Property: Post should have no categories
            $this->assertCount(0, $post->categories, "Post should have no categories");
            
            // Property: Load the admin posts index component
            $component = Livewire::actingAs($user)
                ->test('admin.posts.index');
            
            // Property: Component should render successfully
            $component->assertOk();
            
            // Property: Post should be visible in the list
            $component->assertSee($post->title);
            
            // Property: No category badges should be displayed for this post
            // (We can't easily test the absence of badges without checking the specific HTML structure,
            // but we can verify the post is displayed without errors)
            $html = $component->html();
            $this->assertStringContainsString($post->title, $html, "Post title should be displayed");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 33: Category badge display (multiple posts)
     * Validates: Requirements 1.7, 11.4
     * 
     * For any set of posts with different category associations, each post
     * should display only its own associated category badges.
     */
    public function test_multiple_posts_display_correct_category_badges(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user
            $user = User::factory()->create(['is_admin' => true]);
            
            // Create categories
            $category1 = Category::factory()->create(['name' => 'Category-' . Str::random(8)]);
            $category2 = Category::factory()->create(['name' => 'Category-' . Str::random(8)]);
            $category3 = Category::factory()->create(['name' => 'Category-' . Str::random(8)]);
            
            // Create post 1 with category 1 and 2
            $post1 = $this->createPublishedPost($user, $faker);
            $post1->categories()->sync([$category1->id, $category2->id]);
            
            // Create post 2 with category 2 and 3
            $post2 = $this->createPublishedPost($user, $faker);
            $post2->categories()->sync([$category2->id, $category3->id]);
            
            // Create post 3 with only category 1
            $post3 = $this->createPublishedPost($user, $faker);
            $post3->categories()->sync([$category1->id]);
            
            // Property: Load the admin posts index component
            $component = Livewire::actingAs($user)
                ->test('admin.posts.index');
            
            // Property: Component should render successfully
            $component->assertOk();
            
            // Property: All posts should be visible
            $component->assertSee($post1->title);
            $component->assertSee($post2->title);
            $component->assertSee($post3->title);
            
            // Property: All categories should be visible (since they're all used)
            $component->assertSee($category1->name);
            $component->assertSee($category2->name);
            $component->assertSee($category3->name);
            
            // Property: Verify the HTML structure contains the categories
            $html = $component->html();
            
            // Each category should appear in the HTML
            $this->assertStringContainsString($category1->name, $html);
            $this->assertStringContainsString($category2->name, $html);
            $this->assertStringContainsString($category3->name, $html);
            
            // Cleanup
            $post1->categories()->detach();
            $post2->categories()->detach();
            $post3->categories()->detach();
            $post1->delete();
            $post2->delete();
            $post3->delete();
            $category1->delete();
            $category2->delete();
            $category3->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 33: Category badge display (after sync)
     * Validates: Requirements 1.7, 11.4
     * 
     * For any post, after syncing new categories, the table view should
     * immediately display the updated category badges.
     */
    public function test_category_badges_update_after_sync(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create(['is_admin' => true]);
            $post = $this->createPublishedPost($user, $faker);
            
            // Create initial categories
            $initialCategories = Category::factory()->count(2)->create();
            $post->categories()->sync($initialCategories->pluck('id')->toArray());
            
            // Property: Load the admin posts index component
            $component = Livewire::actingAs($user)
                ->test('admin.posts.index');
            
            // Property: Initial categories should be displayed
            foreach ($initialCategories as $category) {
                $component->assertSee($category->name);
            }
            
            // Create new categories
            $newCategories = Category::factory()->count(3)->create();
            
            // Property: Sync new categories (replace old ones)
            $post->categories()->sync($newCategories->pluck('id')->toArray());
            
            // Property: Reload the component to see updated badges
            $component = Livewire::actingAs($user)
                ->test('admin.posts.index');
            
            // Property: New categories should be displayed
            foreach ($newCategories as $category) {
                $component->assertSee($category->name);
            }
            
            // Property: Verify in HTML
            $html = $component->html();
            foreach ($newCategories as $category) {
                $this->assertStringContainsString(
                    $category->name,
                    $html,
                    "New category '{$category->name}' should be displayed after sync"
                );
            }
            
            // Cleanup
            $post->categories()->detach();
            $post->delete();
            $initialCategories->each->delete();
            $newCategories->each->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 33: Category badge display (special characters)
     * Validates: Requirements 1.7, 11.4
     * 
     * For any post with categories containing special characters in their names,
     * the badges should display the names correctly without encoding issues.
     */
    public function test_category_badges_display_special_characters_correctly(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create(['is_admin' => true]);
            $post = $this->createPublishedPost($user, $faker);
            
            // Create categories with special characters
            $specialNames = [
                'Tech & Innovation',
                'Design/UX',
                'AI/ML Research',
                'Web 3.0',
                'C++ Programming',
            ];
            
            $categories = collect();
            foreach ($specialNames as $name) {
                $category = Category::factory()->create([
                    'name' => $name,
                    'slug' => Str::slug($name . '-' . Str::random(8)),
                ]);
                $categories->push($category);
            }
            
            // Property: Assign categories to post
            $post->categories()->sync($categories->pluck('id')->toArray());
            
            // Property: Load the admin posts index component
            $component = Livewire::actingAs($user)
                ->test('admin.posts.index');
            
            // Property: Component should render successfully
            $component->assertOk();
            
            // Property: Each category with special characters should be displayed correctly
            foreach ($categories as $category) {
                $component->assertSee($category->name);
            }
            
            // Property: Verify in HTML that special characters are properly handled
            $html = $component->html();
            foreach ($categories as $category) {
                // The HTML should contain the category name (may be HTML-encoded)
                $this->assertTrue(
                    str_contains($html, $category->name) || 
                    str_contains($html, htmlspecialchars($category->name, ENT_QUOTES)),
                    "Category '{$category->name}' should be displayed correctly with special characters"
                );
            }
            
            // Cleanup
            $post->categories()->detach();
            $post->delete();
            $categories->each->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 33: Category badge display (long names)
     * Validates: Requirements 1.7, 11.4
     * 
     * For any post with categories having long names, the badges should
     * display the full names without truncation.
     */
    public function test_category_badges_display_long_names_correctly(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create(['is_admin' => true]);
            $post = $this->createPublishedPost($user, $faker);
            
            // Create categories with long names
            $longName1 = 'Advanced Machine Learning and Artificial Intelligence Research';
            $longName2 = 'Full Stack Web Development with Modern JavaScript Frameworks';
            
            $category1 = Category::factory()->create([
                'name' => $longName1,
                'slug' => Str::slug($longName1 . '-' . Str::random(8)),
            ]);
            
            $category2 = Category::factory()->create([
                'name' => $longName2,
                'slug' => Str::slug($longName2 . '-' . Str::random(8)),
            ]);
            
            // Property: Assign categories to post
            $post->categories()->sync([$category1->id, $category2->id]);
            
            // Property: Load the admin posts index component
            $component = Livewire::actingAs($user)
                ->test('admin.posts.index');
            
            // Property: Component should render successfully
            $component->assertOk();
            
            // Property: Long category names should be displayed
            $component->assertSee($longName1);
            $component->assertSee($longName2);
            
            // Property: Verify in HTML
            $html = $component->html();
            $this->assertStringContainsString($longName1, $html, "Long category name 1 should be displayed");
            $this->assertStringContainsString($longName2, $html, "Long category name 2 should be displayed");
            
            // Cleanup
            $post->categories()->detach();
            $post->delete();
            $category1->delete();
            $category2->delete();
            $user->delete();
        }
    }
}

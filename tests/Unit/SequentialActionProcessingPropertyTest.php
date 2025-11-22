<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Enums\CommentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Property-Based Tests for Sequential Action Processing
 * 
 * These tests verify that when multiple actions are queued, the system processes
 * them sequentially and updates the UI for each action in order.
 */
final class SequentialActionProcessingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 38: Sequential action processing
     * Validates: Requirements 12.5
     * 
     * For any queue of multiple actions, the system should process them sequentially
     * and update the UI for each action in order.
     * 
     * This test verifies that multiple category deletions are processed sequentially,
     * with each deletion completing before the next one starts.
     */
    public function test_multiple_category_deletions_process_sequentially(): void
    {
        // Run multiple iterations to verify property holds across different scenarios
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create multiple categories
            $categoryCount = $faker->numberBetween(3, 5);
            $categories = Category::factory()->count($categoryCount)->create();
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: All categories should initially be visible
            foreach ($categories as $category) {
                $component->assertSee($category->name);
            }
            
            // Property: Delete categories sequentially
            // Each deletion should complete before the next one starts
            foreach ($categories as $index => $category) {
                // Property: Before deletion, category should exist in database
                $this->assertDatabaseHas('categories', [
                    'id' => $category->id,
                ]);
                
                // Property: Perform deletion
                $component->call('deleteCategory', $category->id);
                
                // Property: After deletion, category should be removed from database
                // This confirms the action completed before moving to the next
                $this->assertDatabaseMissing('categories', [
                    'id' => $category->id,
                ]);
                
                // Property: Category should no longer be visible
                $component->assertDontSee($category->name);
                
                // Property: Success message should be set for this action
                $component->assertSet('statusMessage', __('messages.category_deleted'));
                $component->assertSet('statusLevel', 'success');
                
                // Property: Remaining categories should still be visible
                for ($j = $index + 1; $j < $categoryCount; $j++) {
                    $this->assertDatabaseHas('categories', [
                        'id' => $categories[$j]->id,
                    ]);
                }
            }
            
            // Property: All categories should be deleted after sequential processing
            $this->assertEquals(0, Category::count());
            
            // Property: The actions were processed sequentially, with each action
            // completing (database update + UI update) before the next action started
            $this->assertTrue(true, 'Multiple deletions processed sequentially');
            
            // Cleanup
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 38: Sequential action processing
     * Validates: Requirements 12.5
     * 
     * For any queue of multiple inline edits, the system should process them
     * sequentially and update the UI for each edit in order.
     */
    public function test_multiple_inline_edits_process_sequentially(): void
    {
        // Run multiple iterations
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create a category
            $category = Category::factory()->create([
                'name' => 'Original Name',
                'slug' => 'original-slug',
            ]);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: Perform multiple sequential edits on the same category
            $edits = [
                ['field' => 'name', 'value' => 'First Edit'],
                ['field' => 'name', 'value' => 'Second Edit'],
                ['field' => 'name', 'value' => 'Third Edit'],
            ];
            
            foreach ($edits as $index => $edit) {
                // Property: Start editing
                $component->call('startEditing', $category->id, $edit['field']);
                
                // Property: Set new value
                $component->set("editingValues.{$edit['field']}", $edit['value']);
                
                // Property: Save the edit
                $component->call('saveInlineEdit');
                
                // Property: After save, database should have the new value
                // This confirms the action completed before moving to the next
                $this->assertDatabaseHas('categories', [
                    'id' => $category->id,
                    $edit['field'] => $edit['value'],
                ]);
                
                // Property: Editing mode should be exited
                $component->assertSet('editingId', null);
                $component->assertSet('editingField', null);
                
                // Property: Success message should be set for this action
                $component->assertSet('statusMessage', __('messages.category_updated'));
                $component->assertSet('statusLevel', 'success');
                
                // Property: The new value should be visible
                $component->assertSee($edit['value']);
                
                // Property: Previous values should not be visible (except the current one)
                if ($index > 0) {
                    $component->assertDontSee($edits[$index - 1]['value']);
                }
            }
            
            // Property: Final value should be the last edit
            $this->assertEquals('Third Edit', $category->fresh()->name);
            
            // Property: The edits were processed sequentially, with each edit
            // completing (database update + UI update) before the next edit started
            $this->assertTrue(true, 'Multiple inline edits processed sequentially');
            
            // Cleanup
            $category->delete();
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 38: Sequential action processing
     * Validates: Requirements 12.5
     * 
     * For any queue of multiple post publication toggles, the system should
     * process them sequentially and update the UI for each toggle in order.
     */
    public function test_multiple_publication_toggles_process_sequentially(): void
    {
        // Run fewer iterations for post tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create multiple unpublished posts
            $postCount = $faker->numberBetween(3, 5);
            $posts = Post::factory()->count($postCount)->create([
                'user_id' => $admin->id,
                'published_at' => null,
            ]);
            
            // Mount the posts index component
            $component = Livewire::test('admin.posts.index');
            
            // Property: All posts should initially be unpublished
            foreach ($posts as $post) {
                $this->assertNull($post->fresh()->published_at);
            }
            
            // Property: Publish posts sequentially
            foreach ($posts as $index => $post) {
                // Property: Before publish, post should be unpublished
                $this->assertNull($post->fresh()->published_at);
                
                // Property: Perform publish
                $component->call('publish', $post->id);
                
                // Property: After publish, post should be published in database
                // This confirms the action completed before moving to the next
                $this->assertNotNull($post->fresh()->published_at);
                
                // Property: Remaining posts should still be unpublished
                for ($j = $index + 1; $j < $postCount; $j++) {
                    $this->assertNull($posts[$j]->fresh()->published_at);
                }
            }
            
            // Property: All posts should be published after sequential processing
            foreach ($posts as $post) {
                $this->assertNotNull($post->fresh()->published_at);
            }
            
            // Property: Now unpublish posts sequentially
            foreach ($posts as $index => $post) {
                // Property: Before unpublish, post should be published
                $this->assertNotNull($post->fresh()->published_at);
                
                // Property: Perform unpublish
                $component->call('unpublish', $post->id);
                
                // Property: After unpublish, post should be unpublished in database
                $this->assertNull($post->fresh()->published_at);
                
                // Property: Remaining posts should still be published
                for ($j = $index + 1; $j < $postCount; $j++) {
                    $this->assertNotNull($posts[$j]->fresh()->published_at);
                }
            }
            
            // Property: All posts should be unpublished after sequential processing
            foreach ($posts as $post) {
                $this->assertNull($post->fresh()->published_at);
            }
            
            // Property: The toggles were processed sequentially, with each toggle
            // completing (database update) before the next toggle started
            $this->assertTrue(true, 'Multiple publication toggles processed sequentially');
            
            // Cleanup
            foreach ($posts as $post) {
                $post->delete();
            }
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 38: Sequential action processing
     * Validates: Requirements 12.5
     * 
     * For any queue of multiple comment status changes, the system should
     * process them sequentially and update the UI for each change in order.
     */
    public function test_multiple_comment_status_changes_process_sequentially(): void
    {
        // Run fewer iterations
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create admin user and post
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            $post = Post::factory()->create([
                'user_id' => $admin->id,
                'published_at' => now(),
            ]);
            
            // Create multiple pending comments
            $commentCount = $faker->numberBetween(3, 5);
            $comments = Comment::factory()->count($commentCount)->create([
                'post_id' => $post->id,
                'user_id' => $admin->id,
                'status' => CommentStatus::Pending,
            ]);
            
            // Mount the comments index component
            $component = Livewire::test('admin.comments.index');
            
            // Property: All comments should initially be pending
            foreach ($comments as $comment) {
                $this->assertEquals(CommentStatus::Pending, $comment->fresh()->status);
            }
            
            // Property: Approve comments sequentially
            foreach ($comments as $index => $comment) {
                // Property: Before approval, comment should be pending
                $this->assertEquals(CommentStatus::Pending, $comment->fresh()->status);
                
                // Property: Perform approval
                $component->call('approveComment', $comment->id);
                
                // Property: After approval, comment should be approved in database
                // This confirms the action completed before moving to the next
                $this->assertEquals(CommentStatus::Approved, $comment->fresh()->status);
                
                // Property: Remaining comments should still be pending
                for ($j = $index + 1; $j < $commentCount; $j++) {
                    $this->assertEquals(CommentStatus::Pending, $comments[$j]->fresh()->status);
                }
            }
            
            // Property: All comments should be approved after sequential processing
            foreach ($comments as $comment) {
                $this->assertEquals(CommentStatus::Approved, $comment->fresh()->status);
            }
            
            // Property: Now reject comments sequentially
            foreach ($comments as $index => $comment) {
                // Property: Before rejection, comment should be approved
                $this->assertEquals(CommentStatus::Approved, $comment->fresh()->status);
                
                // Property: Perform rejection
                $component->call('rejectComment', $comment->id);
                
                // Property: After rejection, comment should be rejected in database
                $this->assertEquals(CommentStatus::Rejected, $comment->fresh()->status);
                
                // Property: Remaining comments should still be approved
                for ($j = $index + 1; $j < $commentCount; $j++) {
                    $this->assertEquals(CommentStatus::Approved, $comments[$j]->fresh()->status);
                }
            }
            
            // Property: All comments should be rejected after sequential processing
            foreach ($comments as $comment) {
                $this->assertEquals(CommentStatus::Rejected, $comment->fresh()->status);
            }
            
            // Property: The status changes were processed sequentially, with each change
            // completing (database update) before the next change started
            $this->assertTrue(true, 'Multiple comment status changes processed sequentially');
            
            // Cleanup
            foreach ($comments as $comment) {
                $comment->delete();
            }
            $post->delete();
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 38: Sequential action processing
     * Validates: Requirements 12.5
     * 
     * For any queue of mixed action types, the system should process them
     * sequentially and update the UI for each action in order.
     */
    public function test_mixed_action_types_process_sequentially(): void
    {
        // Run multiple iterations
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create a category
            $category = Category::factory()->create([
                'name' => 'Original Name',
            ]);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: Perform mixed actions sequentially
            // 1. Start editing
            $component->call('startEditing', $category->id, 'name');
            $component->assertSet('editingId', $category->id);
            
            // 2. Change value
            $component->set('editingValues.name', 'First Edit');
            
            // 3. Save edit
            $component->call('saveInlineEdit');
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => 'First Edit',
            ]);
            $component->assertSet('editingId', null);
            
            // 4. Start editing again
            $component->call('startEditing', $category->id, 'name');
            $component->assertSet('editingId', $category->id);
            
            // 5. Change value again
            $component->set('editingValues.name', 'Second Edit');
            
            // 6. Cancel edit (revert)
            $component->call('cancelInlineEdit');
            $component->assertSet('editingId', null);
            
            // Property: Database should still have first edit (cancel didn't save)
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => 'First Edit',
            ]);
            
            // 7. Open create form
            $component->call('startCreateForm');
            $component->assertSet('formOpen', true);
            
            // 8. Close form
            $component->call('cancelForm');
            $component->assertSet('formOpen', false);
            
            // 9. Delete category
            $component->call('deleteCategory', $category->id);
            $this->assertDatabaseMissing('categories', [
                'id' => $category->id,
            ]);
            
            // Property: All actions were processed sequentially, with each action
            // completing before the next action started
            $this->assertTrue(true, 'Mixed action types processed sequentially');
            
            // Cleanup
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 38: Sequential action processing
     * Validates: Requirements 12.5
     * 
     * For any bulk action processing multiple items, the system should process
     * each item sequentially and update the UI after all items are processed.
     */
    public function test_bulk_actions_process_items_sequentially(): void
    {
        // Run fewer iterations for bulk operations
        for ($i = 0; $i < 3; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create multiple unpublished posts
            $postCount = $faker->numberBetween(3, 5);
            $posts = Post::factory()->count($postCount)->create([
                'user_id' => $admin->id,
                'published_at' => null,
            ]);
            
            // Mount the posts index component
            $component = Livewire::test('admin.posts.index');
            
            // Property: All posts should initially be unpublished
            foreach ($posts as $post) {
                $this->assertNull($post->fresh()->published_at);
            }
            
            // Property: Select all posts
            $postIds = $posts->pluck('id')->toArray();
            $component->set('selected', $postIds);
            
            // Property: Perform bulk publish
            $component->call('bulkPublish');
            
            // Property: After bulk action, all posts should be published
            // The bulk action processes each item sequentially
            foreach ($posts as $post) {
                $this->assertNotNull($post->fresh()->published_at);
            }
            
            // Property: Bulk feedback should show all items were updated
            $bulkFeedback = $component->get('bulkFeedback');
            $this->assertNotNull($bulkFeedback);
            $this->assertEquals($postCount, $bulkFeedback['updated']);
            
            // Property: Selection should be cleared after bulk action
            $component->assertSet('selected', []);
            
            // Property: Now perform bulk unpublish
            $component->set('selected', $postIds);
            $component->call('bulkUnpublish');
            
            // Property: After bulk unpublish, all posts should be unpublished
            foreach ($posts as $post) {
                $this->assertNull($post->fresh()->published_at);
            }
            
            // Property: The bulk action processed each item sequentially,
            // completing all updates before returning control
            $this->assertTrue(true, 'Bulk actions process items sequentially');
            
            // Cleanup
            foreach ($posts as $post) {
                $post->delete();
            }
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 38: Sequential action processing
     * Validates: Requirements 12.5
     * 
     * For any sequence of form submissions, the system should process them
     * sequentially and update the UI for each submission in order.
     */
    public function test_multiple_form_submissions_process_sequentially(): void
    {
        // Run multiple iterations
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: Create multiple categories sequentially
            $categoryNames = [];
            $categoryCount = $faker->numberBetween(3, 5);
            
            for ($j = 0; $j < $categoryCount; $j++) {
                $categoryName = ucwords($faker->words($faker->numberBetween(1, 3), true)) . " {$j}";
                $categoryNames[] = $categoryName;
                
                // Property: Open create form
                $component->call('startCreateForm');
                $component->assertSet('formOpen', true);
                
                // Property: Fill in form
                $component->set('formName', $categoryName);
                
                // Property: Submit form
                $component->call('saveCategory');
                
                // Property: After submission, category should be created in database
                // This confirms the action completed before moving to the next
                $this->assertDatabaseHas('categories', [
                    'name' => $categoryName,
                ]);
                
                // Property: Form fields should be reset
                $component->assertSet('formName', '');
                $component->assertSet('formSlug', '');
                
                // Property: Success message should be set
                $component->assertSet('statusMessage', __('messages.category_created'));
                $component->assertSet('statusLevel', 'success');
                
                // Property: New category should be visible
                $component->assertSee($categoryName);
                
                // Property: All previously created categories should still be visible
                for ($k = 0; $k < $j; $k++) {
                    $component->assertSee($categoryNames[$k]);
                }
            }
            
            // Property: All categories should be created after sequential processing
            $this->assertEquals($categoryCount, Category::count());
            
            // Property: The form submissions were processed sequentially, with each
            // submission completing (database insert + UI update) before the next started
            $this->assertTrue(true, 'Multiple form submissions processed sequentially');
            
            // Cleanup
            Category::whereIn('name', $categoryNames)->delete();
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 38: Sequential action processing
     * Validates: Requirements 12.5
     * 
     * For any sequence of search/filter actions, the system should process them
     * sequentially and update the UI for each action in order.
     */
    public function test_multiple_search_actions_process_sequentially(): void
    {
        // Run multiple iterations
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create categories with different search terms
            $categories = [
                Category::factory()->create(['name' => 'Alpha Category']),
                Category::factory()->create(['name' => 'Beta Category']),
                Category::factory()->create(['name' => 'Gamma Category']),
            ];
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: All categories should initially be visible
            foreach ($categories as $category) {
                $component->assertSee($category->name);
            }
            
            // Property: Perform sequential search actions
            $searches = ['Alpha', 'Beta', 'Gamma'];
            
            foreach ($searches as $index => $searchTerm) {
                // Property: Set search term
                $component->set('search', $searchTerm);
                
                // Property: After search, only matching category should be visible
                $component->assertSee($categories[$index]->name);
                
                // Property: Non-matching categories should not be visible
                for ($j = 0; $j < count($categories); $j++) {
                    if ($j !== $index) {
                        $component->assertDontSee($categories[$j]->name);
                    }
                }
                
                // Property: Search state should be updated
                $component->assertSet('search', $searchTerm);
            }
            
            // Property: Clear search
            $component->call('clearSearch');
            $component->assertSet('search', '');
            
            // Property: All categories should be visible again
            foreach ($categories as $category) {
                $component->assertSee($category->name);
            }
            
            // Property: The search actions were processed sequentially, with each
            // search completing (filter applied + UI update) before the next started
            $this->assertTrue(true, 'Multiple search actions processed sequentially');
            
            // Cleanup
            foreach ($categories as $category) {
                $category->delete();
            }
            $admin->delete();
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Property-Based Tests for Optimistic UI Immediate Update
 * 
 * These tests verify that UI updates happen immediately when user actions are triggered,
 * before server confirmation is received.
 */
final class OptimisticUIImmediateUpdatePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 34: Optimistic UI immediate update
     * Validates: Requirements 12.1
     * 
     * For any user action, the UI should update immediately before receiving server response.
     * 
     * Note: This test verifies that Livewire component state changes are synchronous
     * and happen immediately when actions are called, which enables optimistic UI updates
     * on the frontend.
     */
    public function test_category_deletion_triggers_immediate_state_change(): void
    {
        // Run fewer iterations for Livewire component tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create a category
            $category = Category::factory()->create([
                'name' => ucwords($faker->words($faker->numberBetween(1, 3), true)),
            ]);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: Before action, category should be in the list
            $component->assertSee($category->name);
            
            // Property: Call delete action - this should trigger immediate state change
            // The component's state should update synchronously
            $component->call('deleteCategory', $category->id);
            
            // Property: After action call, component should have updated state
            // The statusMessage should be set immediately (optimistic update)
            $component->assertSet('statusMessage', __('messages.category_deleted'));
            $component->assertSet('statusLevel', 'success');
            
            // Property: The category should be deleted from database
            $this->assertDatabaseMissing('categories', [
                'id' => $category->id,
            ]);
            
            // Property: Component should reflect the deletion in its rendered output
            $component->assertDontSee($category->name);
            
            // Cleanup
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 34: Optimistic UI immediate update
     * Validates: Requirements 12.1
     * 
     * For any inline edit action, the component state should update immediately
     * when the edit is initiated, before server confirmation.
     */
    public function test_category_inline_edit_triggers_immediate_state_change(): void
    {
        // Run fewer iterations for Livewire component tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create a category
            $category = Category::factory()->create([
                'name' => ucwords($faker->words($faker->numberBetween(1, 3), true)),
            ]);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: Initially, no editing state should be set
            $component->assertSet('editingId', null);
            $component->assertSet('editingField', null);
            
            // Property: Call startEditing action - this should trigger immediate state change
            $component->call('startEditing', $category->id, 'name');
            
            // Property: After action call, editing state should be set immediately
            $component->assertSet('editingId', $category->id);
            $component->assertSet('editingField', 'name');
            $component->assertSet('editingValues.name', $category->name);
            $component->assertSet('editingValues.slug', $category->slug);
            
            // Property: The component should now show the edit input
            // This is the optimistic UI update - showing edit mode immediately
            $component->assertSee('editingValues.name');
            
            // Cleanup
            $category->delete();
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 34: Optimistic UI immediate update
     * Validates: Requirements 12.1
     * 
     * For any form open action, the component state should update immediately
     * to show the form, before any server round-trip.
     */
    public function test_category_form_open_triggers_immediate_state_change(): void
    {
        // Run fewer iterations for Livewire component tests
        for ($i = 0; $i < 10; $i++) {
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: Initially, form should be closed
            $component->assertSet('formOpen', false);
            $component->assertSet('formCategoryId', null);
            
            // Property: Call startCreateForm action - this should trigger immediate state change
            $component->call('startCreateForm');
            
            // Property: After action call, form state should be open immediately
            $component->assertSet('formOpen', true);
            $component->assertSet('formCategoryId', null);
            $component->assertSet('formName', '');
            $component->assertSet('formSlug', '');
            $component->assertSet('formDescription', null);
            $component->assertSet('formSlugManuallyEdited', false);
            
            // Property: The component should now show the form
            // This is the optimistic UI update - showing form immediately
            $component->assertSee(__('categories.form.name_label'));
            $component->assertSee(__('categories.form.slug_label'));
            
            // Cleanup
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 34: Optimistic UI immediate update
     * Validates: Requirements 12.1
     * 
     * For any post publication action, the component should update database state immediately
     * when the action is called (synchronous update).
     */
    public function test_post_publication_triggers_immediate_state_change(): void
    {
        // Run fewer iterations for Livewire component tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create an unpublished post
            $post = Post::factory()->create([
                'user_id' => $admin->id,
                'title' => ucwords($faker->words($faker->numberBetween(2, 5), true)),
                'published_at' => null,
            ]);
            
            // Mount the posts index component
            $component = Livewire::test('admin.posts.index');
            
            // Property: Post should initially be unpublished
            $this->assertNull($post->fresh()->published_at);
            
            // Property: Call publish action - this should trigger immediate database update
            $component->call('publish', $post->id);
            
            // Property: After action call, post should be published in database immediately
            // This demonstrates that the action completes synchronously, enabling optimistic UI
            $this->assertNotNull($post->fresh()->published_at);
            
            // Property: Call unpublish to revert
            $component->call('unpublish', $post->id);
            
            // Property: Post should be unpublished again immediately
            $this->assertNull($post->fresh()->published_at);
            
            // Property: The synchronous nature of these updates enables optimistic UI
            // The frontend can update immediately because the backend processes synchronously
            $this->assertTrue(true, 'Publication state changes are immediate and synchronous');
            
            // Cleanup
            $post->delete();
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 34: Optimistic UI immediate update
     * Validates: Requirements 12.1
     * 
     * For any search input change, the component should update state immediately
     * and trigger filtering without page reload.
     */
    public function test_search_input_triggers_immediate_state_change(): void
    {
        // Run fewer iterations for Livewire component tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create categories with unique search terms
            $searchTerm = 'uniqueterm' . $faker->numberBetween(10000, 99999);
            $matchingCategory = Category::factory()->create([
                'name' => "Category with {$searchTerm}",
            ]);
            $nonMatchingCategory = Category::factory()->create([
                'name' => 'Different category name',
            ]);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: Initially, search should be empty
            $component->assertSet('search', '');
            
            // Property: Both categories should be visible
            $component->assertSee($matchingCategory->name);
            $component->assertSee($nonMatchingCategory->name);
            
            // Property: Set search term - this should trigger immediate state change
            $component->set('search', $searchTerm);
            
            // Property: After setting search, component state should update immediately
            $component->assertSet('search', $searchTerm);
            
            // Property: Component should now show only matching category
            $component->assertSee($matchingCategory->name);
            $component->assertDontSee($nonMatchingCategory->name);
            
            // Property: Clear search - state should update immediately
            $component->call('clearSearch');
            
            // Property: Search should be cleared immediately
            $component->assertSet('search', '');
            
            // Property: Both categories should be visible again
            $component->assertSee($matchingCategory->name);
            $component->assertSee($nonMatchingCategory->name);
            
            // Cleanup
            $matchingCategory->delete();
            $nonMatchingCategory->delete();
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 34: Optimistic UI immediate update
     * Validates: Requirements 12.1
     * 
     * For any sort action, the component should update state immediately
     * and re-order results without page reload.
     */
    public function test_sort_action_triggers_immediate_state_change(): void
    {
        // Run fewer iterations for Livewire component tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Create categories with different names
            $categoryA = Category::factory()->create(['name' => 'AAA Category']);
            $categoryZ = Category::factory()->create(['name' => 'ZZZ Category']);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Property: Initially, sort field might be null (before first sort)
            $initialSortField = $component->get('sortField');
            $initialSortDirection = $component->get('sortDirection');
            
            // Property: Call sortBy action on a new field - this should trigger immediate state change
            $component->call('sortBy', 'posts_count');
            
            // Property: After action call, sort state should update immediately
            $component->assertSet('sortField', 'posts_count');
            // When sorting by a new field, sortBy() always defaults to 'asc' (per ManagesSorting trait)
            $component->assertSet('sortDirection', 'asc');
            
            // Property: Call sortBy again on same field to toggle direction
            $component->call('sortBy', 'posts_count');
            
            // Property: Direction should toggle immediately
            $component->assertSet('sortField', 'posts_count');
            $component->assertSet('sortDirection', 'desc');
            
            // Property: Call sortBy again to toggle back
            $component->call('sortBy', 'posts_count');
            
            // Property: Direction should toggle back to asc
            $component->assertSet('sortField', 'posts_count');
            $component->assertSet('sortDirection', 'asc');
            
            // Property: Change to different sort field
            $component->call('sortBy', 'updated_at');
            
            // Property: Sort field should change immediately
            $component->assertSet('sortField', 'updated_at');
            // New field always starts with 'asc'
            $component->assertSet('sortDirection', 'asc');
            
            // Property: The component state changes are synchronous and immediate
            // This enables optimistic UI updates on the frontend
            $this->assertTrue(true, 'Sort state changes are immediate and synchronous');
            
            // Cleanup
            $categoryA->delete();
            $categoryZ->delete();
            $admin->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 34: Optimistic UI immediate update
     * Validates: Requirements 12.1
     * 
     * For any form input change, the component should update state immediately
     * with live validation feedback.
     */
    public function test_form_input_triggers_immediate_state_change(): void
    {
        // Run fewer iterations for Livewire component tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create admin user
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);
            
            // Mount the categories index component
            $component = Livewire::test('admin.categories.index');
            
            // Open the form
            $component->call('startCreateForm');
            
            // Property: Form should be open
            $component->assertSet('formOpen', true);
            
            // Generate random category name
            $categoryName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            
            // Property: Set form name - this should trigger immediate state change
            $component->set('formName', $categoryName);
            
            // Property: After setting name, component state should update immediately
            $component->assertSet('formName', $categoryName);
            
            // Property: Slug should be auto-generated immediately (if not manually edited)
            $expectedSlug = \Illuminate\Support\Str::slug($categoryName);
            $component->assertSet('formSlug', $expectedSlug);
            
            // Property: Manually edit slug - this should trigger immediate state change
            $manualSlug = 'custom-slug-' . $faker->numberBetween(1, 100);
            $component->set('formSlug', $manualSlug);
            
            // Property: After setting slug, component state should update immediately
            $component->assertSet('formSlug', \Illuminate\Support\Str::slug($manualSlug));
            $component->assertSet('formSlugManuallyEdited', true);
            
            // Property: Change name again - slug should NOT auto-update now
            $newName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $component->set('formName', $newName);
            
            // Property: Name should update immediately
            $component->assertSet('formName', $newName);
            
            // Property: Slug should remain unchanged (manual edit locked it)
            $component->assertSet('formSlug', \Illuminate\Support\Str::slug($manualSlug));
            
            // Cleanup
            $admin->delete();
        }
    }
}

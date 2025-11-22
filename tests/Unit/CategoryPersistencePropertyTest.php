<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Property-Based Tests for Category Data Persistence
 * 
 * These tests verify universal properties for category data persistence (round-trip).
 */
final class CategoryPersistencePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip
     * Validates: Requirements 2.5
     * 
     * For any category and any valid data, creating or updating the category
     * should result in the data being persisted to the database and displayed
     * correctly in the table view.
     */
    public function test_category_creation_persistence_round_trip(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Generate random category data
            $categoryName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $categorySlug = Str::slug($categoryName);
            $categoryDescription = $faker->optional()->sentence();
            
            // Property: Create a category with specific data
            $category = Category::factory()->create([
                'name' => $categoryName,
                'slug' => $categorySlug,
                'description' => $categoryDescription,
            ]);
            
            // Property: Category should exist in database with exact data
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => $categoryName,
                'slug' => $categorySlug,
                'description' => $categoryDescription,
            ]);
            
            // Property: Retrieving the category should return the same data
            $retrievedCategory = Category::find($category->id);
            $this->assertNotNull($retrievedCategory, "Category should be retrievable by ID");
            $this->assertSame($category->id, $retrievedCategory->id, "Retrieved category should have same ID");
            $this->assertSame($categoryName, $retrievedCategory->name, "Retrieved category should have same name");
            $this->assertSame($categorySlug, $retrievedCategory->slug, "Retrieved category should have same slug");
            $this->assertSame($categoryDescription, $retrievedCategory->description, "Retrieved category should have same description");
            
            // Property: Finding by slug should return the same category
            $foundBySlug = Category::where('slug', $categorySlug)->first();
            $this->assertNotNull($foundBySlug, "Category should be findable by slug");
            $this->assertSame($category->id, $foundBySlug->id, "Category found by slug should have same ID");
            $this->assertSame($categoryName, $foundBySlug->name, "Category found by slug should have same name");
            
            // Property: All attributes should match after round-trip
            $this->assertEquals($category->toArray(), $retrievedCategory->toArray(), "All attributes should match after round-trip");
            
            // Cleanup
            $category->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (update)
     * Validates: Requirements 2.5
     * 
     * For any category, updating the category data should persist the changes
     * and return the updated data on retrieval.
     */
    public function test_category_update_persistence_round_trip(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create initial category
            $initialName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $initialSlug = Str::slug($initialName);
            $initialDescription = $faker->sentence();
            
            $category = Category::factory()->create([
                'name' => $initialName,
                'slug' => $initialSlug,
                'description' => $initialDescription,
            ]);
            
            // Property: Initial data should be persisted
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => $initialName,
                'slug' => $initialSlug,
            ]);
            
            // Generate new data for update
            $updatedName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $updatedSlug = Str::slug($updatedName);
            $updatedDescription = $faker->sentence();
            
            // Property: Update the category
            $category->update([
                'name' => $updatedName,
                'slug' => $updatedSlug,
                'description' => $updatedDescription,
            ]);
            
            // Property: Updated data should be persisted in database
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => $updatedName,
                'slug' => $updatedSlug,
                'description' => $updatedDescription,
            ]);
            
            // Property: Old data should no longer exist
            $this->assertDatabaseMissing('categories', [
                'id' => $category->id,
                'name' => $initialName,
                'slug' => $initialSlug,
            ]);
            
            // Property: Retrieving the category should return updated data
            $retrievedCategory = Category::find($category->id);
            $this->assertSame($updatedName, $retrievedCategory->name, "Retrieved category should have updated name");
            $this->assertSame($updatedSlug, $retrievedCategory->slug, "Retrieved category should have updated slug");
            $this->assertSame($updatedDescription, $retrievedCategory->description, "Retrieved category should have updated description");
            
            // Property: Finding by new slug should work
            $foundByNewSlug = Category::where('slug', $updatedSlug)->first();
            $this->assertNotNull($foundByNewSlug, "Category should be findable by new slug");
            $this->assertSame($category->id, $foundByNewSlug->id, "Category found by new slug should have same ID");
            
            // Property: Finding by old slug should return nothing
            $foundByOldSlug = Category::where('slug', $initialSlug)->first();
            $this->assertNull($foundByOldSlug, "Category should not be findable by old slug");
            
            // Cleanup
            $category->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (partial update)
     * Validates: Requirements 2.5
     * 
     * For any category, updating only some fields should persist those changes
     * while keeping other fields unchanged.
     */
    public function test_category_partial_update_persistence(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create initial category
            $initialName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $initialSlug = Str::slug($initialName);
            $initialDescription = $faker->sentence();
            
            $category = Category::factory()->create([
                'name' => $initialName,
                'slug' => $initialSlug,
                'description' => $initialDescription,
            ]);
            
            // Property: Update only the description
            $updatedDescription = $faker->sentence();
            $category->update([
                'description' => $updatedDescription,
            ]);
            
            // Property: Description should be updated
            $retrievedCategory = Category::find($category->id);
            $this->assertSame($updatedDescription, $retrievedCategory->description, "Description should be updated");
            
            // Property: Name and slug should remain unchanged
            $this->assertSame($initialName, $retrievedCategory->name, "Name should remain unchanged");
            $this->assertSame($initialSlug, $retrievedCategory->slug, "Slug should remain unchanged");
            
            // Property: Update only the name (and slug)
            $updatedName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $updatedSlug = Str::slug($updatedName);
            $category->update([
                'name' => $updatedName,
                'slug' => $updatedSlug,
            ]);
            
            // Property: Name and slug should be updated
            $retrievedCategory = Category::find($category->id);
            $this->assertSame($updatedName, $retrievedCategory->name, "Name should be updated");
            $this->assertSame($updatedSlug, $retrievedCategory->slug, "Slug should be updated");
            
            // Property: Description should remain from previous update
            $this->assertSame($updatedDescription, $retrievedCategory->description, "Description should remain from previous update");
            
            // Cleanup
            $category->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (null description)
     * Validates: Requirements 2.5
     * 
     * For any category with a null description, the null value should be
     * persisted and retrieved correctly.
     */
    public function test_category_persistence_with_null_description(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create category with null description
            $categoryName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $categorySlug = Str::slug($categoryName);
            
            $category = Category::factory()->create([
                'name' => $categoryName,
                'slug' => $categorySlug,
                'description' => null,
            ]);
            
            // Property: Category should be persisted with null description
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => $categoryName,
                'slug' => $categorySlug,
                'description' => null,
            ]);
            
            // Property: Retrieved category should have null description
            $retrievedCategory = Category::find($category->id);
            $this->assertNull($retrievedCategory->description, "Retrieved category should have null description");
            
            // Property: Update to add description
            $newDescription = $faker->sentence();
            $category->update(['description' => $newDescription]);
            
            $retrievedCategory = Category::find($category->id);
            $this->assertSame($newDescription, $retrievedCategory->description, "Description should be updated from null");
            
            // Property: Update back to null
            $category->update(['description' => null]);
            
            $retrievedCategory = Category::find($category->id);
            $this->assertNull($retrievedCategory->description, "Description should be updated back to null");
            
            // Cleanup
            $category->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (timestamps)
     * Validates: Requirements 2.5
     * 
     * For any category, created_at and updated_at timestamps should be
     * automatically managed and persisted correctly.
     */
    public function test_category_persistence_with_timestamps(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create category
            $category = Category::factory()->create();
            
            // Property: created_at should be set
            $this->assertNotNull($category->created_at, "created_at should be set");
            
            // Property: updated_at should be set
            $this->assertNotNull($category->updated_at, "updated_at should be set");
            
            // Property: created_at and updated_at should be equal on creation
            $this->assertEquals(
                $category->created_at->timestamp,
                $category->updated_at->timestamp,
                "created_at and updated_at should be equal on creation",
                1  // Allow 1 second difference
            );
            
            // Store original timestamps
            $originalCreatedAt = $category->created_at;
            $originalUpdatedAt = $category->updated_at;
            
            // Wait a moment to ensure timestamp difference
            sleep(1);
            
            // Property: Update the category
            $category->update(['name' => ucwords($faker->words(2, true))]);
            
            // Property: created_at should remain unchanged
            $this->assertEquals(
                $originalCreatedAt->timestamp,
                $category->fresh()->created_at->timestamp,
                "created_at should remain unchanged after update"
            );
            
            // Property: updated_at should be newer than original
            $this->assertGreaterThan(
                $originalUpdatedAt->timestamp,
                $category->fresh()->updated_at->timestamp,
                "updated_at should be newer after update"
            );
            
            // Cleanup
            $category->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (special characters)
     * Validates: Requirements 2.5
     * 
     * For any category with special characters in name or description,
     * the data should be persisted and retrieved correctly without corruption.
     */
    public function test_category_persistence_with_special_characters(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Generate names with special characters
            $specialNames = [
                "Technology & Innovation",
                "Arts & Crafts",
                "Food & Drink",
                "Health & Wellness",
                "Travel & Adventure",
                "Science & Nature",
                "Business & Finance",
                "Sports & Fitness",
                "Music & Entertainment",
                "Books & Literature",
            ];
            
            $categoryName = $faker->randomElement($specialNames);
            $categorySlug = Str::slug($categoryName);
            $categoryDescription = $faker->sentence() . " & more!";
            
            // Property: Create category with special characters
            $category = Category::factory()->create([
                'name' => $categoryName,
                'slug' => $categorySlug,
                'description' => $categoryDescription,
            ]);
            
            // Property: Data should be persisted exactly as provided
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => $categoryName,
                'slug' => $categorySlug,
            ]);
            
            // Property: Retrieved data should match exactly
            $retrievedCategory = Category::find($category->id);
            $this->assertSame($categoryName, $retrievedCategory->name, "Name with special characters should be preserved");
            $this->assertSame($categoryDescription, $retrievedCategory->description, "Description with special characters should be preserved");
            
            // Property: Slug should not contain special characters
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $retrievedCategory->slug,
                "Slug should not contain special characters"
            );
            
            // Cleanup
            $category->delete();
        }
    }
}

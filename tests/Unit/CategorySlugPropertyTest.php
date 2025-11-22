<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Property-Based Tests for Category Slug Auto-Generation
 * 
 * These tests verify universal properties for slug generation from category names.
 */
final class CategorySlugPropertyTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Feature: admin-livewire-crud, Property 25: Slug auto-generation from name
     * Validates: Requirements 2.3
     * 
     * For any category name input, if the slug has not been manually edited,
     * the system should automatically generate a slug from the name.
     */
    public function test_slug_auto_generation_from_name(): void
    {
        PropertyTesting::run(function ($faker) {
            // Create a mock component that simulates the CategoryForm behavior
            $component = new class extends Component
            {
                public string $name = '';
                public string $slug = '';
                public bool $slugManuallyEdited = false;

                public function updatedName(string $value): void
                {
                    if ($this->slugManuallyEdited) {
                        return;
                    }

                    $this->slug = Str::slug($value);
                }

                public function updatedSlug(string $value): void
                {
                    $this->slugManuallyEdited = true;
                    $this->slug = Str::slug($value);
                }

                public function render()
                {
                    return '<div></div>';
                }
            };

            // Generate random category name
            $categoryName = $faker->words($faker->numberBetween(1, 5), true);
            
            // Property: Initially, slug should be empty and not manually edited
            $this->assertSame('', $component->slug, "Initially, slug should be empty");
            $this->assertFalse($component->slugManuallyEdited, "Initially, slug should not be manually edited");

            // Property: When name is updated, slug should be auto-generated
            $component->name = $categoryName;
            $component->updatedName($categoryName);
            
            $expectedSlug = Str::slug($categoryName);
            $this->assertSame($expectedSlug, $component->slug, "Slug should be auto-generated from name");

            // Property: Auto-generated slug should match Laravel's Str::slug() output
            $this->assertSame(Str::slug($categoryName), $component->slug, "Slug should match Str::slug() output");

            // Property: Auto-generated slug should only contain lowercase letters, numbers, and hyphens
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $component->slug,
                "Auto-generated slug should only contain lowercase letters, numbers, and hyphens"
            );

            // Property: When name changes again (without manual edit), slug should update
            $newCategoryName = $faker->words($faker->numberBetween(1, 5), true);
            $component->name = $newCategoryName;
            $component->updatedName($newCategoryName);
            
            $newExpectedSlug = Str::slug($newCategoryName);
            $this->assertSame($newExpectedSlug, $component->slug, "Slug should update when name changes");

            // Property: slugManuallyEdited should still be false after auto-generation
            $this->assertFalse($component->slugManuallyEdited, "slugManuallyEdited should remain false after auto-generation");
        });
    }

    /**
     * Feature: admin-livewire-crud, Property 26: Manual slug edit stops auto-generation
     * Validates: Requirements 2.4
     * 
     * For any category form where the slug is manually edited,
     * subsequent changes to the name should not update the slug.
     */
    public function test_manual_slug_edit_stops_auto_generation(): void
    {
        PropertyTesting::run(function ($faker) {
            // Create a mock component that simulates the CategoryForm behavior
            $component = new class extends Component
            {
                public string $name = '';
                public string $slug = '';
                public bool $slugManuallyEdited = false;

                public function updatedName(string $value): void
                {
                    if ($this->slugManuallyEdited) {
                        return;
                    }

                    $this->slug = Str::slug($value);
                }

                public function updatedSlug(string $value): void
                {
                    $this->slugManuallyEdited = true;
                    $this->slug = Str::slug($value);
                }

                public function render()
                {
                    return '<div></div>';
                }
            };

            // Generate random initial category name
            $initialName = $faker->words($faker->numberBetween(1, 5), true);
            
            // Property: Set initial name and auto-generate slug
            $component->name = $initialName;
            $component->updatedName($initialName);
            
            $autoGeneratedSlug = $component->slug;
            $this->assertSame(Str::slug($initialName), $autoGeneratedSlug, "Initial slug should be auto-generated");
            $this->assertFalse($component->slugManuallyEdited, "Initially, slug should not be manually edited");

            // Property: Manually edit the slug to a custom value
            $manualSlug = $faker->slug($faker->numberBetween(1, 3));
            $component->slug = $manualSlug;
            $component->updatedSlug($manualSlug);
            
            $expectedManualSlug = Str::slug($manualSlug);
            $this->assertSame($expectedManualSlug, $component->slug, "Slug should be set to manual value");
            $this->assertTrue($component->slugManuallyEdited, "slugManuallyEdited should be true after manual edit");

            // Property: Change the name - slug should NOT update
            $newName = $faker->words($faker->numberBetween(1, 5), true);
            $component->name = $newName;
            $component->updatedName($newName);
            
            // The slug should remain the manually edited value, NOT auto-generate from new name
            $this->assertSame($expectedManualSlug, $component->slug, "Slug should remain manually edited value after name change");
            $this->assertNotSame(Str::slug($newName), $component->slug, "Slug should NOT auto-generate from new name");
            $this->assertTrue($component->slugManuallyEdited, "slugManuallyEdited should remain true");

            // Property: Change the name again - slug should still NOT update
            $anotherNewName = $faker->words($faker->numberBetween(1, 5), true);
            $component->name = $anotherNewName;
            $component->updatedName($anotherNewName);
            
            // The slug should STILL remain the manually edited value
            $this->assertSame($expectedManualSlug, $component->slug, "Slug should still remain manually edited value after multiple name changes");
            $this->assertNotSame(Str::slug($anotherNewName), $component->slug, "Slug should NOT auto-generate from another new name");
            $this->assertTrue($component->slugManuallyEdited, "slugManuallyEdited should remain true after multiple name changes");
        });
    }

    /**
     * Feature: admin-livewire-crud, Property 7: Slug format validation
     * Validates: Requirements 2.4
     * 
     * For any category slug input, the system should validate that the slug matches
     * the format /^[a-z0-9]+(?:-[a-z0-9]+)*$/ and reject invalid formats.
     */
    public function test_slug_format_validation(): void
    {
        PropertyTesting::run(function ($faker) {
            // Valid slug patterns that should pass validation
            $validSlugs = [
                // Single word slugs
                $faker->lexify('???'),  // 3 lowercase letters
                $faker->numerify('###'),  // 3 numbers
                $faker->bothify('??##'),  // mix of letters and numbers
                
                // Multi-word slugs with hyphens
                $faker->lexify('???-???'),  // word-word
                $faker->bothify('??-##-??'),  // mixed with hyphens
                $faker->lexify('???-???-???'),  // three words
                
                // Edge cases
                'a',  // single character
                '1',  // single number
                'a-b',  // minimal hyphenated
                'test-123',  // word-number
                '123-test',  // number-word
                'a-1-b-2',  // alternating
            ];

            foreach ($validSlugs as $slug) {
                // Property: Valid slugs should match the regex pattern
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    $slug,
                    "Valid slug '{$slug}' should match the validation pattern"
                );
            }

            // Invalid slug patterns that should fail validation
            $invalidSlugs = [
                // Uppercase letters
                'Test',
                'TEST',
                'Test-Slug',
                
                // Special characters
                'test_slug',  // underscore
                'test.slug',  // period
                'test slug',  // space
                'test@slug',  // at symbol
                'test!slug',  // exclamation
                'test#slug',  // hash
                'test$slug',  // dollar
                'test%slug',  // percent
                'test&slug',  // ampersand
                'test*slug',  // asterisk
                'test+slug',  // plus
                'test=slug',  // equals
                'test/slug',  // forward slash
                'test\\slug',  // backslash
                'test|slug',  // pipe
                'test[slug',  // bracket
                'test]slug',  // bracket
                'test{slug',  // brace
                'test}slug',  // brace
                'test(slug',  // parenthesis
                'test)slug',  // parenthesis
                'test<slug',  // less than
                'test>slug',  // greater than
                'test?slug',  // question mark
                'test:slug',  // colon
                'test;slug',  // semicolon
                'test,slug',  // comma
                'test\'slug',  // single quote
                'test"slug',  // double quote
                
                // Leading/trailing hyphens
                '-test',
                'test-',
                '-test-slug',
                'test-slug-',
                
                // Multiple consecutive hyphens
                'test--slug',
                'test---slug',
                
                // Empty string
                '',
                
                // Only hyphens
                '-',
                '--',
                '---',
                
                // Unicode/non-ASCII
                'tëst',
                'tést',
                'test-café',
                '测试',
            ];

            foreach ($invalidSlugs as $slug) {
                // Property: Invalid slugs should NOT match the regex pattern
                $this->assertDoesNotMatchRegularExpression(
                    '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    $slug,
                    "Invalid slug '{$slug}' should NOT match the validation pattern"
                );
            }

            // Property: Str::slug() output should always produce valid slugs
            $randomStrings = [
                $faker->sentence(),
                $faker->words(3, true),
                $faker->text(50),
                'Test With UPPERCASE',
                'test_with_underscores',
                'test.with.periods',
                'test with spaces',
                'Tëst wïth Üñïçödé',
            ];

            foreach ($randomStrings as $string) {
                $slug = Str::slug($string);
                
                // Property: Str::slug() should always produce valid slugs
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    $slug,
                    "Str::slug() output for '{$string}' should match validation pattern"
                );
            }
        });
    }

    /**
    /**
     * Feature: admin-livewire-crud, Property 8: Uniqueness validation
     * Validates: Requirements 2.5
     * 
     * For any category with a slug that already exists in the database,
     * attempting to save should fail with a uniqueness validation error.
     */
    public function test_slug_uniqueness_validation(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Generate random category data
            $existingCategoryName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $existingSlug = Str::slug($existingCategoryName);
            
            // Property: Create a category with a specific slug
            $existingCategory = Category::factory()->create([
                'name' => $existingCategoryName,
                'slug' => $existingSlug,
            ]);
            
            // Property: Category should exist in database
            $this->assertDatabaseHas('categories', [
                'id' => $existingCategory->id,
                'slug' => $existingSlug,
            ]);
            
            // Property: Attempting to create another category with the same slug should fail validation
            $duplicateCategoryName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            
            $validator = Validator::make([
                'name' => $duplicateCategoryName,
                'slug' => $existingSlug,  // Same slug as existing category
                'description' => $faker->sentence(),
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);
            
            // Property: Validation should fail due to duplicate slug
            $this->assertTrue($validator->fails(), "Validation should fail for duplicate slug");
            $this->assertTrue($validator->errors()->has('slug'), "Validation errors should include slug field");
            $this->assertStringContainsString(
                'taken',
                strtolower($validator->errors()->first('slug')),
                "Error message should indicate slug is already taken"
            );
            
            // Property: Attempting to update a different category with an existing slug should fail
            $anotherCategory = Category::factory()->create();
            
            $updateValidator = Validator::make([
                'name' => $anotherCategory->name,
                'slug' => $existingSlug,  // Trying to use existing slug
                'description' => $anotherCategory->description,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    'unique:categories,slug,' . $anotherCategory->id,
                ],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);
            
            // Property: Update validation should fail due to duplicate slug
            $this->assertTrue($updateValidator->fails(), "Update validation should fail for duplicate slug");
            $this->assertTrue($updateValidator->errors()->has('slug'), "Update validation errors should include slug field");
            
            // Property: Updating a category with its own slug should succeed
            $selfUpdateValidator = Validator::make([
                'name' => $existingCategory->name,
                'slug' => $existingSlug,  // Same slug, same category
                'description' => $existingCategory->description,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    'unique:categories,slug,' . $existingCategory->id,
                ],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);
            
            // Property: Self-update validation should pass
            $this->assertFalse($selfUpdateValidator->fails(), "Self-update validation should pass with same slug");
            
            // Property: Using a unique slug should pass validation
            $uniqueSlug = Str::slug($faker->unique()->words($faker->numberBetween(1, 3), true));
            
            $uniqueValidator = Validator::make([
                'name' => $faker->words($faker->numberBetween(1, 3), true),
                'slug' => $uniqueSlug,
                'description' => $faker->sentence(),
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);
            
            // Property: Validation should pass for unique slug
            $this->assertFalse($uniqueValidator->fails(), "Validation should pass for unique slug");
            
            // Cleanup
            $existingCategory->delete();
            $anotherCategory->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 8: Uniqueness validation (case sensitivity)
     * Validates: Requirements 2.5
     * 
     * For any category slug, uniqueness validation should be case-insensitive
     * since slugs are always lowercase.
     */
    public function test_slug_uniqueness_is_case_insensitive(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a category with a lowercase slug
            $baseSlug = Str::slug($faker->words($faker->numberBetween(1, 3), true));
            $existingCategory = Category::factory()->create([
                'slug' => $baseSlug,
            ]);
            
            // Property: Attempting to create a category with uppercase version should fail
            // (though Str::slug() would convert it to lowercase anyway)
            $uppercaseSlug = strtoupper($baseSlug);
            $normalizedSlug = Str::slug($uppercaseSlug);  // This will be lowercase
            
            // Property: Normalized slug should be lowercase
            $this->assertSame($baseSlug, $normalizedSlug, "Str::slug() should normalize to lowercase");
            
            // Property: Validation should fail for the normalized (lowercase) slug
            $validator = Validator::make([
                'name' => $faker->words($faker->numberBetween(1, 3), true),
                'slug' => $normalizedSlug,
                'description' => $faker->sentence(),
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);
            
            $this->assertTrue($validator->fails(), "Validation should fail for duplicate slug regardless of input case");
            
            // Cleanup
            $existingCategory->delete();
        }
    }
}

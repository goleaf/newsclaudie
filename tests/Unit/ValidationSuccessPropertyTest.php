<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Property-Based Tests for Validation Success
 * 
 * These tests verify that when all fields contain valid data, validation passes
 * and all error indicators are removed, enabling form submission.
 */
final class ValidationSuccessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 6: Validation success enables submission
     * Validates: Requirements 10.5
     * 
     * For any form with all fields containing valid data, the system should remove
     * all error indicators and allow form submission.
     */
    public function test_category_form_passes_validation_with_all_valid_data(): void
    {
        PropertyTesting::run(function ($faker) {
            // Generate valid category data
            $validData = [
                'name' => ucwords($faker->words(2, true)),
                'slug' => Str::slug($faker->words(2, true)),
                'description' => $faker->optional()->sentence(),
            ];

            // Validate with all valid data
            $validator = Validator::make($validData, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should pass for all valid data
            $this->assertFalse($validator->fails(), "Validation should pass when all fields contain valid data");
            
            // Property: There should be no error messages
            $this->assertCount(0, $validator->errors()->all(), "There should be no error messages when validation passes");
            
            // Property: No individual fields should have errors
            $this->assertFalse($validator->errors()->has('name'), "Should not have error for 'name' field");
            $this->assertFalse($validator->errors()->has('slug'), "Should not have error for 'slug' field");
            $this->assertFalse($validator->errors()->has('description'), "Should not have error for 'description' field");
            
            // Property: Validated data should be available for submission
            $validated = $validator->validated();
            $this->assertIsArray($validated, "Validated data should be available as an array");
            $this->assertArrayHasKey('name', $validated, "Validated data should include 'name'");
            $this->assertArrayHasKey('slug', $validated, "Validated data should include 'slug'");
            
            // Property: The validated data should match the input data
            $this->assertSame($validData['name'], $validated['name'], "Validated name should match input");
            $this->assertSame($validData['slug'], $validated['slug'], "Validated slug should match input");
        }, 100);
    }

    /**
     * Feature: admin-livewire-crud, Property 6: Validation success enables submission (Post validation)
     * Validates: Requirements 10.5
     * 
     * For any post form with all valid data, validation should pass without errors.
     */
    public function test_post_form_passes_validation_with_all_valid_data(): void
    {
        PropertyTesting::run(function ($faker) {
            // Create categories for relationship testing
            $category1 = Category::factory()->create();
            $category2 = Category::factory()->create();

            // Generate valid post data
            $validData = [
                'title' => $faker->sentence(),
                'body' => $faker->paragraphs(3, true),
                'description' => $faker->optional()->sentence(),
                'featured_image' => $faker->optional()->imageUrl(),
                'tags' => $faker->optional()->words(3),
                'categories' => $faker->optional()->randomElements([$category1->id, $category2->id], 2),
            ];

            // Validate with all valid data
            $validator = Validator::make($validData, [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'description' => ['nullable', 'string', 'max:255'],
                'featured_image' => ['nullable', 'url', 'max:255'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
                'categories' => ['nullable', 'array'],
                'categories.*' => ['integer', 'exists:categories,id'],
            ]);

            // Property: Validation should pass for all valid data
            $this->assertFalse($validator->fails(), "Validation should pass when all post fields contain valid data");
            
            // Property: There should be no error messages
            $this->assertCount(0, $validator->errors()->all(), "There should be no error messages when validation passes");
            
            // Property: No individual fields should have errors
            $this->assertFalse($validator->errors()->has('title'), "Should not have error for 'title' field");
            $this->assertFalse($validator->errors()->has('body'), "Should not have error for 'body' field");
            $this->assertFalse($validator->errors()->has('description'), "Should not have error for 'description' field");
            $this->assertFalse($validator->errors()->has('featured_image'), "Should not have error for 'featured_image' field");
            $this->assertFalse($validator->errors()->has('tags'), "Should not have error for 'tags' field");
            $this->assertFalse($validator->errors()->has('categories'), "Should not have error for 'categories' field");
            
            // Property: Validated data should be available for submission
            $validated = $validator->validated();
            $this->assertIsArray($validated, "Validated data should be available as an array");
            $this->assertArrayHasKey('title', $validated, "Validated data should include 'title'");
            $this->assertArrayHasKey('body', $validated, "Validated data should include 'body'");
            
            // Property: The validated data should match the input data for required fields
            $this->assertSame($validData['title'], $validated['title'], "Validated title should match input");
            $this->assertSame($validData['body'], $validated['body'], "Validated body should match input");

            // Clean up
            $category1->delete();
            $category2->delete();
        }, 10);  // Run fewer iterations for database tests
    }

    /**
     * Feature: admin-livewire-crud, Property 6: Validation success enables submission (Comment validation)
     * Validates: Requirements 10.5
     * 
     * For any comment form with all valid data, validation should pass without errors.
     */
    public function test_comment_form_passes_validation_with_all_valid_data(): void
    {
        PropertyTesting::run(function ($faker) {
            // Generate valid comment data
            $validData = [
                'content' => $faker->paragraph(),
            ];

            // Validate with all valid data
            $validator = Validator::make($validData, [
                'content' => ['required', 'string', 'max:1024'],
            ]);

            // Property: Validation should pass for all valid data
            $this->assertFalse($validator->fails(), "Validation should pass when comment content is valid");
            
            // Property: There should be no error messages
            $this->assertCount(0, $validator->errors()->all(), "There should be no error messages when validation passes");
            
            // Property: The 'content' field should not have errors
            $this->assertFalse($validator->errors()->has('content'), "Should not have error for 'content' field");
            
            // Property: Validated data should be available for submission
            $validated = $validator->validated();
            $this->assertIsArray($validated, "Validated data should be available as an array");
            $this->assertArrayHasKey('content', $validated, "Validated data should include 'content'");
            
            // Property: The validated data should match the input data
            $this->assertSame($validData['content'], $validated['content'], "Validated content should match input");
        }, 100);
    }

    /**
     * Feature: admin-livewire-crud, Property 6: Validation success enables submission (User validation)
     * Validates: Requirements 10.5
     * 
     * For any user form with all valid data, validation should pass without errors.
     */
    public function test_user_form_passes_validation_with_all_valid_data(): void
    {
        PropertyTesting::run(function ($faker) {
            // Generate valid user data
            $validData = [
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => $faker->password(8, 20),
            ];

            // Validate with all valid data
            $validator = Validator::make($validData, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: Validation should pass for all valid data
            $this->assertFalse($validator->fails(), "Validation should pass when all user fields contain valid data");
            
            // Property: There should be no error messages
            $this->assertCount(0, $validator->errors()->all(), "There should be no error messages when validation passes");
            
            // Property: No individual fields should have errors
            $this->assertFalse($validator->errors()->has('name'), "Should not have error for 'name' field");
            $this->assertFalse($validator->errors()->has('email'), "Should not have error for 'email' field");
            $this->assertFalse($validator->errors()->has('password'), "Should not have error for 'password' field");
            
            // Property: Validated data should be available for submission
            $validated = $validator->validated();
            $this->assertIsArray($validated, "Validated data should be available as an array");
            $this->assertArrayHasKey('name', $validated, "Validated data should include 'name'");
            $this->assertArrayHasKey('email', $validated, "Validated data should include 'email'");
            $this->assertArrayHasKey('password', $validated, "Validated data should include 'password'");
            
            // Property: The validated data should match the input data
            $this->assertSame($validData['name'], $validated['name'], "Validated name should match input");
            $this->assertSame($validData['email'], $validated['email'], "Validated email should match input");
            $this->assertSame($validData['password'], $validated['password'], "Validated password should match input");
        }, 10);  // Run fewer iterations for uniqueness validation
    }

    /**
     * Feature: admin-livewire-crud, Property 6: Validation success enables submission (Optional fields)
     * Validates: Requirements 10.5
     * 
     * For any form with optional fields, validation should pass when optional fields
     * are omitted or contain valid data.
     */
    public function test_validation_passes_with_optional_fields_omitted_or_valid(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Optional fields omitted
            $dataWithoutOptionals = [
                'name' => ucwords($faker->words(2, true)),
                'slug' => Str::slug($faker->words(2, true)),
            ];

            $validator1 = Validator::make($dataWithoutOptionals, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should pass when optional fields are omitted
            $this->assertFalse($validator1->fails(), "Validation should pass when optional fields are omitted");
            $this->assertCount(0, $validator1->errors()->all(), "There should be no errors when optional fields are omitted");

            // Test Case 2: Optional fields with null values
            $dataWithNullOptionals = [
                'name' => ucwords($faker->words(2, true)),
                'slug' => Str::slug($faker->words(2, true)),
                'description' => null,
            ];

            $validator2 = Validator::make($dataWithNullOptionals, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should pass when optional fields are null
            $this->assertFalse($validator2->fails(), "Validation should pass when optional fields are null");
            $this->assertCount(0, $validator2->errors()->all(), "There should be no errors when optional fields are null");

            // Test Case 3: Optional fields with valid values
            $dataWithValidOptionals = [
                'name' => ucwords($faker->words(2, true)),
                'slug' => Str::slug($faker->words(2, true)),
                'description' => $faker->sentence(),
            ];

            $validator3 = Validator::make($dataWithValidOptionals, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should pass when optional fields contain valid data
            $this->assertFalse($validator3->fails(), "Validation should pass when optional fields contain valid data");
            $this->assertCount(0, $validator3->errors()->all(), "There should be no errors when optional fields contain valid data");
        }, 100);
    }

    /**
     * Feature: admin-livewire-crud, Property 6: Validation success enables submission (Edge cases)
     * Validates: Requirements 10.5
     * 
     * For any form with edge case valid values (max length, boundary values),
     * validation should pass without errors.
     */
    public function test_validation_passes_with_edge_case_valid_values(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Maximum length values (at boundary)
            $maxLengthData = [
                'name' => str_repeat('a', 255),  // Exactly 255 characters
                'slug' => str_repeat('a', 255),  // Exactly 255 characters
                'description' => str_repeat('a', 1000),  // Exactly 1000 characters
            ];

            $validator1 = Validator::make($maxLengthData, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should pass at maximum length boundary
            $this->assertFalse($validator1->fails(), "Validation should pass when fields are at maximum length");
            $this->assertCount(0, $validator1->errors()->all(), "There should be no errors at maximum length boundary");

            // Test Case 2: Minimum length values
            $minLengthData = [
                'name' => 'a',  // Single character
                'slug' => 'a',  // Single character
                'description' => 'a',  // Single character
            ];

            $validator2 = Validator::make($minLengthData, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should pass at minimum length
            $this->assertFalse($validator2->fails(), "Validation should pass when fields are at minimum length");
            $this->assertCount(0, $validator2->errors()->all(), "There should be no errors at minimum length");

            // Test Case 3: Valid slug with numbers and hyphens
            $complexSlugData = [
                'name' => ucwords($faker->words(2, true)),
                'slug' => 'valid-slug-123-with-numbers',
                'description' => $faker->sentence(),
            ];

            $validator3 = Validator::make($complexSlugData, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should pass for complex valid slug
            $this->assertFalse($validator3->fails(), "Validation should pass for complex valid slug");
            $this->assertCount(0, $validator3->errors()->all(), "There should be no errors for complex valid slug");
        }, 100);
    }

    /**
     * Feature: admin-livewire-crud, Property 6: Validation success enables submission (Transition from invalid to valid)
     * Validates: Requirements 10.5
     * 
     * When transitioning from invalid to valid data, validation should pass and
     * all error indicators should be removed.
     */
    public function test_validation_passes_after_correcting_all_invalid_fields(): void
    {
        PropertyTesting::run(function ($faker) {
            // Step 1: Start with invalid data
            $invalidData = [
                'name' => '',  // Invalid: empty
                'slug' => 'Invalid-Slug',  // Invalid: uppercase
                'description' => str_repeat('a', 1001),  // Invalid: too long
            ];

            $invalidValidator = Validator::make($invalidData, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should fail for invalid data
            $this->assertTrue($invalidValidator->fails(), "Validation should fail for invalid data");
            $this->assertGreaterThan(0, count($invalidValidator->errors()->all()), "Should have error messages for invalid data");

            // Step 2: Correct all fields to valid data
            $validData = [
                'name' => ucwords($faker->words(2, true)),
                'slug' => Str::slug($faker->words(2, true)),
                'description' => str_repeat('a', 1000),
            ];

            $validValidator = Validator::make($validData, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: After correcting all fields, validation should pass
            $this->assertFalse($validValidator->fails(), "Validation should pass after correcting all fields");
            
            // Property: All error indicators should be removed
            $this->assertCount(0, $validValidator->errors()->all(), "All error messages should be removed after correction");
            $this->assertFalse($validValidator->errors()->has('name'), "Should not have error for 'name' field");
            $this->assertFalse($validValidator->errors()->has('slug'), "Should not have error for 'slug' field");
            $this->assertFalse($validValidator->errors()->has('description'), "Should not have error for 'description' field");
            
            // Property: Form submission should be enabled (validated data available)
            $validated = $validValidator->validated();
            $this->assertIsArray($validated, "Validated data should be available for submission");
            $this->assertArrayHasKey('name', $validated, "Validated data should include all fields");
            $this->assertArrayHasKey('slug', $validated, "Validated data should include all fields");
        }, 100);
    }
}

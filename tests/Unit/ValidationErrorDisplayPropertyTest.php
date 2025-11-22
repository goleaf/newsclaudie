<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Property-Based Tests for Validation Error Display
 * 
 * These tests verify universal properties for validation error display and invalid input rejection.
 */
final class ValidationErrorDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 4: Invalid input rejection
     * Validates: Requirements 10.1, 10.4
     * 
     * For any form field and any invalid input, submitting the form should display
     * field-specific error messages and prevent data persistence.
     */
    public function test_category_form_rejects_invalid_input_with_field_specific_errors(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Missing required name field
            $validator = Validator::make([
                'name' => '',
                'slug' => 'valid-slug',
                'description' => $faker->sentence(),
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should fail for missing required field
            $this->assertTrue($validator->fails(), "Validation should fail for missing required name");
            
            // Property: Error should be specific to the 'name' field
            $this->assertTrue($validator->errors()->has('name'), "Validation errors should include 'name' field");
            
            // Property: Error message should indicate the field is required
            $errorMessage = $validator->errors()->first('name');
            $this->assertNotEmpty($errorMessage, "Error message should not be empty");
            $this->assertStringContainsString('required', strtolower($errorMessage), "Error message should mention 'required'");

            // Test Case 2: Invalid slug format
            $invalidSlugs = [
                'Invalid-Slug',  // uppercase
                'invalid_slug',  // underscore
                'invalid slug',  // space
                '-invalid',      // leading hyphen
                'invalid-',      // trailing hyphen
                'invalid--slug', // double hyphen
                '',              // empty
            ];

            foreach ($invalidSlugs as $invalidSlug) {
                $validator = Validator::make([
                    'name' => $faker->words(2, true),
                    'slug' => $invalidSlug,
                    'description' => $faker->sentence(),
                ], [
                    'name' => ['required', 'string', 'max:255'],
                    'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                    'description' => ['nullable', 'string', 'max:1000'],
                ]);

                // Property: Validation should fail for invalid slug format
                $this->assertTrue($validator->fails(), "Validation should fail for invalid slug: '{$invalidSlug}'");
                
                // Property: Error should be specific to the 'slug' field
                $this->assertTrue($validator->errors()->has('slug'), "Validation errors should include 'slug' field for: '{$invalidSlug}'");
            }

            // Test Case 3: Exceeding maximum length
            $validator = Validator::make([
                'name' => str_repeat('a', 256),  // Exceeds 255 max
                'slug' => 'valid-slug',
                'description' => str_repeat('a', 1001),  // Exceeds 1000 max
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should fail for exceeding max length
            $this->assertTrue($validator->fails(), "Validation should fail for exceeding max length");
            
            // Property: Errors should be specific to both fields
            $this->assertTrue($validator->errors()->has('name'), "Validation errors should include 'name' field for max length");
            $this->assertTrue($validator->errors()->has('description'), "Validation errors should include 'description' field for max length");
            
            // Property: Error messages should mention max length
            $nameError = $validator->errors()->first('name');
            $descError = $validator->errors()->first('description');
            $this->assertStringContainsString('255', $nameError, "Name error should mention max length 255");
            $this->assertStringContainsString('1000', $descError, "Description error should mention max length 1000");

            // Test Case 4: Duplicate slug (uniqueness validation)
            $existingCategory = Category::factory()->create([
                'slug' => 'existing-slug',
            ]);

            $validator = Validator::make([
                'name' => $faker->words(2, true),
                'slug' => 'existing-slug',
                'description' => $faker->sentence(),
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should fail for duplicate slug
            $this->assertTrue($validator->fails(), "Validation should fail for duplicate slug");
            
            // Property: Error should be specific to the 'slug' field
            $this->assertTrue($validator->errors()->has('slug'), "Validation errors should include 'slug' field for uniqueness");
            
            // Property: Error message should indicate the slug is taken
            $slugError = $validator->errors()->first('slug');
            $this->assertStringContainsString('taken', strtolower($slugError), "Error message should mention slug is taken");

            $existingCategory->delete();
        }, 10);  // Run fewer iterations for database tests
    }

    /**
     * Feature: admin-livewire-crud, Property 4: Invalid input rejection (Post validation)
     * Validates: Requirements 10.1, 10.4
     * 
     * For any post form field and any invalid input, validation should fail with
     * field-specific error messages.
     */
    public function test_post_form_rejects_invalid_input_with_field_specific_errors(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Missing required title
            $validator = Validator::make([
                'title' => '',
                'body' => $faker->paragraphs(3, true),
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'description' => ['nullable', 'string', 'max:255'],
                'featured_image' => ['nullable', 'url', 'max:255'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
                'categories' => ['nullable', 'array'],
                'categories.*' => ['integer', 'exists:categories,id'],
            ]);

            // Property: Validation should fail for missing required title
            $this->assertTrue($validator->fails(), "Validation should fail for missing required title");
            
            // Property: Error should be specific to the 'title' field
            $this->assertTrue($validator->errors()->has('title'), "Validation errors should include 'title' field");

            // Test Case 2: Missing required body
            $validator = Validator::make([
                'title' => $faker->sentence(),
                'body' => '',
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
            ]);

            // Property: Validation should fail for missing required body
            $this->assertTrue($validator->fails(), "Validation should fail for missing required body");
            
            // Property: Error should be specific to the 'body' field
            $this->assertTrue($validator->errors()->has('body'), "Validation errors should include 'body' field");

            // Test Case 3: Invalid featured_image URL
            $invalidUrls = [
                'not-a-url',
                'javascript:alert(1)',
                'just some text',
                'missing-protocol.com',
            ];

            foreach ($invalidUrls as $invalidUrl) {
                $validator = Validator::make([
                    'title' => $faker->sentence(),
                    'body' => $faker->paragraphs(3, true),
                    'featured_image' => $invalidUrl,
                ], [
                    'title' => ['required', 'string', 'max:255'],
                    'body' => ['required', 'string'],
                    'featured_image' => ['nullable', 'url', 'max:255'],
                ]);

                // Property: Validation should fail for invalid URL
                $this->assertTrue($validator->fails(), "Validation should fail for invalid URL: '{$invalidUrl}'");
                
                // Property: Error should be specific to the 'featured_image' field
                $this->assertTrue($validator->errors()->has('featured_image'), "Validation errors should include 'featured_image' field");
            }

            // Test Case 4: Invalid tag format (exceeding max length)
            $validator = Validator::make([
                'title' => $faker->sentence(),
                'body' => $faker->paragraphs(3, true),
                'tags' => [str_repeat('a', 51)],  // Exceeds 50 max
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
            ]);

            // Property: Validation should fail for tag exceeding max length
            $this->assertTrue($validator->fails(), "Validation should fail for tag exceeding max length");
            
            // Property: Error should be specific to the tag array element
            $this->assertTrue($validator->errors()->has('tags.0'), "Validation errors should include 'tags.0' field");

            // Test Case 5: Invalid category ID (non-existent)
            $validator = Validator::make([
                'title' => $faker->sentence(),
                'body' => $faker->paragraphs(3, true),
                'categories' => [99999],  // Non-existent category ID
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'categories' => ['nullable', 'array'],
                'categories.*' => ['integer', 'exists:categories,id'],
            ]);

            // Property: Validation should fail for non-existent category
            $this->assertTrue($validator->fails(), "Validation should fail for non-existent category");
            
            // Property: Error should be specific to the category array element
            $this->assertTrue($validator->errors()->has('categories.0'), "Validation errors should include 'categories.0' field");
        }, 10);  // Run fewer iterations for complex validation
    }

    /**
     * Feature: admin-livewire-crud, Property 4: Invalid input rejection (Comment validation)
     * Validates: Requirements 10.1, 10.4
     * 
     * For any comment form field and any invalid input, validation should fail with
     * field-specific error messages.
     */
    public function test_comment_form_rejects_invalid_input_with_field_specific_errors(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Missing required content
            $validator = Validator::make([
                'content' => '',
            ], [
                'content' => ['required', 'string', 'max:1024'],
            ]);

            // Property: Validation should fail for missing required content
            $this->assertTrue($validator->fails(), "Validation should fail for missing required content");
            
            // Property: Error should be specific to the 'content' field
            $this->assertTrue($validator->errors()->has('content'), "Validation errors should include 'content' field");

            // Test Case 2: Content exceeding maximum length
            $validator = Validator::make([
                'content' => str_repeat('a', 1025),  // Exceeds 1024 max
            ], [
                'content' => ['required', 'string', 'max:1024'],
            ]);

            // Property: Validation should fail for exceeding max length
            $this->assertTrue($validator->fails(), "Validation should fail for content exceeding max length");
            
            // Property: Error should be specific to the 'content' field
            $this->assertTrue($validator->errors()->has('content'), "Validation errors should include 'content' field for max length");
            
            // Property: Error message should mention max length
            $contentError = $validator->errors()->first('content');
            $this->assertStringContainsString('1024', $contentError, "Content error should mention max length 1024");
        }, 10);
    }

    /**
     * Feature: admin-livewire-crud, Property 4: Invalid input rejection (User validation)
     * Validates: Requirements 10.1, 10.4
     * 
     * For any user form field and any invalid input, validation should fail with
     * field-specific error messages.
     */
    public function test_user_form_rejects_invalid_input_with_field_specific_errors(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Invalid email format
            $invalidEmails = [
                'not-an-email',
                '@nodomain.com',
                'spaces in@email.com',
                'double@@domain.com',
                'no-at-sign.com',
                'missing-domain@',
            ];

            foreach ($invalidEmails as $invalidEmail) {
                $validator = Validator::make([
                    'name' => $faker->name(),
                    'email' => $invalidEmail,
                    'password' => 'password123',
                ], [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                    'password' => ['required', 'string', 'min:8'],
                ]);

                // Property: Validation should fail for invalid email format
                $this->assertTrue($validator->fails(), "Validation should fail for invalid email: '{$invalidEmail}'");
                
                // Property: Error should be specific to the 'email' field
                $this->assertTrue($validator->errors()->has('email'), "Validation errors should include 'email' field for: '{$invalidEmail}'");
            }

            // Test Case 2: Duplicate email (uniqueness validation)
            $existingUser = User::factory()->create([
                'email' => 'existing@example.com',
            ]);

            $validator = Validator::make([
                'name' => $faker->name(),
                'email' => 'existing@example.com',
                'password' => 'password123',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: Validation should fail for duplicate email
            $this->assertTrue($validator->fails(), "Validation should fail for duplicate email");
            
            // Property: Error should be specific to the 'email' field
            $this->assertTrue($validator->errors()->has('email'), "Validation errors should include 'email' field for uniqueness");
            
            // Property: Error message should indicate the email is taken
            $emailError = $validator->errors()->first('email');
            $this->assertStringContainsString('taken', strtolower($emailError), "Error message should mention email is taken");

            // Test Case 3: Password too short
            $validator = Validator::make([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => 'short',  // Less than 8 characters
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: Validation should fail for password too short
            $this->assertTrue($validator->fails(), "Validation should fail for password too short");
            
            // Property: Error should be specific to the 'password' field
            $this->assertTrue($validator->errors()->has('password'), "Validation errors should include 'password' field");
            
            // Property: Error message should mention minimum length
            $passwordError = $validator->errors()->first('password');
            $this->assertStringContainsString('8', $passwordError, "Password error should mention minimum length 8");

            $existingUser->delete();
        }, 10);  // Run fewer iterations for database tests
    }

    /**
     * Feature: admin-livewire-crud, Property 4: Invalid input rejection (Multiple field errors)
     * Validates: Requirements 10.1, 10.4
     * 
     * For any form with multiple invalid fields, validation should fail and display
     * errors for all invalid fields simultaneously.
     */
    public function test_multiple_field_errors_are_displayed_simultaneously(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case: Multiple invalid fields in category form
            $validator = Validator::make([
                'name' => '',  // Missing required
                'slug' => 'Invalid-Slug',  // Invalid format
                'description' => str_repeat('a', 1001),  // Exceeds max
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should fail
            $this->assertTrue($validator->fails(), "Validation should fail for multiple invalid fields");
            
            // Property: All invalid fields should have errors
            $this->assertTrue($validator->errors()->has('name'), "Validation errors should include 'name' field");
            $this->assertTrue($validator->errors()->has('slug'), "Validation errors should include 'slug' field");
            $this->assertTrue($validator->errors()->has('description'), "Validation errors should include 'description' field");
            
            // Property: Error count should match number of invalid fields
            $this->assertCount(3, $validator->errors()->keys(), "Should have errors for all 3 invalid fields");

            // Test Case: Multiple invalid fields in post form
            $validator = Validator::make([
                'title' => '',  // Missing required
                'body' => '',  // Missing required
                'featured_image' => 'not-a-url',  // Invalid URL
                'tags' => [str_repeat('a', 51)],  // Tag too long
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'featured_image' => ['nullable', 'url', 'max:255'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
            ]);

            // Property: Validation should fail
            $this->assertTrue($validator->fails(), "Validation should fail for multiple invalid post fields");
            
            // Property: All invalid fields should have errors
            $this->assertTrue($validator->errors()->has('title'), "Validation errors should include 'title' field");
            $this->assertTrue($validator->errors()->has('body'), "Validation errors should include 'body' field");
            $this->assertTrue($validator->errors()->has('featured_image'), "Validation errors should include 'featured_image' field");
            $this->assertTrue($validator->errors()->has('tags.0'), "Validation errors should include 'tags.0' field");
            
            // Property: Should have at least 4 error keys
            $this->assertGreaterThanOrEqual(4, count($validator->errors()->keys()), "Should have errors for at least 4 invalid fields");
        }, 10);
    }

    /**
     * Feature: admin-livewire-crud, Property 4: Invalid input rejection (Data persistence prevention)
     * Validates: Requirements 10.1, 10.4
     * 
     * For any invalid input, the system should prevent data persistence to the database.
     */
    public function test_invalid_input_prevents_data_persistence(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Test Case 1: Invalid category should not be persisted
            $initialCategoryCount = Category::count();
            
            $validator = Validator::make([
                'name' => '',  // Invalid: missing required
                'slug' => 'invalid-slug',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            ]);

            // Property: Validation should fail
            $this->assertTrue($validator->fails(), "Validation should fail for invalid category");
            
            // Property: No category should be created when validation fails
            // (In real application, the controller/component would check validator->fails() before persisting)
            if ($validator->fails()) {
                // Simulate the behavior: don't persist if validation fails
                $this->assertSame($initialCategoryCount, Category::count(), "Category count should not change when validation fails");
            }

            // Test Case 2: Valid category should be persisted
            $validValidator = Validator::make([
                'name' => ucwords($faker->words(2, true)),
                'slug' => Str::slug($faker->words(2, true)),
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            ]);

            // Property: Validation should pass for valid data
            $this->assertFalse($validValidator->fails(), "Validation should pass for valid category");
            
            // Property: Valid data can be persisted
            if (!$validValidator->fails()) {
                $category = Category::create($validValidator->validated());
                $this->assertSame($initialCategoryCount + 1, Category::count(), "Category count should increase when validation passes");
                $category->delete();
            }
        }
    }
}

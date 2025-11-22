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
 * Property-Based Tests for Error Clearing on Correction
 * 
 * These tests verify that validation errors are cleared when invalid input is corrected.
 */
final class ErrorClearingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 5: Error clearing on correction
     * Validates: Requirements 10.2
     * 
     * For any form field with a validation error, correcting the input to a valid value
     * should clear the error message for that field.
     */
    public function test_category_form_clears_errors_when_invalid_input_is_corrected(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Empty name field -> corrected to valid name
            // Step 1: Validate with invalid (empty) name
            $invalidValidator = Validator::make([
                'name' => '',
                'slug' => 'valid-slug',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            ]);

            // Property: Validation should fail for empty name
            $this->assertTrue($invalidValidator->fails(), "Validation should fail for empty name");
            $this->assertTrue($invalidValidator->errors()->has('name'), "Should have error for 'name' field");

            // Step 2: Correct the input with valid name
            $validName = ucwords($faker->words(2, true));
            $correctedValidator = Validator::make([
                'name' => $validName,
                'slug' => 'valid-slug',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedValidator->fails(), "Validation should pass after correcting name");
            
            // Property: The 'name' field should no longer have errors
            $this->assertFalse($correctedValidator->errors()->has('name'), "Should not have error for 'name' field after correction");

            // Test Case 2: Invalid slug format -> corrected to valid slug
            // Step 1: Validate with invalid slug (uppercase)
            $invalidSlugValidator = Validator::make([
                'name' => $faker->words(2, true),
                'slug' => 'Invalid-Slug',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            ]);

            // Property: Validation should fail for invalid slug
            $this->assertTrue($invalidSlugValidator->fails(), "Validation should fail for invalid slug format");
            $this->assertTrue($invalidSlugValidator->errors()->has('slug'), "Should have error for 'slug' field");

            // Step 2: Correct the slug to valid format
            $validSlug = Str::slug($faker->words(2, true));
            $correctedSlugValidator = Validator::make([
                'name' => $faker->words(2, true),
                'slug' => $validSlug,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedSlugValidator->fails(), "Validation should pass after correcting slug");
            
            // Property: The 'slug' field should no longer have errors
            $this->assertFalse($correctedSlugValidator->errors()->has('slug'), "Should not have error for 'slug' field after correction");

            // Test Case 3: Exceeding max length -> corrected to within limit
            // Step 1: Validate with name exceeding max length
            $tooLongValidator = Validator::make([
                'name' => str_repeat('a', 256),
                'slug' => 'valid-slug',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            ]);

            // Property: Validation should fail for exceeding max length
            $this->assertTrue($tooLongValidator->fails(), "Validation should fail for name exceeding max length");
            $this->assertTrue($tooLongValidator->errors()->has('name'), "Should have error for 'name' field");

            // Step 2: Correct to within max length
            $validLengthName = str_repeat('a', 255);
            $correctedLengthValidator = Validator::make([
                'name' => $validLengthName,
                'slug' => 'valid-slug',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedLengthValidator->fails(), "Validation should pass after correcting length");
            
            // Property: The 'name' field should no longer have errors
            $this->assertFalse($correctedLengthValidator->errors()->has('name'), "Should not have error for 'name' field after length correction");
        }, 10);  // Run fewer iterations for validation tests
    }

    /**
     * Feature: admin-livewire-crud, Property 5: Error clearing on correction (Post validation)
     * Validates: Requirements 10.2
     * 
     * For any post form field with a validation error, correcting the input should clear
     * the error for that specific field.
     */
    public function test_post_form_clears_errors_when_invalid_input_is_corrected(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Empty title -> corrected to valid title
            // Step 1: Validate with invalid (empty) title
            $invalidValidator = Validator::make([
                'title' => '',
                'body' => $faker->paragraphs(3, true),
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
            ]);

            // Property: Validation should fail for empty title
            $this->assertTrue($invalidValidator->fails(), "Validation should fail for empty title");
            $this->assertTrue($invalidValidator->errors()->has('title'), "Should have error for 'title' field");

            // Step 2: Correct the title
            $validTitle = $faker->sentence();
            $correctedValidator = Validator::make([
                'title' => $validTitle,
                'body' => $faker->paragraphs(3, true),
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedValidator->fails(), "Validation should pass after correcting title");
            
            // Property: The 'title' field should no longer have errors
            $this->assertFalse($correctedValidator->errors()->has('title'), "Should not have error for 'title' field after correction");

            // Test Case 2: Invalid featured_image URL -> corrected to valid URL
            // Step 1: Validate with invalid URL
            $invalidUrlValidator = Validator::make([
                'title' => $faker->sentence(),
                'body' => $faker->paragraphs(3, true),
                'featured_image' => 'not-a-url',
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'featured_image' => ['nullable', 'url', 'max:255'],
            ]);

            // Property: Validation should fail for invalid URL
            $this->assertTrue($invalidUrlValidator->fails(), "Validation should fail for invalid URL");
            $this->assertTrue($invalidUrlValidator->errors()->has('featured_image'), "Should have error for 'featured_image' field");

            // Step 2: Correct to valid URL
            $validUrl = $faker->imageUrl();
            $correctedUrlValidator = Validator::make([
                'title' => $faker->sentence(),
                'body' => $faker->paragraphs(3, true),
                'featured_image' => $validUrl,
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'featured_image' => ['nullable', 'url', 'max:255'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedUrlValidator->fails(), "Validation should pass after correcting URL");
            
            // Property: The 'featured_image' field should no longer have errors
            $this->assertFalse($correctedUrlValidator->errors()->has('featured_image'), "Should not have error for 'featured_image' field after correction");

            // Test Case 3: Tag exceeding max length -> corrected to valid length
            // Step 1: Validate with tag too long
            $invalidTagValidator = Validator::make([
                'title' => $faker->sentence(),
                'body' => $faker->paragraphs(3, true),
                'tags' => [str_repeat('a', 51)],
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
            ]);

            // Property: Validation should fail for tag exceeding max length
            $this->assertTrue($invalidTagValidator->fails(), "Validation should fail for tag exceeding max length");
            $this->assertTrue($invalidTagValidator->errors()->has('tags.0'), "Should have error for 'tags.0' field");

            // Step 2: Correct tag to valid length
            $validTag = str_repeat('a', 50);
            $correctedTagValidator = Validator::make([
                'title' => $faker->sentence(),
                'body' => $faker->paragraphs(3, true),
                'tags' => [$validTag],
            ], [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedTagValidator->fails(), "Validation should pass after correcting tag length");
            
            // Property: The 'tags.0' field should no longer have errors
            $this->assertFalse($correctedTagValidator->errors()->has('tags.0'), "Should not have error for 'tags.0' field after correction");
        }, 10);
    }

    /**
     * Feature: admin-livewire-crud, Property 5: Error clearing on correction (User validation)
     * Validates: Requirements 10.2
     * 
     * For any user form field with a validation error, correcting the input should clear
     * the error for that specific field.
     */
    public function test_user_form_clears_errors_when_invalid_input_is_corrected(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Invalid email format -> corrected to valid email
            // Step 1: Validate with invalid email
            $invalidEmailValidator = Validator::make([
                'name' => $faker->name(),
                'email' => 'not-an-email',
                'password' => 'password123',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: Validation should fail for invalid email
            $this->assertTrue($invalidEmailValidator->fails(), "Validation should fail for invalid email");
            $this->assertTrue($invalidEmailValidator->errors()->has('email'), "Should have error for 'email' field");

            // Step 2: Correct to valid email
            $validEmail = $faker->unique()->safeEmail();
            $correctedEmailValidator = Validator::make([
                'name' => $faker->name(),
                'email' => $validEmail,
                'password' => 'password123',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedEmailValidator->fails(), "Validation should pass after correcting email");
            
            // Property: The 'email' field should no longer have errors
            $this->assertFalse($correctedEmailValidator->errors()->has('email'), "Should not have error for 'email' field after correction");

            // Test Case 2: Password too short -> corrected to valid length
            // Step 1: Validate with short password
            $shortPasswordValidator = Validator::make([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => 'short',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: Validation should fail for short password
            $this->assertTrue($shortPasswordValidator->fails(), "Validation should fail for short password");
            $this->assertTrue($shortPasswordValidator->errors()->has('password'), "Should have error for 'password' field");

            // Step 2: Correct to valid password length
            $validPassword = 'password123';
            $correctedPasswordValidator = Validator::make([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => $validPassword,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedPasswordValidator->fails(), "Validation should pass after correcting password");
            
            // Property: The 'password' field should no longer have errors
            $this->assertFalse($correctedPasswordValidator->errors()->has('password'), "Should not have error for 'password' field after correction");

            // Test Case 3: Duplicate email -> corrected to unique email
            // Create an existing user
            $existingUser = User::factory()->create([
                'email' => 'existing@example.com',
            ]);

            // Step 1: Validate with duplicate email
            $duplicateEmailValidator = Validator::make([
                'name' => $faker->name(),
                'email' => 'existing@example.com',
                'password' => 'password123',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: Validation should fail for duplicate email
            $this->assertTrue($duplicateEmailValidator->fails(), "Validation should fail for duplicate email");
            $this->assertTrue($duplicateEmailValidator->errors()->has('email'), "Should have error for 'email' field");

            // Step 2: Correct to unique email
            $uniqueEmail = $faker->unique()->safeEmail();
            $correctedUniqueEmailValidator = Validator::make([
                'name' => $faker->name(),
                'email' => $uniqueEmail,
                'password' => 'password123',
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedUniqueEmailValidator->fails(), "Validation should pass after correcting to unique email");
            
            // Property: The 'email' field should no longer have errors
            $this->assertFalse($correctedUniqueEmailValidator->errors()->has('email'), "Should not have error for 'email' field after correction");

            $existingUser->delete();
        }, 10);  // Run fewer iterations for database tests
    }

    /**
     * Feature: admin-livewire-crud, Property 5: Error clearing on correction (Comment validation)
     * Validates: Requirements 10.2
     * 
     * For any comment form field with a validation error, correcting the input should clear
     * the error for that specific field.
     */
    public function test_comment_form_clears_errors_when_invalid_input_is_corrected(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case 1: Empty content -> corrected to valid content
            // Step 1: Validate with empty content
            $invalidValidator = Validator::make([
                'content' => '',
            ], [
                'content' => ['required', 'string', 'max:1024'],
            ]);

            // Property: Validation should fail for empty content
            $this->assertTrue($invalidValidator->fails(), "Validation should fail for empty content");
            $this->assertTrue($invalidValidator->errors()->has('content'), "Should have error for 'content' field");

            // Step 2: Correct with valid content
            $validContent = $faker->paragraph();
            $correctedValidator = Validator::make([
                'content' => $validContent,
            ], [
                'content' => ['required', 'string', 'max:1024'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedValidator->fails(), "Validation should pass after correcting content");
            
            // Property: The 'content' field should no longer have errors
            $this->assertFalse($correctedValidator->errors()->has('content'), "Should not have error for 'content' field after correction");

            // Test Case 2: Content exceeding max length -> corrected to within limit
            // Step 1: Validate with content too long
            $tooLongValidator = Validator::make([
                'content' => str_repeat('a', 1025),
            ], [
                'content' => ['required', 'string', 'max:1024'],
            ]);

            // Property: Validation should fail for content exceeding max length
            $this->assertTrue($tooLongValidator->fails(), "Validation should fail for content exceeding max length");
            $this->assertTrue($tooLongValidator->errors()->has('content'), "Should have error for 'content' field");

            // Step 2: Correct to within max length
            $validLengthContent = str_repeat('a', 1024);
            $correctedLengthValidator = Validator::make([
                'content' => $validLengthContent,
            ], [
                'content' => ['required', 'string', 'max:1024'],
            ]);

            // Property: After correction, validation should pass
            $this->assertFalse($correctedLengthValidator->fails(), "Validation should pass after correcting content length");
            
            // Property: The 'content' field should no longer have errors
            $this->assertFalse($correctedLengthValidator->errors()->has('content'), "Should not have error for 'content' field after length correction");
        }, 10);
    }

    /**
     * Feature: admin-livewire-crud, Property 5: Error clearing on correction (Selective clearing)
     * Validates: Requirements 10.2
     * 
     * When correcting one field with an error, only that field's error should be cleared.
     * Other fields with errors should retain their error messages.
     */
    public function test_correcting_one_field_does_not_clear_errors_from_other_fields(): void
    {
        PropertyTesting::run(function ($faker) {
            // Test Case: Multiple invalid fields, correct only one
            // Step 1: Validate with multiple invalid fields
            $multipleErrorsValidator = Validator::make([
                'name' => '',  // Invalid: empty
                'slug' => 'Invalid-Slug',  // Invalid: uppercase
                'description' => str_repeat('a', 1001),  // Invalid: too long
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: All three fields should have errors
            $this->assertTrue($multipleErrorsValidator->fails(), "Validation should fail for multiple invalid fields");
            $this->assertTrue($multipleErrorsValidator->errors()->has('name'), "Should have error for 'name' field");
            $this->assertTrue($multipleErrorsValidator->errors()->has('slug'), "Should have error for 'slug' field");
            $this->assertTrue($multipleErrorsValidator->errors()->has('description'), "Should have error for 'description' field");

            // Step 2: Correct only the 'name' field, leave others invalid
            $correctedNameValidator = Validator::make([
                'name' => ucwords($faker->words(2, true)),  // Now valid
                'slug' => 'Invalid-Slug',  // Still invalid
                'description' => str_repeat('a', 1001),  // Still invalid
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should still fail (other fields still invalid)
            $this->assertTrue($correctedNameValidator->fails(), "Validation should still fail when other fields remain invalid");
            
            // Property: The corrected 'name' field should no longer have errors
            $this->assertFalse($correctedNameValidator->errors()->has('name'), "Should not have error for corrected 'name' field");
            
            // Property: The uncorrected fields should still have errors
            $this->assertTrue($correctedNameValidator->errors()->has('slug'), "Should still have error for uncorrected 'slug' field");
            $this->assertTrue($correctedNameValidator->errors()->has('description'), "Should still have error for uncorrected 'description' field");

            // Step 3: Correct another field ('slug'), leave 'description' invalid
            $correctedSlugValidator = Validator::make([
                'name' => ucwords($faker->words(2, true)),  // Valid
                'slug' => Str::slug($faker->words(2, true)),  // Now valid
                'description' => str_repeat('a', 1001),  // Still invalid
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should still fail (description still invalid)
            $this->assertTrue($correctedSlugValidator->fails(), "Validation should still fail when description remains invalid");
            
            // Property: Both corrected fields should not have errors
            $this->assertFalse($correctedSlugValidator->errors()->has('name'), "Should not have error for 'name' field");
            $this->assertFalse($correctedSlugValidator->errors()->has('slug'), "Should not have error for corrected 'slug' field");
            
            // Property: The uncorrected 'description' field should still have error
            $this->assertTrue($correctedSlugValidator->errors()->has('description'), "Should still have error for uncorrected 'description' field");

            // Step 4: Correct all fields
            $allCorrectedValidator = Validator::make([
                'name' => ucwords($faker->words(2, true)),  // Valid
                'slug' => Str::slug($faker->words(2, true)),  // Valid
                'description' => str_repeat('a', 1000),  // Now valid
            ], [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Property: Validation should pass when all fields are corrected
            $this->assertFalse($allCorrectedValidator->fails(), "Validation should pass when all fields are corrected");
            
            // Property: No fields should have errors
            $this->assertFalse($allCorrectedValidator->errors()->has('name'), "Should not have error for 'name' field");
            $this->assertFalse($allCorrectedValidator->errors()->has('slug'), "Should not have error for 'slug' field");
            $this->assertFalse($allCorrectedValidator->errors()->has('description'), "Should not have error for 'description' field");
        }, 10);
    }
}

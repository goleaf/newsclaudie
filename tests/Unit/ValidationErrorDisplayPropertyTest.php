<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Helpers\PropertyTesting;

/**
 * Property 4: Invalid input rejection
 * 
 * Feature: admin-livewire-crud, Property 4: Invalid input rejection
 * Validates: Requirements 10.1, 10.4
 * 
 * For any form field and any invalid input, submitting the form should display
 * field-specific error messages and prevent data persistence.
 */

test('property: invalid category name displays error and prevents persistence', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Generate invalid names (empty, too long, etc.)
        $invalidName = $faker->randomElement([
            '', // Empty
            '   ', // Whitespace only
            Str::random(256), // Too long (max 255)
        ]);
        
        $initialCount = Category::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->set('formOpen', true)
            ->set('formName', $invalidName)
            ->set('formSlug', 'test-slug')
            ->call('saveCategory');
        
        // Should have validation errors
        $component->assertHasErrors(['formName']);
        
        // Should not persist invalid data
        expect(Category::count())->toBe($initialCount);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: invalid category slug format displays error and prevents persistence', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Generate invalid slug formats that remain invalid after Str::slug() normalization
        $invalidSlug = $faker->randomElement([
            '', // Empty
            '   ', // Whitespace only
            '!!!', // Only special characters (becomes empty after normalization)
            '---', // Only hyphens (invalid pattern)
            Str::random(256), // Too long (max 255)
        ]);
        
        $initialCount = Category::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->set('formOpen', true)
            ->set('formName', 'Valid Name')
            ->set('formSlug', $invalidSlug)
            ->call('saveCategory');
        
        // Should have validation errors
        $component->assertHasErrors(['formSlug']);
        
        // Should not persist invalid data
        expect(Category::count())->toBe($initialCount);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: duplicate category slug displays uniqueness error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Create an existing category
        $existing = Category::factory()->create();
        
        $initialCount = Category::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->set('formOpen', true)
            ->set('formName', $faker->words(2, true))
            ->set('formSlug', $existing->slug) // Use existing slug
            ->call('saveCategory');
        
        // Should have uniqueness validation error
        $component->assertHasErrors(['formSlug']);
        
        // Should not create duplicate
        expect(Category::count())->toBe($initialCount);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: invalid post title displays error and prevents persistence', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Generate invalid titles
        $invalidTitle = $faker->randomElement([
            '', // Empty
            '   ', // Whitespace only
            Str::random(256), // Too long (max 255)
        ]);
        
        $initialCount = Post::withoutGlobalScopes()->count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.posts.index')
            ->set('form.title', $invalidTitle)
            ->set('form.slug', 'test-slug')
            ->set('form.body', 'Test body content')
            ->call('savePost');
        
        // Should have validation errors
        $component->assertHasErrors(['form.title']);
        
        // Should not persist invalid data
        expect(Post::withoutGlobalScopes()->count())->toBe($initialCount);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: invalid post slug format displays error and prevents persistence', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Generate invalid slug formats that remain invalid after Str::slug() normalization
        $invalidSlug = $faker->randomElement([
            '', // Empty
            '   ', // Whitespace only
            '!!!', // Only special characters (becomes empty after normalization)
            '---', // Only hyphens (invalid pattern)
            Str::random(256), // Too long (max 255)
        ]);
        
        $initialCount = Post::withoutGlobalScopes()->count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.posts.index')
            ->set('form.title', 'Valid Title')
            ->set('form.slug', $invalidSlug)
            ->set('form.body', 'Test body content')
            ->call('savePost');
        
        // Should have validation errors
        $component->assertHasErrors(['form.slug']);
        
        // Should not persist invalid data
        expect(Post::withoutGlobalScopes()->count())->toBe($initialCount);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: invalid user email displays error and prevents persistence', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Generate invalid emails that will definitely fail validation
        // Using only the most obviously invalid formats
        $invalidEmail = $faker->randomElement([
            '', // Empty - required validation
            'not-an-email', // No @ symbol - email format validation
            '@example.com', // Missing local part - email format validation
        ]);
        
        $initialCount = User::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.users.index')
            ->call('openCreateModal')
            ->set('createForm.name', 'Test User')
            ->set('createForm.email', $invalidEmail)
            ->set('createForm.password', 'password123')
            ->set('createForm.password_confirmation', 'password123')
            ->call('createUser');
        
        // Should have validation errors
        $component->assertHasErrors(['createForm.email']);
        
        // Should not persist invalid data
        expect(User::count())->toBe($initialCount);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: duplicate user email displays uniqueness error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Create an existing user
        $existing = User::factory()->create();
        
        $initialCount = User::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.users.index')
            ->set('createForm.name', $faker->name())
            ->set('createForm.email', $existing->email) // Use existing email
            ->set('createForm.password', 'password123')
            ->set('createForm.password_confirmation', 'password123')
            ->call('createUser');
        
        // Should have uniqueness validation error
        $component->assertHasErrors(['createForm.email']);
        
        // Should not create duplicate
        expect(User::count())->toBe($initialCount);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: mismatched password confirmation displays error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $initialCount = User::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.users.index')
            ->set('createForm.name', $faker->name())
            ->set('createForm.email', $faker->unique()->safeEmail())
            ->set('createForm.password', 'password123')
            ->set('createForm.password_confirmation', 'different-password')
            ->call('createUser');
        
        // Should have confirmation validation error
        $component->assertHasErrors(['createForm.password']);
        
        // Should not persist invalid data
        expect(User::count())->toBe($initialCount);
    }, 50);
})->group('property', 'validation', 'admin-crud');

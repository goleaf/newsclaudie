<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Helpers\PropertyTesting;

/**
 * Property 5: Error clearing on correction
 * 
 * Feature: admin-livewire-crud, Property 5: Error clearing on correction
 * Validates: Requirements 10.2
 * 
 * For any form field with a validation error, correcting the input to a valid value
 * should clear the error message for that field.
 */

test('property: correcting invalid category name clears error on resubmit', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $validSlug = Str::slug($faker->unique()->words(2, true));
        
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->set('formOpen', true)
            ->set('formName', '') // Invalid: empty
            ->set('formSlug', $validSlug);
        
        // Trigger validation by calling save
        $component->call('saveCategory');
        
        // Should have error
        $component->assertHasErrors(['formName']);
        
        // Now correct the error with a valid name
        $validName = $faker->words(2, true);
        $component->set('formName', $validName);
        
        // Resubmit with corrected data
        $component->call('saveCategory');
        
        // Error should be cleared and category should be created successfully
        $component->assertHasNoErrors();
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: correcting invalid category slug clears error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->set('formOpen', true)
            ->set('formName', 'Valid Name')
            ->set('formSlug', ''); // Invalid: empty
        
        // Trigger validation
        $component->call('saveCategory');
        
        // Should have error
        $component->assertHasErrors(['formSlug']);
        
        // Now correct the error with a valid slug
        $validSlug = Str::slug($faker->words(2, true));
        $component->set('formSlug', $validSlug);
        
        // Error should be cleared
        $component->assertHasNoErrors(['formSlug']);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: correcting duplicate category slug clears uniqueness error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Create an existing category
        $existing = Category::factory()->create();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->set('formOpen', true)
            ->set('formName', $faker->words(2, true))
            ->set('formSlug', $existing->slug); // Invalid: duplicate
        
        // Trigger validation
        $component->call('saveCategory');
        
        // Should have uniqueness error
        $component->assertHasErrors(['formSlug']);
        
        // Now correct with a unique slug
        $uniqueSlug = Str::slug($faker->unique()->words(3, true));
        $component->set('formSlug', $uniqueSlug);
        
        // Error should be cleared
        $component->assertHasNoErrors(['formSlug']);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: correcting invalid post title clears error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $component = Livewire::actingAs($admin)
            ->test('admin.posts.index')
            ->set('form.title', '') // Invalid: empty
            ->set('form.slug', 'test-slug')
            ->set('form.body', 'Test body');
        
        // Trigger validation
        $component->call('savePost');
        
        // Should have error
        $component->assertHasErrors(['form.title']);
        
        // Now correct the error
        $validTitle = $faker->sentence();
        $component->set('form.title', $validTitle);
        
        // Error should be cleared
        $component->assertHasNoErrors(['form.title']);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: correcting invalid post slug clears error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $component = Livewire::actingAs($admin)
            ->test('admin.posts.index')
            ->set('form.title', 'Valid Title')
            ->set('form.slug', '') // Invalid: empty
            ->set('form.body', 'Test body');
        
        // Trigger validation
        $component->call('savePost');
        
        // Should have error
        $component->assertHasErrors(['form.slug']);
        
        // Now correct the error
        $validSlug = Str::slug($faker->words(2, true));
        $component->set('form.slug', $validSlug);
        
        // Error should be cleared
        $component->assertHasNoErrors(['form.slug']);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: correcting invalid user email clears error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $component = Livewire::actingAs($admin)
            ->test('admin.users.index')
            ->call('openCreateModal')
            ->set('createForm.name', 'Test User')
            ->set('createForm.email', 'not-an-email') // Invalid: no @ symbol
            ->set('createForm.password', 'password123')
            ->set('createForm.password_confirmation', 'password123');
        
        // Trigger validation
        $component->call('createUser');
        
        // Should have error
        $component->assertHasErrors(['createForm.email']);
        
        // Now correct the error
        $validEmail = $faker->unique()->safeEmail();
        $component->set('createForm.email', $validEmail);
        
        // Error should be cleared
        $component->assertHasNoErrors(['createForm.email']);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: correcting duplicate user email clears uniqueness error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Create an existing user
        $existing = User::factory()->create();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.users.index')
            ->call('openCreateModal')
            ->set('createForm.name', $faker->name())
            ->set('createForm.email', $existing->email) // Invalid: duplicate
            ->set('createForm.password', 'password123')
            ->set('createForm.password_confirmation', 'password123');
        
        // Trigger validation
        $component->call('createUser');
        
        // Should have uniqueness error
        $component->assertHasErrors(['createForm.email']);
        
        // Now correct with a unique email
        $uniqueEmail = $faker->unique()->safeEmail();
        $component->set('createForm.email', $uniqueEmail);
        
        // Error should be cleared
        $component->assertHasNoErrors(['createForm.email']);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: correcting mismatched password confirmation clears error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $component = Livewire::actingAs($admin)
            ->test('admin.users.index')
            ->call('openCreateModal')
            ->set('createForm.name', $faker->name())
            ->set('createForm.email', $faker->unique()->safeEmail())
            ->set('createForm.password', 'password123')
            ->set('createForm.password_confirmation', 'different'); // Invalid: mismatch
        
        // Trigger validation
        $component->call('createUser');
        
        // Should have error
        $component->assertHasErrors(['createForm.password']);
        
        // Now correct the confirmation
        $component->set('createForm.password_confirmation', 'password123');
        
        // Error should be cleared
        $component->assertHasNoErrors(['createForm.password']);
    }, 50);
})->group('property', 'validation', 'admin-crud');

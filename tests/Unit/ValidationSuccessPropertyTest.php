<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Helpers\PropertyTesting;

/**
 * Property 6: Validation success enables submission
 * 
 * Feature: admin-livewire-crud, Property 6: Validation success enables submission
 * Validates: Requirements 10.5
 * 
 * For any form with all fields containing valid data, the system should remove
 * all error indicators and allow form submission.
 */

test('property: valid category data allows successful creation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $validName = $faker->words(2, true);
        $validSlug = Str::slug($faker->unique()->words(2, true));
        $validDescription = $faker->sentence();
        
        $initialCount = Category::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->set('formOpen', true)
            ->set('formName', $validName)
            ->set('formSlug', $validSlug)
            ->set('formDescription', $validDescription)
            ->call('saveCategory');
        
        // Should have no validation errors
        $component->assertHasNoErrors();
        
        // Should successfully create the category
        expect(Category::count())->toBe($initialCount + 1);
        
        // Verify the data was persisted correctly
        $category = Category::where('slug', $validSlug)->first();
        expect($category)->not->toBeNull();
        expect($category->name)->toBe($validName);
        expect($category->description)->toBe($validDescription);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: valid post data allows successful creation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $validTitle = $faker->sentence();
        $validSlug = Str::slug($faker->unique()->words(3, true));
        $validBody = $faker->paragraphs(3, true);
        
        $initialCount = Post::withoutGlobalScopes()->count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.posts.index')
            ->set('form.title', $validTitle)
            ->set('form.slug', $validSlug)
            ->set('form.body', $validBody)
            ->call('savePost');
        
        // Should have no validation errors
        $component->assertHasNoErrors();
        
        // Should successfully create the post
        expect(Post::withoutGlobalScopes()->count())->toBe($initialCount + 1);
        
        // Verify the data was persisted correctly
        // The slug might be normalized again by the component, so check by title
        $post = Post::withoutGlobalScopes()->where('title', $validTitle)->first();
        expect($post)->not->toBeNull();
        expect($post->body)->toBe($validBody);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: valid user data allows successful creation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $validName = $faker->name();
        $validEmail = $faker->unique()->safeEmail();
        $validPassword = 'password123';
        
        $initialCount = User::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.users.index')
            ->call('openCreateModal')
            ->set('createForm.name', $validName)
            ->set('createForm.email', $validEmail)
            ->set('createForm.password', $validPassword)
            ->set('createForm.password_confirmation', $validPassword)
            ->call('createUser');
        
        // Should have no validation errors
        $component->assertHasNoErrors();
        
        // Should successfully create the user
        expect(User::count())->toBe($initialCount + 1);
        
        // Verify the data was persisted correctly
        $user = User::where('email', $validEmail)->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe($validName);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: valid category update allows successful modification', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Create an existing category
        $category = Category::factory()->create();
        
        $newName = $faker->words(2, true);
        $newSlug = Str::slug($faker->unique()->words(2, true));
        
        $totalCount = Category::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->call('startEditForm', $category->id)
            ->set('formName', $newName)
            ->set('formSlug', $newSlug)
            ->call('saveCategory');
        
        // Should have no validation errors
        $component->assertHasNoErrors();
        
        // Should not create a new category
        expect(Category::count())->toBe($totalCount);
        
        // Verify the data was updated correctly
        $category->refresh();
        expect($category->name)->toBe($newName);
        expect($category->slug)->toBe($newSlug);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: valid post with optional fields allows successful creation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $validTitle = $faker->sentence();
        $validSlug = Str::slug($faker->unique()->words(3, true));
        $validBody = $faker->paragraphs(3, true);
        $validDescription = $faker->sentence();
        $validFeaturedImage = $faker->imageUrl();
        
        $initialCount = Post::withoutGlobalScopes()->count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.posts.index')
            ->set('form.title', $validTitle)
            ->set('form.slug', $validSlug)
            ->set('form.body', $validBody)
            ->set('form.description', $validDescription)
            ->set('form.featured_image', $validFeaturedImage)
            ->call('savePost');
        
        // Should have no validation errors
        $component->assertHasNoErrors();
        
        // Should successfully create the post
        expect(Post::withoutGlobalScopes()->count())->toBe($initialCount + 1);
        
        // Verify all data including optional fields was persisted correctly
        $post = Post::withoutGlobalScopes()->where('title', $validTitle)->first();
        expect($post)->not->toBeNull();
        expect($post->body)->toBe($validBody);
        expect($post->description)->toBe($validDescription);
        expect($post->featured_image)->toBe($validFeaturedImage);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: valid user with role flags allows successful creation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        $validName = $faker->name();
        $validEmail = $faker->unique()->safeEmail();
        $validPassword = 'password123';
        $isAdmin = $faker->boolean();
        $isAuthor = $faker->boolean();
        
        $initialCount = User::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.users.index')
            ->call('openCreateModal')
            ->set('createForm.name', $validName)
            ->set('createForm.email', $validEmail)
            ->set('createForm.password', $validPassword)
            ->set('createForm.password_confirmation', $validPassword)
            ->set('createForm.is_admin', $isAdmin)
            ->set('createForm.is_author', $isAuthor)
            ->call('createUser');
        
        // Should have no validation errors
        $component->assertHasNoErrors();
        
        // Should successfully create the user
        expect(User::count())->toBe($initialCount + 1);
        
        // Verify all data including role flags was persisted correctly
        $user = User::where('email', $validEmail)->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe($validName);
        expect($user->is_admin)->toBe($isAdmin);
        expect($user->is_author)->toBe($isAuthor);
    }, 50);
})->group('property', 'validation', 'admin-crud');

test('property: minimal valid data allows successful creation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    PropertyTesting::run(function ($faker) use ($admin) {
        // Test with only required fields
        $validName = $faker->words(2, true);
        $validSlug = Str::slug($faker->unique()->words(2, true));
        
        $initialCount = Category::count();
        
        $component = Livewire::actingAs($admin)
            ->test('admin.categories.index')
            ->set('formOpen', true)
            ->set('formName', $validName)
            ->set('formSlug', $validSlug)
            // Omit optional description field
            ->call('saveCategory');
        
        // Should have no validation errors
        $component->assertHasNoErrors();
        
        // Should successfully create the category
        expect(Category::count())->toBe($initialCount + 1);
        
        // Verify the data was persisted correctly
        $category = Category::where('slug', $validSlug)->first();
        expect($category)->not->toBeNull();
        expect($category->name)->toBe($validName);
        expect($category->description)->toBeNull();
    }, 50);
})->group('property', 'validation', 'admin-crud');

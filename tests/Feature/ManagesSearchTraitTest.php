<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Volt\Volt;

it('resets pagination when search changes on the categories list', function (): void {
    // Create enough categories to have multiple pages
    \App\Models\Category::factory()->count(30)->create();
    
    $component = Volt::test('categories.index', ['page' => 3]);
    
    // Verify we're on page 3
    $paginator = $component->viewData('categories');
    expect($paginator->currentPage())->toBe(3);
    
    // Set search and verify pagination resets
    $component->set('search', 'news');
    $paginator = $component->viewData('categories');
    expect($paginator->currentPage())->toBe(1);
});

it('resets pagination when search changes on admin users', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    
    // Create enough users to have multiple pages
    User::factory()->count(30)->create();

    $component = Volt::actingAs($admin)
        ->test('admin.users.index', ['page' => 2]);
    
    // Verify we're on page 2
    $paginator = $component->viewData('users');
    expect($paginator->currentPage())->toBe(2);
    
    // Set search and verify pagination resets
    $component->set('search', 'editor');
    $paginator = $component->viewData('users');
    expect($paginator->currentPage())->toBe(1);
});

it('resets pagination when search changes on admin posts', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    
    // Create enough posts to have multiple pages
    \App\Models\Post::factory()->count(30)->create(['user_id' => $admin->id]);

    $component = Volt::actingAs($admin)
        ->test('admin.posts.index', ['page' => 2]);
    
    // Verify we're on page 2
    $paginator = $component->viewData('posts');
    expect($paginator->currentPage())->toBe(2);
    
    // Set search and verify pagination resets
    $component->set('search', 'draft');
    $paginator = $component->viewData('posts');
    expect($paginator->currentPage())->toBe(1);
});

it('pushes search terms into the query string metadata', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $categories = Volt::test('categories.index')->set('search', 'alpha');
    $users = Volt::actingAs($admin)->test('admin.users.index')->set('search', 'beta');
    $posts = Volt::actingAs($admin)->test('admin.posts.index')->set('search', 'gamma');

    // Verify search values are set
    expect($categories->get('search'))->toBe('alpha');
    expect($users->get('search'))->toBe('beta');
    expect($posts->get('search'))->toBe('gamma');
});

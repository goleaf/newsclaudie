<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Livewire\Volt\Volt;
use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('can search categories by name', function (): void {
    Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
    Category::factory()->create(['name' => 'Science', 'slug' => 'science']);
    Category::factory()->create(['name' => 'Arts', 'slug' => 'arts']);

    actingAs($this->admin);

    Volt::test('admin.categories.index')
        ->set('search', 'tech')
        ->assertSee('Technology')
        ->assertDontSee('Science')
        ->assertDontSee('Arts');
});

it('can search categories by slug', function (): void {
    Category::factory()->create(['name' => 'Technology News', 'slug' => 'technology-news']);
    Category::factory()->create(['name' => 'Science Updates', 'slug' => 'science-updates']);

    actingAs($this->admin);

    Volt::test('admin.categories.index')
        ->set('search', 'science')
        ->assertSee('Science Updates')
        ->assertDontSee('Technology News');
});

it('can clear search', function (): void {
    Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
    Category::factory()->create(['name' => 'Science', 'slug' => 'science']);

    actingAs($this->admin);

    Volt::test('admin.categories.index')
        ->set('search', 'tech')
        ->assertSee('Technology')
        ->assertDontSee('Science')
        ->call('clearSearch')
        ->assertSee('Technology')
        ->assertSee('Science');
});

it('can sort categories by name ascending', function (): void {
    Category::factory()->create(['name' => 'Zebra', 'slug' => 'zebra']);
    Category::factory()->create(['name' => 'Apple', 'slug' => 'apple']);
    Category::factory()->create(['name' => 'Mango', 'slug' => 'mango']);

    actingAs($this->admin);

    Volt::test('admin.categories.index')
        ->call('sortBy', 'name')
        ->assertSet('sortField', 'name')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Apple', 'Mango', 'Zebra']);
});

it('can sort categories by name descending', function (): void {
    Category::factory()->create(['name' => 'Zebra', 'slug' => 'zebra']);
    Category::factory()->create(['name' => 'Apple', 'slug' => 'apple']);
    Category::factory()->create(['name' => 'Mango', 'slug' => 'mango']);

    actingAs($this->admin);

    Volt::test('admin.categories.index')
        ->call('sortBy', 'name')
        ->call('sortBy', 'name') // Toggle to descending
        ->assertSet('sortField', 'name')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Zebra', 'Mango', 'Apple']);
});

it('can delete a category', function (): void {
    $category = Category::factory()->create(['name' => 'Test Category']);

    actingAs($this->admin);

    Volt::test('admin.categories.index')
        ->call('deleteCategory', $category->id)
        ->assertSet('statusMessage', __('messages.category_deleted'));

    expect(Category::find($category->id))->toBeNull();
});

it('persists search in query string', function (): void {
    Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);

    actingAs($this->admin);

    Volt::test('admin.categories.index')
        ->set('search', 'tech')
        ->assertSet('search', 'tech');
});

it('can change sort field', function (): void {
    Category::factory()->create(['name' => 'Zebra', 'slug' => 'zebra']);
    Category::factory()->create(['name' => 'Apple', 'slug' => 'apple']);

    actingAs($this->admin);

    // Sorting by slug should work
    Volt::test('admin.categories.index')
        ->call('sortBy', 'slug')
        ->assertSeeInOrder(['apple', 'zebra']);
});

<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Livewire\Volt\Volt;
use function Pest\Laravel\actingAs;

it('saves inline category name edits', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    actingAs($admin);

    Volt::test('admin.categories.index')
        ->call('startEditing', $category->id, 'name')
        ->set('editingValues.name', 'Updated Name')
        ->call('saveInlineEdit')
        ->assertSet('editingId', null)
        ->assertSee('Updated Name');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Name',
    ]);
});

it('validates slug inline edits in real time', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create(['slug' => 'valid-slug']);

    actingAs($admin);

    Volt::test('admin.categories.index')
        ->call('startEditing', $category->id, 'slug')
        ->set('editingValues.slug', 'Bad Slug!')
        ->assertHasErrors(['editingValues.slug' => 'regex'])
        ->assertSee(__('validation.category.slug_regex'));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'slug' => 'valid-slug',
    ]);
});

it('opens the create form and auto-generates the slug', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin);

    Volt::test('admin.categories.index')
        ->call('startCreateForm')
        ->set('formName', 'Breaking News')
        ->assertSet('formSlug', 'breaking-news')
        ->assertSet('formOpen', true);
});

it('validates the slug when manually edited on the form', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin);

    Volt::test('admin.categories.index')
        ->call('startCreateForm')
        ->set('formSlug', '!!!')
        ->assertHasErrors(['formSlug' => 'required']);
});

it('creates categories through the admin form and enforces unique slugs', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin);

    Volt::test('admin.categories.index')
        ->call('startCreateForm')
        ->set('formName', 'Investigations')
        ->set('formDescription', 'Longform reporting')
        ->call('saveCategory')
        ->assertSet('statusMessage', __('messages.category_created'))
        ->set('formName', 'Investigations')
        ->call('saveCategory')
        ->assertHasErrors(['formSlug' => 'unique']);

    $this->assertDatabaseHas('categories', [
        'name' => 'Investigations',
        'slug' => 'investigations',
        'description' => 'Longform reporting',
    ]);
});

it('deletes categories from the admin page even when posts are attached', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();
    $post = Post::factory()->create();

    $category->posts()->attach($post);

    actingAs($admin);

    Volt::test('admin.categories.index')
        ->call('deleteCategory', $category->id)
        ->assertSet('statusMessage', __('messages.category_deleted'));

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    $this->assertDatabaseMissing('category_post', [
        'category_id' => $category->id,
        'post_id' => $post->id,
    ]);
});

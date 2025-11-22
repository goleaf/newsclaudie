<?php

declare(strict_types=1);

use App\Models\Category;
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

<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use function Pest\Laravel\actingAs;

it('renders the admin categories page for administrators', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $categories = Category::factory()->count(2)->create();

    actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSeeText(__('admin.categories.heading'))
        ->assertSeeText(e($categories->first()->name));
});

it('forbids non-admins from visiting the admin categories page', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});


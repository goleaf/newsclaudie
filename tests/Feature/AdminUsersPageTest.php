<?php

declare(strict_types=1);

use App\Models\User;
use function Pest\Laravel\actingAs;

it('renders the admin users page for administrators', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'Newsroom Tester']);

    actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSeeText(__('admin.users.heading'))
        ->assertSeeText('Newsroom Tester');
});

it('forbids non-admins from visiting the admin users page', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});




<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

it('renders the admin posts page for administrators', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $posts = Post::factory()
        ->count(2)
        ->create(['user_id' => $admin->id, 'published_at' => now()]);

    actingAs($admin)
        ->get(route('admin.posts.index'))
        ->assertOk()
        ->assertSeeText(__('admin.posts.heading'))
        ->assertSeeText(e($posts->first()->title))
        ->assertSeeText(__('admin.posts.status.published'));
});

it('forbids non-admins from visiting the admin posts page', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.posts.index'))
        ->assertForbidden();
});

it('sorts posts by the requested column from the query string', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $alpha = Post::factory()->create([
        'title' => 'Alpha Sort Post',
        'slug' => 'alpha-sort-post',
        'user_id' => $admin->id,
        'published_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $zulu = Post::factory()->create([
        'title' => 'Zulu Sort Post',
        'slug' => 'zulu-sort-post',
        'user_id' => $admin->id,
        'published_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    actingAs($admin)
        ->get(route('admin.posts.index', ['sort' => 'title', 'direction' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder([$alpha->title, $zulu->title]);

    actingAs($admin)
        ->get(route('admin.posts.index', ['sort' => 'title', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder([$zulu->title, $alpha->title]);
});

it('toggles sort direction when the same column is selected', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('admin.posts.index')
        ->call('sortBy', 'title')
        ->assertSet('sortField', 'title')
        ->assertSet('sortDirection', 'asc')
        ->call('sortBy', 'title')
        ->assertSet('sortDirection', 'desc');
});

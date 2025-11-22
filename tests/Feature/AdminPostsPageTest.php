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

it('keeps sort state in pagination links', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Post::factory()->count(12)->create([
        'user_id' => $admin->id,
        'published_at' => now(),
    ]);

    // Test via HTTP request to ensure query string is properly set
    actingAs($admin)
        ->get(route('admin.posts.index', ['sort' => 'title', 'direction' => 'asc', 'perPage' => 10]))
        ->assertOk()
        ->assertSee('sort=title', false)
        ->assertSee('direction=asc', false)
        ->assertSee('perPage=10', false);
});

it('selects all visible posts when toggling select all', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $posts = Post::factory()->count(3)->create([
        'user_id' => $admin->id,
        'published_at' => now(),
    ]);

    $component = Livewire::actingAs($admin)
        ->test('admin.posts.index');

    $expectedIds = $posts->pluck('id')->sort()->values()->all();

    $component->set('selectAll', true);
    
    $selected = collect($component->get('selected'))->sort()->values()->all();
    expect($selected)->toEqual($expectedIds);
    
    $component->set('selectAll', false);
    expect($component->get('selected'))->toEqual([]);
});

it('bulk publishes selected drafts and clears the selection', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $drafts = Post::factory()->count(2)->create([
        'user_id' => $admin->id,
        'published_at' => null,
    ]);

    $component = Livewire::actingAs($admin)
        ->test('admin.posts.index');

    $component
        ->set('selected', $drafts->pluck('id')->all())
        ->call('bulkPublish')
        ->assertSet('selected', [])
        ->assertSet('selectAll', false);

    $feedback = $component->get('bulkFeedback');

    expect($feedback)->toMatchArray([
        'status' => 'success',
        'action' => 'publish',
        'attempted' => 2,
        'updated' => 2,
        'failures' => [],
    ]);

    $drafts->each(fn (Post $post) => expect($post->fresh()->isPublished())->toBeTrue());
});

it('reports failures when some bulk actions cannot complete', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $post = Post::factory()->create([
        'user_id' => $admin->id,
        'published_at' => now(),
    ]);

    $missingId = $post->id + 1000;

    $component = Livewire::actingAs($admin)
        ->test('admin.posts.index');

    $component
        ->set('selected', [$post->id, $missingId])
        ->call('bulkUnpublish')
        ->assertSet('selected', [$missingId]);

    $feedback = $component->get('bulkFeedback');

    expect($feedback['status'])->toBe('warning')
        ->and($feedback['action'])->toBe('unpublish')
        ->and($feedback['attempted'])->toBe(2)
        ->and($feedback['updated'])->toBe(1)
        ->and($feedback['failures'])->toHaveCount(1)
        ->and($feedback['failures'][0]['id'])->toBe($missingId);

    expect($post->fresh()->isPublished())->toBeFalse();
});

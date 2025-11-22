<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;
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

it('filters categories by the search query string', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $matching = Category::factory()->create([
        'name' => 'Alpha Insights',
        'slug' => 'alpha-insights',
    ]);
    $other = Category::factory()->create([
        'name' => 'Zeta Daily',
        'slug' => 'zeta-daily',
    ]);

    actingAs($admin)
        ->get(route('admin.categories.index', ['search' => 'Alpha']))
        ->assertOk()
        ->assertSeeText($matching->name)
        ->assertDontSeeText($other->name);
});

it('sorts categories from the query string', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $alpha = Category::factory()->create([
        'name' => 'Alpha Category',
        'slug' => 'alpha-category',
        'updated_at' => now()->subDays(2),
    ]);
    $zulu = Category::factory()->create([
        'name' => 'Zulu Category',
        'slug' => 'zulu-category',
        'updated_at' => now()->subDay(),
    ]);

    $post = Post::factory()->create();
    $zulu->posts()->attach($post);

    actingAs($admin)
        ->get(route('admin.categories.index', ['sort' => 'posts_count', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder([$zulu->name, $alpha->name]);

    actingAs($admin)
        ->get(route('admin.categories.index', ['sort' => 'updated_at', 'direction' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder([$alpha->name, $zulu->name]);
});

it('toggles sort direction when selecting the same column', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('admin.categories.index')
        ->call('sortBy', 'updated_at')
        ->assertSet('sortField', 'updated_at')
        ->assertSet('sortDirection', 'asc')
        ->call('sortBy', 'updated_at')
        ->assertSet('sortDirection', 'desc');
});

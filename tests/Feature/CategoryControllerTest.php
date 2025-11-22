<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_index_page_can_be_rendered(): void
    {
        $response = $this->get(route('categories.index'));

        $response->assertStatus(200);
    }

    public function test_categories_index_displays_categories(): void
    {
        $categories = Category::factory()->count(3)->create();

        $response = $this->get(route('categories.index'));

        $response->assertStatus(200);
        foreach ($categories as $category) {
            $response->assertSee($category->name);
        }
    }

    public function test_category_show_page_displays_category_and_posts(): void
    {
        // Create a user first since PostFactory requires it
        \App\Models\User::factory()->create(['is_author' => true]);

        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'published_at' => now(),
        ]);
        $category->posts()->attach($post);

        $response = $this->get(route('categories.show', $category));

        $response->assertStatus(200);
        $response->assertSee($category->name);
        $response->assertSee($post->title);
    }

    public function test_category_can_be_created(): void
    {
        $categoryData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category',
        ];

        $response = $this->post(route('categories.store'), $categoryData);

        $this->assertDatabaseHas('categories', $categoryData);
        $response->assertRedirect(route('categories.index'));
    }

    public function test_category_can_be_updated(): void
    {
        $category = Category::factory()->create();

        $updatedData = [
            'name' => 'Updated Category',
            'slug' => 'updated-category',
            'description' => 'Updated description',
        ];

        $response = $this->put(route('categories.update', $category), $updatedData);

        $this->assertDatabaseHas('categories', $updatedData);
        $response->assertRedirect(route('categories.index'));
    }

    public function test_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $response = $this->delete(route('categories.destroy', $category));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $response->assertRedirect(route('categories.index'));
    }

    public function test_category_slug_must_be_unique(): void
    {
        $category = Category::factory()->create(['slug' => 'unique-slug']);

        $response = $this->post(route('categories.store'), [
            'name' => 'Another Category',
            'slug' => 'unique-slug',
            'description' => 'Description',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_category_name_is_required(): void
    {
        $response = $this->post(route('categories.store'), [
            'slug' => 'test-slug',
            'description' => 'Description',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_category_slug_is_required(): void
    {
        $response = $this->post(route('categories.store'), [
            'name' => 'Test Category',
            'description' => 'Description',
        ]);

        $response->assertSessionHasErrors('slug');
    }
}

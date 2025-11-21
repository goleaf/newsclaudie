<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some default categories
        $categories = [
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Articles about technology, programming, and software development.',
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'description' => 'Articles about lifestyle, health, and wellness.',
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Articles about business, entrepreneurship, and finance.',
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'description' => 'Articles about travel, destinations, and adventures.',
            ],
            [
                'name' => 'Food',
                'slug' => 'food',
                'description' => 'Articles about food, recipes, and culinary experiences.',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        // Create additional random categories
        Category::factory()->count(5)->create();
    }
}

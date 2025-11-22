<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

final class NewsSeeder extends Seeder
{
    private const TOTAL_NEWS_ITEMS = 10000;
    private const BATCH_SIZE = 1000;

    /**
     * Seed a large dataset of news posts.
     */
    public function run(): void
    {
        $this->ensureAuthorPool();
        $categoryIds = $this->ensureCategories();

        $existingCount = Post::count();
        if ($existingCount >= self::TOTAL_NEWS_ITEMS) {
            return;
        }

        $remaining = self::TOTAL_NEWS_ITEMS - $existingCount;
        while ($remaining > 0) {
            $batchSize = min(self::BATCH_SIZE, $remaining);

            $posts = Post::factory()->count($batchSize)->create();

            foreach ($posts as $post) {
                $selectionCount = min(count($categoryIds), max(1, rand(1, 3)));
                $selection = Arr::random($categoryIds, $selectionCount);
                $post->categories()->syncWithoutDetaching(
                    is_array($selection) ? $selection : [$selection]
                );
            }

            $remaining -= $batchSize;
        }
    }

    private function ensureAuthorPool(): void
    {
        $authorTarget = 10;
        $currentAuthors = User::where('is_author', true)->count();

        if ($currentAuthors >= $authorTarget) {
            return;
        }

        User::factory($authorTarget - $currentAuthors)->create([
            'is_author' => true,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function ensureCategories(): array
    {
        if (! Category::query()->exists()) {
            $this->call(CategorySeeder::class);
        }

        return Category::query()->pluck('id')->all();
    }
}



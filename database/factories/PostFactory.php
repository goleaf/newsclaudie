<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
final class PostFactory extends Factory
{
    /**
     * Cache authors to avoid repeated queries when generating large datasets.
     */
    private static ?Collection $authorPool = null;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->sentence();

        $user = $this->resolveAuthor();

        return [
            'user_id' => $user->id,
            'title' => $title,
            'slug' => $this->makeUniqueSlug($title),
            'description' => $this->faker->sentence(),
            'body' => $this->getMarkdown(),
            'featured_image' => $this->getFeaturedImage(),
            'published_at' => rand(0, 20) < 1 ? null : $this->faker->dateTimeThisYear(),
            'tags' => $this->getTags(),
        ];
    }

    /**
     * Generate some fake markdown text for the body.
     *
     * @return string
     */
    private function getMarkdown()
    {
        $faker = \Faker\Factory::create();
        $faker->addProvider(new \DavidBadura\FakerMarkdownGenerator\FakerProvider($faker));

        return $faker->markdown();
    }

    /**
     * Generate a seed and use picsum.photos to get a random image.
     *
     * @see https://picsum.photos/
     *
     * @return string
     */
    private function getFeaturedImage()
    {
        return 'https://picsum.photos/seed/'.rand(0, 99).'/960/640';
    }

    /**
     * Generate some tags.
     *
     * @return array|null $tags
     */
    private function getTags()
    {
        if (! config('blog.withTags')) {
            return [];
        }

        $array = [];
        $tagcount = rand(0, rand(1, 3)); // Generate a weighted number of tags

        for ($i = 0; $i < $tagcount; $i++) {
            $words = rand(1, rand(1, rand(1, 3))); // Generate a weighted number of words in the tag
            $array[] = $this->faker->words($words, true);
        }

        return $array;
    }

    private function resolveAuthor(): User
    {
        if (self::$authorPool === null) {
            self::$authorPool = User::query()->get();
        }

        if (self::$authorPool->isEmpty()) {
            $created = User::factory()->create([
                'is_author' => true,
            ]);

            self::$authorPool = collect([$created]);
        }

        $author = self::$authorPool->random();

        if (! $author->is_author) {
            $author->is_author = true; // Ensure the picked user can author posts
            $author->save();
        }

        return $author;
    }

    private function makeUniqueSlug(string $title): string
    {
        return Str::slug($title.'-'.Str::random(8));
    }

    /**
     * Indicate that the post is published.
     *
     * @return static
     */
    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }

    /**
     * Indicate that the post is a draft (unpublished).
     *
     * @return static
     */
    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => null,
            ];
        });
    }
}

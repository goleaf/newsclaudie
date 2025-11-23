<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
final class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'post_id' => function (array $attributes) {
                // If user_id is set, use it for the post's author
                $userId = $attributes['user_id'] ?? User::factory();
                return Post::factory()->for(
                    is_int($userId) ? User::find($userId) : $userId,
                    'author'
                );
            },
            'content' => $this->faker->paragraph(),
            'status' => CommentStatus::Pending,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => CommentStatus::Approved,
            'approved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'approved_by' => User::query()->where('is_admin', true)->inRandomOrder()->value('id'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => ['status' => CommentStatus::Rejected]);
    }

    public function randomStatus(): static
    {
        return $this->state(new Sequence(
            ['status' => CommentStatus::Approved],
            ['status' => CommentStatus::Pending],
            ['status' => CommentStatus::Rejected],
        ));
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommentStatus;
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
            'user_id' => User::pluck('id')->random(),
            'post_id' => Post::pluck('id')->random(),
            'content' => $this->faker->sentence(),
            'status' => CommentStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => CommentStatus::Approved]);
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

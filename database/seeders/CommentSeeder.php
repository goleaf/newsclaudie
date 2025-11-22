<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Comment;
use Illuminate\Database\Seeder;

final class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('blog.allowComments')) {
            Comment::factory()
                ->count(50)
                ->randomStatus()
                ->create();
        }
    }
}

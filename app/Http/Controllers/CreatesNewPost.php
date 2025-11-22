<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

final class CreatesNewPost extends Controller
{
    /**
     * Store a new blog post.
     *
     * @return Illuminate\Http\Response
     */
    public function store(User $user, array $input)
    {
        $post = Post::forceCreate(
            array_merge($input, [
                'user_id' => $user->id,
                'slug' => $this->getUniqueSlug($input['title']),
            ])
        );

        return redirect()->route('posts.show', ['post' => $post]);
    }

    /**
     * Generate a unique slug. Thanks to iwconnect for the idea of using the post id as a modifier.
     *
     * @see https://iwconnect.com/the-easiest-way-to-create-unique-slugs-for-blog-posts-in-laravel/
     */
    private function getUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);

        if (in_array($slug, ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'], true)) {
            $slug .= '-post';
        }

        $query = Post::withoutGlobalScopes();

        if ($query->where('slug', $slug)->exists()) {
            $nextId = ($query->max('id') ?? 0) + 1;

            return sprintf('%s-%d', $slug, $nextId);
        }

        return $slug;
    }
}

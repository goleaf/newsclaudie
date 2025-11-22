<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;

final class CreatesNewPost extends Controller
{
    public function createPost(User $user, array $input): Post
    {
        return Post::forceCreate(
            array_merge($input, [
                'user_id' => $user->id,
                'slug' => Post::generateUniqueSlug($input['title']),
            ])
        );
    }

    /**
     * Store a new blog post.
     *
     * @return Illuminate\Http\Response
     */
    public function store(User $user, array $input)
    {
        $post = $this->createPost($user, $input);

        return redirect()->route('posts.show', ['post' => $post]);
    }
}

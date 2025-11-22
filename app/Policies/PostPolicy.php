<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return Response|bool
     */
    public function view(?User $user, Post $post)
    {
        if ($post->isPublished()) {
            return true;
        }

        // Determine whether the user can view a post that is scheduled to be published in the future which inherits the update permissions.
        return $user->can('update', $post);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user)
    {
        return $user->is_admin || $user->is_author;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response
     */
    public function update(User $user, Post $post)
    {
        if ($user->is_admin === true) {
            return true;
        }

        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('You do not own this post.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response
     */
    public function delete(User $user, Post $post)
    {
        if ($user->is_admin === true) {
            return true;
        }

        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('You do not own this post.');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response
     */
    public function restore(User $user, Post $post)
    {
        if ($user->is_admin === true) {
            return true;
        }

        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('You do not own this post.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response
     */
    public function forceDelete(User $user, Post $post)
    {
        if ($user->is_admin === true) {
            return true;
        }

        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('You do not own this post.');
    }
}

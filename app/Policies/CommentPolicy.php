<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user)
    {
        if (! config('blog.allowComments')) {
            return Response::deny('Commenting is not allowed.');
        }

        if (config('blog.requireVerifiedEmailForComments') && ! $user->hasVerifiedEmail()) {
            return Response::deny('Your email must be verified to comment.');
        }

        if ($user->is_admin === true) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, Comment $comment)
    {
        if (! config('blog.allowComments')) {
            return Response::deny('Commenting is not allowed.');
        }

        if (config('blog.requireVerifiedEmailForComments') && ! $user->hasVerifiedEmail()) {
            return Response::deny('Your email must be verified to comment.');
        }

        if ($user->is_admin === true) {
            return true;
        }

        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::deny('You do not own this comment.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, Comment $comment)
    {
        if ($user->is_admin === true) {
            return true;
        }

        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::deny('You do not own this comment.');
    }
}

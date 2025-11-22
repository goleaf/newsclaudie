<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class UserPolicy
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
    public function view(User $user, User $model)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can update the model.
     *
     * Remember that this permission allows the user to create and remove admins!
     *
     * @return Response|bool
     */
    public function update(User $user, User $model)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, User $model)
    {
        if (! $user->is_admin) {
            return Response::deny('You are not an admin.');
        }

        if ($model->is_admin) {
            return Response::deny('You cannot delete an admin user. You must remove the permission first.');
        }

        if ($model->posts()->count()) {
            return Response::deny('You cannot delete a user that has posts. You must remove them first.');
        }

        return true;
    }

    /**
     * Determine whether the user can ban/unban the model.
     *
     * @return Response|bool
     */
    public function ban(User $user, User $model)
    {
        if (! $user->is_admin) {
            return Response::deny('You are not an admin.');
        }

        if ($model->is_admin) {
            return Response::deny('You cannot ban an admin user. You must remove the permission first.');
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, User $model)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, User $model)
    {
        //
    }
}

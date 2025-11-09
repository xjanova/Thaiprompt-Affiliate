<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PageBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;

class PageBuilderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any page builders.
     */
    public function viewAny(User $user): bool
    {
        // Allow if user is admin or has specific permission
        return $user->is_admin || $user->hasPermissionTo('page-builder.view');
    }

    /**
     * Determine whether the user can view the page builder.
     */
    public function view(User $user, PageBuilder $pageBuilder): bool
    {
        // Allow if user is admin or has specific permission
        return $user->is_admin || $user->hasPermissionTo('page-builder.view');
    }

    /**
     * Determine whether the user can create page builders.
     */
    public function create(User $user): bool
    {
        // Allow if user is admin or has specific permission
        return $user->is_admin || $user->hasPermissionTo('page-builder.create');
    }

    /**
     * Determine whether the user can update the page builder.
     */
    public function update(User $user, PageBuilder $pageBuilder): bool
    {
        // Allow if user is admin or has specific permission
        return $user->is_admin || $user->hasPermissionTo('page-builder.edit');
    }

    /**
     * Determine whether the user can delete the page builder.
     */
    public function delete(User $user, PageBuilder $pageBuilder): bool
    {
        // Prevent deletion of homepage
        if ($pageBuilder->page_type === 'homepage') {
            return false;
        }

        // Allow if user is admin or has specific permission
        return $user->is_admin || $user->hasPermissionTo('page-builder.delete');
    }

    /**
     * Determine whether the user can restore the page builder.
     */
    public function restore(User $user, PageBuilder $pageBuilder): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the page builder.
     */
    public function forceDelete(User $user, PageBuilder $pageBuilder): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can publish/activate the page builder.
     */
    public function publish(User $user, PageBuilder $pageBuilder): bool
    {
        return $user->is_admin || $user->hasPermissionTo('page-builder.publish');
    }

    /**
     * Determine whether the user can duplicate the page builder.
     */
    public function duplicate(User $user, PageBuilder $pageBuilder): bool
    {
        return $user->is_admin || $user->hasPermissionTo('page-builder.create');
    }
}

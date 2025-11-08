<?php

namespace App\Policies\BotAutomation;

use App\Models\User;
use App\Models\BotAutomation\BotAutomation;
use Illuminate\Auth\Access\HandlesAuthorization;

class BotAutomationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any automations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the automation.
     */
    public function view(User $user, BotAutomation $automation): bool
    {
        return $user->id === $automation->user_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can create automations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the automation.
     */
    public function update(User $user, BotAutomation $automation): bool
    {
        return $user->id === $automation->user_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can delete the automation.
     */
    public function delete(User $user, BotAutomation $automation): bool
    {
        return $user->id === $automation->user_id || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can execute the automation.
     */
    public function execute(User $user, BotAutomation $automation): bool
    {
        return $user->id === $automation->user_id || $user->hasRole(['admin', 'super_admin']);
    }
}

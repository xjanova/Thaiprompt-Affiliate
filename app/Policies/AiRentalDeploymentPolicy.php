<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AiRentalDeployment;

/**
 * AI Rental Deployment Policy
 *
 * จัดการสิทธิ์การเข้าถึง Deployments
 */
class AiRentalDeploymentPolicy
{
    /**
     * ดู Deployment
     *
     * @param User $user
     * @param AiRentalDeployment $deployment
     * @return bool
     */
    public function view(User $user, AiRentalDeployment $deployment): bool
    {
        // เจ้าของหรือ admin เท่านั้น
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * สร้าง Deployment
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // ทุกคนที่ login แล้วสร้างได้
        return true;
    }

    /**
     * แก้ไข Deployment
     *
     * @param User $user
     * @param AiRentalDeployment $deployment
     * @return bool
     */
    public function update(User $user, AiRentalDeployment $deployment): bool
    {
        // เจ้าของหรือ admin เท่านั้น
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * ลบ Deployment
     *
     * @param User $user
     * @param AiRentalDeployment $deployment
     * @return bool
     */
    public function delete(User $user, AiRentalDeployment $deployment): bool
    {
        // เจ้าของหรือ admin เท่านั้น
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * Start Deployment
     *
     * @param User $user
     * @param AiRentalDeployment $deployment
     * @return bool
     */
    public function start(User $user, AiRentalDeployment $deployment): bool
    {
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * Stop Deployment
     *
     * @param User $user
     * @param AiRentalDeployment $deployment
     * @return bool
     */
    public function stop(User $user, AiRentalDeployment $deployment): bool
    {
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * Restart Deployment
     *
     * @param User $user
     * @param AiRentalDeployment $deployment
     * @return bool
     */
    public function restart(User $user, AiRentalDeployment $deployment): bool
    {
        return $user->id === $deployment->user_id || $user->is_admin;
    }
}

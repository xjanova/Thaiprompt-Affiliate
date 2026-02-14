<?php

namespace App\Policies;

use App\Models\AiRentalDeployment;
use App\Models\User;

/**
 * AI Rental Deployment Policy
 *
 * จัดการสิทธิ์การเข้าถึง Deployments
 */
class AiRentalDeploymentPolicy
{
    /**
     * ดู Deployment
     */
    public function view(User $user, AiRentalDeployment $deployment): bool
    {
        // เจ้าของหรือ admin เท่านั้น
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * สร้าง Deployment
     */
    public function create(User $user): bool
    {
        // ทุกคนที่ login แล้วสร้างได้
        return true;
    }

    /**
     * แก้ไข Deployment
     */
    public function update(User $user, AiRentalDeployment $deployment): bool
    {
        // เจ้าของหรือ admin เท่านั้น
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * ลบ Deployment
     */
    public function delete(User $user, AiRentalDeployment $deployment): bool
    {
        // เจ้าของหรือ admin เท่านั้น
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * Start Deployment
     */
    public function start(User $user, AiRentalDeployment $deployment): bool
    {
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * Stop Deployment
     */
    public function stop(User $user, AiRentalDeployment $deployment): bool
    {
        return $user->id === $deployment->user_id || $user->is_admin;
    }

    /**
     * Restart Deployment
     */
    public function restart(User $user, AiRentalDeployment $deployment): bool
    {
        return $user->id === $deployment->user_id || $user->is_admin;
    }
}

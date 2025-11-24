<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AiRentalCloudConfig;

/**
 * Policy สำหรับ AiRentalCloudConfig
 *
 * ผู้ใช้สามารถจัดการเฉพาะ config ของตัวเองได้
 */
class AiRentalCloudConfigPolicy
{
    /**
     * ดูข้อมูล config
     */
    public function view(User $user, AiRentalCloudConfig $config): bool
    {
        return $user->id === $config->user_id || $user->is_admin;
    }

    /**
     * แก้ไข config
     */
    public function update(User $user, AiRentalCloudConfig $config): bool
    {
        return $user->id === $config->user_id || $user->is_admin;
    }

    /**
     * ลบ config
     */
    public function delete(User $user, AiRentalCloudConfig $config): bool
    {
        return $user->id === $config->user_id || $user->is_admin;
    }
}

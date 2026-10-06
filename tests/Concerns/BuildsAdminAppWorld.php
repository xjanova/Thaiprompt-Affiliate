<?php

namespace Tests\Concerns;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * ตัวช่วยเทสต์ของแอปแอดมิน (/api/admin/*) — สร้างแอดมิน/บิลดูดวงแบบเจาะจงคอลัมน์
 */
trait BuildsAdminAppWorld
{
    /** แอดมินธรรมดา (role = admin) */
    protected function makeAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'admin', 'is_super_admin' => false], $attrs));
    }

    /** super admin */
    protected function makeSuperAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'admin', 'is_super_admin' => true], $attrs));
    }

    /** สมาชิกทั่วไป */
    protected function makeMember(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'is_super_admin' => false], $attrs));
    }

    /** ล็อกอินด้วย token ability admin (เหมือนที่ /api/admin/auth/login ออกให้) */
    protected function actAs(User $user): User
    {
        Sanctum::actingAs($user, ['admin']);

        return $user;
    }

    /**
     * แถว fortune_readings — forceFill เพราะบางคอลัมน์ไม่อยู่ใน $fillable · timestamps ตั้งเองได้ผ่าน $attrs
     *
     * @param  array<string, mixed>  $attrs
     * @param  array<string, mixed>|null  $state
     */
    protected function makeReading(array $attrs = [], ?array $state = null): FortuneReading
    {
        $reading = new FortuneReading;
        $reading->timestamps = ! array_key_exists('updated_at', $attrs);
        $reading->forceFill(array_merge([
            'platform' => 'facebook',
            'platform_user_id' => '61550000000077',
            'facebook_user_id' => '61550000000077',
            'facebook_user_name' => 'ลูกค้าทดสอบ',
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'questions' => [],
            'is_paid' => false,
            'amount_paid' => 0,
            'created_at' => $attrs['created_at'] ?? now(),
        ], $attrs, ['conversation_state' => $state]))->save();

        return $reading->fresh();
    }

    protected function tearDownAdminAppWorld(): void
    {
        FortuneTellingSetting::clearSettingsCache();
    }
}

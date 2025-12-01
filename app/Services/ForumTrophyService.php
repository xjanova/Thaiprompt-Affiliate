<?php

namespace App\Services;

use App\Models\ForumTrophy;
use App\Models\User;

/**
 * ForumTrophyService - บริการจัดการโทรฟี่ในฟอรั่ม
 *
 * รับผิดชอบการตรวจสอบและมอบโทรฟี่อัตโนมัติให้ผู้ใช้
 * รองรับทั้งโทรฟี่ดี (positive) และไม่ดี (negative)
 *
 * @example
 * $service = app(ForumTrophyService::class);
 * $service->checkAndAwardTrophies($user);
 */
class ForumTrophyService
{
    /**
     * ตรวจสอบและมอบโทรฟี่ให้ผู้ใช้ (โทรฟี่ดี)
     *
     * @param User $user
     * @return array รายการโทรฟี่ที่ได้รับใหม่
     */
    public function checkAndAwardTrophies(User $user): array
    {
        $awarded = [];

        // ดึงโทรฟี่อัตโนมัติที่ยังไม่ได้รับ
        $trophies = ForumTrophy::active()
            ->positive()
            ->automatic()
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($trophies as $trophy) {
            if ($trophy->isQualified($user)) {
                $trophy->awardTo($user, null, 'ได้รับอัตโนมัติจากระบบ');
                $awarded[] = $trophy;
            }
        }

        return $awarded;
    }

    /**
     * ตรวจสอบและมอบโทรฟี่ไม่ดีให้ผู้ใช้
     *
     * @param User $user
     * @return array รายการโทรฟี่ที่ได้รับใหม่
     */
    public function checkAndAwardNegativeTrophies(User $user): array
    {
        $awarded = [];

        // ดึงโทรฟี่ไม่ดีอัตโนมัติที่ยังไม่ได้รับ
        $trophies = ForumTrophy::active()
            ->negative()
            ->automatic()
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($trophies as $trophy) {
            if ($trophy->isQualified($user)) {
                $trophy->awardTo($user, null, 'ได้รับอัตโนมัติจากระบบเนื่องจากพฤติกรรมไม่เหมาะสม');
                $awarded[] = $trophy;
            }
        }

        return $awarded;
    }

    /**
     * มอบโทรฟี่ด้วยตนเอง
     *
     * @param User $user ผู้ได้รับ
     * @param ForumTrophy $trophy โทรฟี่
     * @param User $awardedBy ผู้มอบ
     * @param string|null $reason เหตุผล
     * @return bool
     */
    public function awardManually(User $user, ForumTrophy $trophy, User $awardedBy, ?string $reason = null): bool
    {
        return $trophy->awardTo($user, $awardedBy, $reason);
    }

    /**
     * ถอนโทรฟี่ (ไม่ได้สำหรับ permanent)
     *
     * @param User $user
     * @param ForumTrophy $trophy
     * @return bool
     */
    public function revoke(User $user, ForumTrophy $trophy): bool
    {
        return $trophy->revokeFrom($user);
    }

    /**
     * ดึงโทรฟี่ทั้งหมดของผู้ใช้
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserTrophies(User $user)
    {
        return $user->forumTrophies()
            ->orderBy('type')
            ->orderBy('display_order')
            ->get();
    }

    /**
     * ดึงโทรฟี่ที่แสดงหลังชื่อ (โทรฟี่หลัก)
     *
     * @param User $user
     * @param int $limit จำนวนที่แสดง
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDisplayTrophies(User $user, int $limit = 3)
    {
        return $user->forumTrophies()
            ->orderByDesc('type') // negative มาก่อน (ติดตลอด)
            ->orderBy('display_order')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงสีชื่อที่จะแสดง (จากโทรฟี่ที่มีสีชื่อ)
     *
     * @param User $user
     * @return string|null
     */
    public function getNameColor(User $user): ?string
    {
        // ดึงโทรฟี่ที่มี name_color (negative มาก่อน)
        $trophy = $user->forumTrophies()
            ->whereNotNull('name_color')
            ->orderByDesc('type') // negative มาก่อน
            ->orderByDesc('pivot_awarded_at')
            ->first();

        return $trophy?->name_color;
    }

    /**
     * ตรวจสอบความก้าวหน้าสู่โทรฟี่ถัดไป
     *
     * @param User $user
     * @return array
     */
    public function getProgress(User $user): array
    {
        $progress = [];

        // ดึงโทรฟี่ดีที่ยังไม่ได้รับ
        $trophies = ForumTrophy::active()
            ->positive()
            ->automatic()
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->ordered()
            ->get();

        foreach ($trophies as $trophy) {
            $current = match ($trophy->requirement_type) {
                'likes_received' => $user->forum_likes_received ?? 0,
                'posts_count' => $user->forum_posts_count ?? 0,
                'threads_count' => $user->forum_threads_count ?? 0,
                'best_answers_count' => $user->forum_best_answers_count ?? 0,
                default => 0,
            };

            $progress[] = [
                'trophy' => $trophy,
                'current' => $current,
                'required' => $trophy->requirement_value,
                'percentage' => min(100, round(($current / max(1, $trophy->requirement_value)) * 100)),
            ];
        }

        return $progress;
    }
}

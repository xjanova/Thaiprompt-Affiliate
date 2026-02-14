<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class UserVideoLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_level_id',
        'current_exp',
        'total_exp',
        'purchased_stars',
        'purchased_star_color',
        'last_star_upgrade_at',
        'videos_watched',
        'quests_completed',
        'total_coins_earned',
        'last_level_up_at',
    ];

    protected $casts = [
        'current_exp' => 'integer',
        'total_exp' => 'integer',
        'purchased_stars' => 'integer',
        'videos_watched' => 'integer',
        'quests_completed' => 'integer',
        'total_coins_earned' => 'decimal:2',
        'last_level_up_at' => 'datetime',
        'last_star_upgrade_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'purchased_stars' => 0,
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get current level
     */
    public function currentLevel()
    {
        return $this->belongsTo(VideoLevel::class, 'current_level_id');
    }

    /**
     * Add experience
     */
    public function addExp($amount)
    {
        $this->current_exp += $amount;
        $this->total_exp += $amount;
        $this->save();

        // Check for level up
        return $this->checkLevelUp();
    }

    /**
     * Check and process level up
     */
    public function checkLevelUp()
    {
        $currentLevel = $this->currentLevel;
        $nextLevel = $currentLevel->next_level;

        if ($nextLevel && $this->current_exp >= $nextLevel->required_exp) {
            $this->levelUp($nextLevel);

            return true;
        }

        return false;
    }

    /**
     * Level up
     */
    protected function levelUp($newLevel)
    {
        $oldLevel = $this->currentLevel;

        $this->current_level_id = $newLevel->id;
        $this->current_exp = $this->current_exp - $newLevel->required_exp; // Carry over excess EXP
        $this->last_level_up_at = now();
        $this->save();

        // Award level up bonus
        if ($newLevel->coin_bonus > 0) {
            $coinModel = VideoCoin::firstOrCreate(['user_id' => $this->user_id]);
            $coinModel->addCoins($newLevel->coin_bonus, 'earned_level_up', 'VideoLevel', $newLevel->id, "Level up to {$newLevel->name}");
        }

        // Check if can level up again (multiple levels at once)
        $this->checkLevelUp();
    }

    /**
     * Increment videos watched
     */
    public function incrementVideosWatched()
    {
        $this->increment('videos_watched');
    }

    /**
     * Increment quests completed
     */
    public function incrementQuestsCompleted()
    {
        $this->increment('quests_completed');
    }

    /**
     * Get progress to next level
     */
    public function getProgressToNextLevelAttribute()
    {
        $nextLevel = $this->currentLevel->next_level;

        if (! $nextLevel) {
            return 100; // Max level
        }

        return ($this->current_exp / $nextLevel->required_exp) * 100;
    }

    // ==========================================
    // ระบบดาว (Star System)
    // ==========================================

    /**
     * ประวัติการอัพเกรดดาว
     */
    public function starUpgradeHistory(): HasMany
    {
        return $this->hasMany(StarUpgradeHistory::class, 'user_id', 'user_id');
    }

    /**
     * จำนวนดาวที่แสดง (สูงสุดระหว่าง Level-based กับ Purchased)
     */
    public function getEffectiveStarsAttribute(): int
    {
        $levelStars = $this->currentLevel?->stars ?? 1;
        $purchasedStars = $this->purchased_stars ?? 0;

        return max($levelStars, $purchasedStars);
    }

    /**
     * สีดาวที่แสดง (ตาม purchased ถ้ามี, ไม่งั้นตาม level)
     */
    public function getEffectiveStarColorAttribute(): string
    {
        // ถ้ามีซื้อดาวและซื้อสูงกว่า level
        if ($this->purchased_stars > 0 && $this->purchased_stars >= ($this->currentLevel?->stars ?? 1)) {
            return $this->purchased_star_color ?? 'bronze';
        }

        return $this->currentLevel?->star_color ?? 'bronze';
    }

    /**
     * ข้อมูลสีดาวที่แสดง
     */
    public function getEffectiveStarColorInfoAttribute(): array
    {
        return StarUpgradePrice::STAR_COLORS[$this->effective_star_color] ?? StarUpgradePrice::STAR_COLORS['bronze'];
    }

    /**
     * Hex สีดาว
     */
    public function getEffectiveStarColorHexAttribute(): string
    {
        return $this->effective_star_color_info['hex'];
    }

    /**
     * Gradient class สีดาว
     */
    public function getEffectiveStarGradientAttribute(): string
    {
        return $this->effective_star_color_info['gradient'];
    }

    /**
     * ตรวจสอบว่าอัพเกรดดาวได้หรือไม่
     *
     * @param  int  $targetStars  ดาวที่ต้องการ
     * @return array ['can_upgrade' => bool, 'reason' => string|null, 'price' => float|null]
     */
    public function canUpgradeStars(int $targetStars): array
    {
        $currentStars = $this->purchased_stars ?? 0;

        // ตรวจสอบว่าดาวไม่ถอยหลัง
        if ($targetStars <= $currentStars) {
            return [
                'can_upgrade' => false,
                'reason' => 'คุณมีดาวระดับนี้อยู่แล้ว',
                'price' => null,
            ];
        }

        // ตรวจสอบว่าดาวไม่เกิน 5
        if ($targetStars > 5) {
            return [
                'can_upgrade' => false,
                'reason' => 'ดาวสูงสุดคือ 5 ดาว',
                'price' => null,
            ];
        }

        // ดึงราคา
        $priceData = StarUpgradePrice::getByStars($targetStars);
        if (! $priceData || ! $priceData->is_active) {
            return [
                'can_upgrade' => false,
                'reason' => 'ระดับดาวนี้ยังไม่เปิดให้ซื้อ',
                'price' => null,
            ];
        }

        // ตรวจสอบ Level ขั้นต่ำ
        $userLevel = $this->currentLevel?->level ?? 1;
        if ($userLevel < $priceData->min_level) {
            return [
                'can_upgrade' => false,
                'reason' => "ต้องมี Level อย่างน้อย {$priceData->min_level} จึงจะซื้อได้",
                'price' => null,
            ];
        }

        // ตรวจสอบว่าต้องมีดาวก่อนหน้าหรือไม่
        if ($priceData->require_previous_star && $targetStars > 1 && $currentStars < ($targetStars - 1)) {
            return [
                'can_upgrade' => false,
                'reason' => 'ต้องอัพเกรดดาวตามลำดับ (ต้องมี '.($targetStars - 1).' ดาวก่อน)',
                'price' => null,
            ];
        }

        // คำนวณราคา
        $price = StarUpgradePrice::calculateUpgradePrice($currentStars, $targetStars);
        if ($price === null) {
            return [
                'can_upgrade' => false,
                'reason' => 'ไม่สามารถคำนวณราคาได้',
                'price' => null,
            ];
        }

        // ตรวจสอบ Coins
        $user = $this->user;
        $coinBalance = $user->videoCoin?->balance ?? 0;
        if ($coinBalance < $price) {
            return [
                'can_upgrade' => false,
                'reason' => 'Coins ไม่เพียงพอ (ต้องการ '.number_format($price, 2).' Coins)',
                'price' => $price,
            ];
        }

        return [
            'can_upgrade' => true,
            'reason' => null,
            'price' => $price,
        ];
    }

    /**
     * อัพเกรดดาว
     *
     * @param  int  $targetStars  ดาวที่ต้องการ
     * @return array ['success' => bool, 'message' => string, 'history' => StarUpgradeHistory|null]
     */
    public function upgradeStars(int $targetStars, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        // ตรวจสอบว่าอัพเกรดได้หรือไม่
        $check = $this->canUpgradeStars($targetStars);
        if (! $check['can_upgrade']) {
            return [
                'success' => false,
                'message' => $check['reason'],
                'history' => null,
            ];
        }

        $price = $check['price'];
        $currentStars = $this->purchased_stars ?? 0;

        // ดึงข้อมูลสีดาว
        $priceData = StarUpgradePrice::getByStars($targetStars);

        DB::beginTransaction();
        try {
            // หัก Coins
            $user = $this->user;
            $videoCoin = $user->videoCoin;
            $videoCoin->subtractBalance($price, "อัพเกรดดาว: {$currentStars} -> {$targetStars} ดาว");

            // อัพเดทดาว
            $this->purchased_stars = $targetStars;
            $this->purchased_star_color = $priceData->star_color;
            $this->last_star_upgrade_at = now();
            $this->save();

            // บันทึกประวัติ
            $history = StarUpgradeHistory::create([
                'user_id' => $this->user_id,
                'from_stars' => $currentStars,
                'to_stars' => $targetStars,
                'coins_paid' => $price,
                'status' => 'completed',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "อัพเกรดเป็น {$targetStars} ดาวสำเร็จ!",
                'history' => $history,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
                'history' => null,
            ];
        }
    }
}

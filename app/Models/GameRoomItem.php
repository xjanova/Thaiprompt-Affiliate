<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * GameRoomItem Model
 *
 * จัดการไอเทมในห้องเกม Snake (อาหาร, powerups)
 */
class GameRoomItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'item_type',
        'position',
        'value',
        'spawned_at',
        'expires_at',
        'is_collected',
        'collected_by',
        'collected_at',
    ];

    protected $casts = [
        'position' => 'array',
        'spawned_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_collected' => 'boolean',
        'collected_at' => 'datetime',
    ];

    /**
     * ประเภทไอเทม
     */
    const TYPE_FOOD = 'food';
    const TYPE_POWERUP_MAGNET = 'powerup_magnet';
    const TYPE_POWERUP_SPEED = 'powerup_speed';
    const TYPE_POWERUP_MULTIPLIER = 'powerup_multiplier';

    /**
     * ระยะเวลาหมดอายุของ powerup (1 นาที)
     */
    const POWERUP_EXPIRE_SECONDS = 60;

    /**
     * ระยะเวลา spawn ไอเทมใหม่ (20 วินาที)
     */
    const SPAWN_INTERVAL_SECONDS = 20;

    /**
     * Relationships
     */
    public function room()
    {
        return $this->belongsTo(GameRoom::class, 'room_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(GameRoomPlayer::class, 'collected_by');
    }

    /**
     * ตรวจสอบว่าไอเทมหมดอายุหรือยัง
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->greaterThan($this->expires_at);
    }

    /**
     * เก็บไอเทมโดยผู้เล่น
     *
     * @param int $playerId
     * @return void
     */
    public function collect(int $playerId): void
    {
        $this->update([
            'is_collected' => true,
            'collected_by' => $playerId,
            'collected_at' => now(),
        ]);
    }

    /**
     * ลบไอเทมที่หมดอายุ
     *
     * @param int $roomId
     * @return int จำนวนไอเทมที่ถูกลบ
     */
    public static function removeExpired(int $roomId): int
    {
        return static::where('room_id', $roomId)
            ->where('is_collected', false)
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * สร้างไอเทมอาหารใหม่
     *
     * @param int $roomId
     * @param array $position
     * @param int $value
     * @return self
     */
    public static function spawnFood(int $roomId, array $position, int $value = 1): self
    {
        return static::create([
            'room_id' => $roomId,
            'item_type' => self::TYPE_FOOD,
            'position' => $position,
            'value' => $value,
            'spawned_at' => now(),
            'expires_at' => null, // อาหารไม่หมดอายุ
        ]);
    }

    /**
     * สร้างไอเทม powerup ใหม่
     *
     * @param int $roomId
     * @param string $type
     * @param array $position
     * @return self
     */
    public static function spawnPowerup(int $roomId, string $type, array $position): self
    {
        return static::create([
            'room_id' => $roomId,
            'item_type' => $type,
            'position' => $position,
            'value' => 0,
            'spawned_at' => now(),
            'expires_at' => now()->addSeconds(self::POWERUP_EXPIRE_SECONDS),
        ]);
    }

    /**
     * ดึงไอเทมที่ยังไม่ถูกเก็บในห้อง
     *
     * @param int $roomId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveItems(int $roomId)
    {
        return static::where('room_id', $roomId)
            ->where('is_collected', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }
}

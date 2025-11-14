<?php

namespace App\Jobs;

use App\Models\GameRoom;
use App\Services\GameRoomManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * SpawnGameRoomItems Job
 *
 * ทำงานเป็นระยะเพื่อ spawn ไอเทมในห้องเกม Snake.io ที่กำลังเล่นอยู่
 * - Spawn powerup ทุก 20 วินาที
 * - รักษาจำนวนอาหารให้เพียงพอ
 * - ลบไอเทมที่หมดอายุ
 */
class SpawnGameRoomItems implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GameRoomManager $roomManager): void
    {
        Log::info('[SpawnGameRoomItems] เริ่มต้น job...');

        // ดึงห้องที่กำลัง active อยู่
        $activeRooms = GameRoom::where('game_id', function ($query) {
                $query->select('id')
                    ->from('games')
                    ->where('slug', 'snake-io')
                    ->limit(1);
            })
            ->whereIn('status', ['waiting', 'in_progress'])
            ->where('current_players', '>', 0) // มีผู้เล่นอยู่
            ->get();

        Log::info('[SpawnGameRoomItems] พบห้อง active:', ['count' => $activeRooms->count()]);

        foreach ($activeRooms as $room) {
            try {
                // ลบไอเทมหมดอายุและ spawn อาหารใหม่ถ้าจำนวนน้อย
                $roomManager->maintainItems($room->id);

                // สุ่ม spawn powerup (โอกาส 30%)
                if (rand(1, 100) <= 30) {
                    $roomManager->spawnRandomPowerup($room->id);
                    Log::info('[SpawnGameRoomItems] Spawn powerup ในห้อง:', ['room_id' => $room->id]);
                }

            } catch (\Exception $e) {
                Log::error('[SpawnGameRoomItems] เกิดข้อผิดพลาดในห้อง:', [
                    'room_id' => $room->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[SpawnGameRoomItems] เสร็จสิ้น job');
    }

    /**
     * จำนวนครั้งที่ job สามารถ retry ได้
     *
     * @var int
     */
    public $tries = 3;

    /**
     * ระยะเวลา timeout ของ job (วินาที)
     *
     * @var int
     */
    public $timeout = 120;
}

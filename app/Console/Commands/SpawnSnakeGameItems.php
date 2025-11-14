<?php

namespace App\Console\Commands;

use App\Jobs\SpawnGameRoomItems;
use Illuminate\Console\Command;

/**
 * SpawnSnakeGameItems Command
 *
 * ส่ง job SpawnGameRoomItems ไปยัง queue
 * ควรถูกเรียกใช้โดย scheduler ทุก 20 วินาที
 */
class SpawnSnakeGameItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snake-game:spawn-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch job เพื่อ spawn ไอเทมในห้องเกม Snake.io ที่กำลัง active';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('[Snake Game] กำลัง dispatch job SpawnGameRoomItems...');

        // Dispatch job ไปยัง queue
        SpawnGameRoomItems::dispatch();

        $this->info('[Snake Game] ส่ง job สำเร็จ');

        return Command::SUCCESS;
    }
}

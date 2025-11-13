<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GameMission;
use Carbon\Carbon;

class MissionsSeeder extends Seeder
{
    public function run(): void
    {
        $snakeGame = Game::where('slug', 'snake-io')->first();

        if (!$snakeGame) {
            $this->command->info('Snake.io game not found. Please run GamesSeeder first.');
            return;
        }

        $missions = [
            // Daily Missions
            [
                'game_id' => $snakeGame->id,
                'slug' => 'daily-play-3-games',
                'name' => 'Play 3 Games',
                'description' => 'Play Snake.io 3 times today',
                'type' => 'daily',
                'objective' => 'play_games',
                'target' => 3,
                'reward_type' => 'coins',
                'reward_amount' => 20,
                'reward_points' => 10,
                'difficulty' => 1,
            ],
            [
                'game_id' => $snakeGame->id,
                'slug' => 'daily-score-5000',
                'name' => 'Score 5,000 Points',
                'description' => 'Reach a score of 5,000 in a single game',
                'type' => 'daily',
                'objective' => 'reach_score',
                'target' => 5000,
                'reward_type' => 'coins',
                'reward_amount' => 30,
                'reward_points' => 15,
                'difficulty' => 2,
            ],
            [
                'game_id' => $snakeGame->id,
                'slug' => 'daily-survive-3min',
                'name' => 'Survive 3 Minutes',
                'description' => 'Stay alive for 3 minutes in a single game',
                'type' => 'daily',
                'objective' => 'survive_time',
                'target' => 180, // seconds
                'reward_type' => 'coins',
                'reward_amount' => 25,
                'reward_points' => 12,
                'difficulty' => 2,
            ],
            [
                'game_id' => $snakeGame->id,
                'slug' => 'daily-eat-50-food',
                'name' => 'Eat 50 Food',
                'description' => 'Consume 50 food items in a single game',
                'type' => 'daily',
                'objective' => 'kill_count',
                'target' => 50,
                'reward_type' => 'coins',
                'reward_amount' => 35,
                'reward_points' => 18,
                'difficulty' => 3,
            ],

            // Weekly Missions
            [
                'game_id' => $snakeGame->id,
                'slug' => 'weekly-play-20-games',
                'name' => 'Play 20 Games',
                'description' => 'Play Snake.io 20 times this week',
                'type' => 'weekly',
                'objective' => 'play_games',
                'target' => 20,
                'reward_type' => 'coins',
                'reward_amount' => 150,
                'reward_points' => 50,
                'difficulty' => 2,
            ],
            [
                'game_id' => $snakeGame->id,
                'slug' => 'weekly-score-50000',
                'name' => 'Score 50,000 Points',
                'description' => 'Reach a cumulative score of 50,000 this week',
                'type' => 'weekly',
                'objective' => 'reach_score',
                'target' => 50000,
                'reward_type' => 'coins',
                'reward_amount' => 200,
                'reward_points' => 75,
                'difficulty' => 3,
            ],
            [
                'game_id' => $snakeGame->id,
                'slug' => 'weekly-win-5-games',
                'name' => 'Win 5 Games',
                'description' => 'Finish in 1st place 5 times this week',
                'type' => 'weekly',
                'objective' => 'kill_count',
                'target' => 5,
                'reward_type' => 'coins',
                'reward_amount' => 250,
                'reward_points' => 100,
                'difficulty' => 4,
            ],
            [
                'game_id' => $snakeGame->id,
                'slug' => 'weekly-master-challenge',
                'name' => 'Master Challenge',
                'description' => 'Score 10,000+ in a single game',
                'type' => 'weekly',
                'objective' => 'reach_score',
                'target' => 10000,
                'reward_type' => 'coins',
                'reward_amount' => 500,
                'reward_points' => 200,
                'difficulty' => 5,
            ],
        ];

        foreach ($missions as $mission) {
            GameMission::updateOrCreate(
                ['slug' => $mission['slug']],
                $mission
            );
        }

        $this->command->info('Missions seeded successfully!');
    }
}

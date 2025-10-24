<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MlmRank;

class MlmRankSeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'description' => 'Entry level rank',
                'level' => 1,
                'required_sales' => 0,
                'required_team_sales' => 0,
                'required_direct_referrals' => 0,
                'bonus_percentage' => 0,
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'description' => 'Second rank',
                'level' => 2,
                'required_sales' => 10000,
                'required_team_sales' => 50000,
                'required_direct_referrals' => 3,
                'bonus_percentage' => 2,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'description' => 'Third rank',
                'level' => 3,
                'required_sales' => 50000,
                'required_team_sales' => 200000,
                'required_direct_referrals' => 5,
                'bonus_percentage' => 5,
            ],
            [
                'name' => 'Platinum',
                'slug' => 'platinum',
                'description' => 'Fourth rank',
                'level' => 4,
                'required_sales' => 100000,
                'required_team_sales' => 500000,
                'required_direct_referrals' => 10,
                'bonus_percentage' => 10,
            ],
            [
                'name' => 'Diamond',
                'slug' => 'diamond',
                'description' => 'Top rank',
                'level' => 5,
                'required_sales' => 500000,
                'required_team_sales' => 2000000,
                'required_direct_referrals' => 20,
                'bonus_percentage' => 15,
            ],
        ];

        foreach ($ranks as $rank) {
            MlmRank::create($rank);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Default feature-flag state. All flags ship OFF by default — flip ON via
 * admin UI only when the backing feature is proven in staging.
 *
 * Run with:  php artisan db:seed --class=FeatureFlagSeeder
 */
class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            [
                'flag_key'         => 'ai_enabled',
                'enabled'          => false,
                'description'      => 'Master toggle for น้องหญิง AI chat (FAB + sheet).',
                'rollout_percent'  => 100,
            ],
            [
                'flag_key'         => 'tts_enabled',
                'enabled'          => false,
                'description'      => 'Enable "ฟังเสียง" button under assistant replies.',
                'rollout_percent'  => 100,
            ],
            [
                'flag_key'         => 'wallet_topup_enabled',
                'enabled'          => true,
                'description'      => 'Shows wallet top-up flows.',
                'rollout_percent'  => 100,
            ],
            [
                'flag_key'         => 'wallet_transfer_enabled',
                'enabled'          => false,
                'description'      => 'Shows wallet → wallet transfer (PIN).',
                'rollout_percent'  => 100,
            ],
            [
                'flag_key'         => 'affiliate_enabled',
                'enabled'          => true,
                'description'      => 'Shows Affiliate tab + home widget.',
                'rollout_percent'  => 100,
            ],
            [
                'flag_key'         => 'analytics_enabled',
                'enabled'          => true,
                'description'      => 'Collect anonymised analytics events.',
                'rollout_percent'  => 100,
            ],
            [
                'flag_key'         => 'force_update',
                'enabled'          => false,
                'description'      => 'When ON, UpdateObserver treats old builds as mandatory.',
                'rollout_percent'  => 100,
            ],
        ];

        foreach ($rows as $r) {
            DB::table('feature_flags')->upsert(
                [
                    'flag_key'         => $r['flag_key'],
                    'enabled'          => $r['enabled'],
                    'description'      => $r['description'],
                    'rollout_percent'  => $r['rollout_percent'],
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ],
                ['flag_key'],
                ['enabled', 'description', 'rollout_percent', 'updated_at']
            );
        }

        $this->command?->info('Seeded ' . count($rows) . ' feature_flags');
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ Wallet
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => fake()->randomFloat(2, 0, 10000),
            'currency' => 'THB',
            'wallet_address' => 'TPW'.strtoupper(Str::random(16)),
            'status' => 'active',
            'two_factor_enabled' => false,
            'failed_attempts' => 0,
            'total_income' => 0,
            'total_expense' => 0,
        ];
    }
}

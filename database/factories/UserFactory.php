<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ User
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'referral_code' => strtoupper(Str::random(8)),
            'member_number' => 'TP'.fake()->unique()->numerify('######'),
            'phone' => fake()->phoneNumber(),
            'preferred_language' => 'th',
        ];
    }

    /**
     * สร้าง admin user
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin '.fake()->name(),
        ]);
    }
}

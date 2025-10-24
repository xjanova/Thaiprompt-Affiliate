<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'referral_code' => 'ADM12345',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');
        Wallet::create(['user_id' => $admin->id]);

        // Create Vendor
        $vendor = User::create([
            'name' => 'Vendor User',
            'email' => 'vendor@example.com',
            'password' => Hash::make('password'),
            'referral_code' => 'VEN12345',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $vendor->assignRole('vendor');
        Wallet::create(['user_id' => $vendor->id]);

        // Create vendor shop
        $vendor->vendor()->create([
            'shop_name' => 'Demo Shop',
            'shop_slug' => 'demo-shop',
            'shop_description' => 'This is a demo shop',
            'status' => 'active',
            'commission_rate' => 70,
        ]);

        // Create Customer
        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'referral_code' => 'CUS12345',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('customer');
        Wallet::create(['user_id' => $customer->id]);
    }
}

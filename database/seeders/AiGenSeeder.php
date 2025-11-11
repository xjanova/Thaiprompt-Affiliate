<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiGenProvider;
use App\Models\AiGenPackage;
use App\Models\AiGenQuota;

class AiGenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Freepik Provider
        $freepik = AiGenProvider::updateOrCreate(
            ['slug' => 'freepik'],
            [
                'name' => 'Freepik',
                'slug' => 'freepik',
                'type' => 'both',
                'description' => 'Generate high-quality images and videos using Freepik AI',
                'logo_url' => null,
                'supported_features' => ['text-to-image', 'text-to-video', 'image-editing'],
                'is_active' => true,
                'priority' => 1,
            ]
        );

        // Add Freepik configs (empty by default - admin needs to configure)
        $freepik->setConfig('api_key', '', true);
        $freepik->setConfig('api_endpoint', 'https://api.freepik.com/v1', false);

        // Create placeholder providers for future integration
        AiGenProvider::updateOrCreate(
            ['slug' => 'vidu'],
            [
                'name' => 'Vidu',
                'slug' => 'vidu',
                'type' => 'video',
                'description' => 'AI-powered video generation platform',
                'logo_url' => null,
                'supported_features' => ['text-to-video', 'video-editing'],
                'is_active' => false,
                'priority' => 2,
            ]
        );

        AiGenProvider::updateOrCreate(
            ['slug' => 'pixverse'],
            [
                'name' => 'Pixverse',
                'slug' => 'pixverse',
                'type' => 'video',
                'description' => 'Create stunning videos with AI',
                'logo_url' => null,
                'supported_features' => ['text-to-video', 'image-to-video'],
                'is_active' => false,
                'priority' => 3,
            ]
        );

        // Create Packages
        AiGenPackage::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for trying out AI generation',
                'price' => 299.00,
                'currency' => 'THB',
                'image_credits' => 50,
                'video_credits' => 10,
                'duration_days' => 30,
                'is_recurring' => false,
                'recurring_period' => null,
                'features' => ['50 Image Credits', '10 Video Credits', '30 Days Access', 'Standard Quality'],
                'provider_access' => null, // null = all providers
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ]
        );

        AiGenPackage::updateOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For content creators and professionals',
                'price' => 799.00,
                'currency' => 'THB',
                'image_credits' => 200,
                'video_credits' => 50,
                'duration_days' => 30,
                'is_recurring' => true,
                'recurring_period' => 'monthly',
                'features' => ['200 Image Credits', '50 Video Credits', 'Monthly Recurring', 'High Quality', 'Priority Support'],
                'provider_access' => null,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ]
        );

        AiGenPackage::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited power for teams and businesses',
                'price' => 1999.00,
                'currency' => 'THB',
                'image_credits' => 1000,
                'video_credits' => 200,
                'duration_days' => 30,
                'is_recurring' => true,
                'recurring_period' => 'monthly',
                'features' => ['1000 Image Credits', '200 Video Credits', 'Monthly Recurring', 'Ultra Quality', '24/7 Priority Support', 'API Access'],
                'provider_access' => null,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ]
        );

        // Create Quotas
        AiGenQuota::updateOrCreate(
            ['name' => 'Default Free Quota'],
            [
                'name' => 'Default Free Quota',
                'description' => 'Free quota for all registered users',
                'free_image_daily' => 3,
                'free_image_monthly' => 20,
                'free_video_daily' => 1,
                'free_video_monthly' => 5,
                'role' => null, // applies to all
                'is_active' => true,
                'is_default' => true,
            ]
        );

        AiGenQuota::updateOrCreate(
            ['name' => 'Admin Unlimited'],
            [
                'name' => 'Admin Unlimited',
                'description' => 'Unlimited access for administrators',
                'free_image_daily' => 999999,
                'free_image_monthly' => 999999,
                'free_video_daily' => 999999,
                'free_video_monthly' => 999999,
                'role' => 'admin',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        $this->command->info('AI Gen system seeded successfully!');
    }
}

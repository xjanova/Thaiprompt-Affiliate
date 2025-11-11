<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BotPlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $platforms = [
            [
                'name' => 'TikTok',
                'code' => 'tiktok',
                'icon' => 'fab fa-tiktok',
                'description' => 'โพสวิดีโอและคอนเทนต์สั้นๆ บน TikTok',
                'required_credentials' => json_encode(['access_token', 'client_key', 'client_secret']),
                'api_endpoints' => json_encode([
                    'auth' => 'https://www.tiktok.com/auth/authorize/',
                    'post' => 'https://open-api.tiktok.com/share/video/upload/',
                    'analytics' => 'https://open-api.tiktok.com/video/data/',
                ]),
                'is_active' => true,
                'max_posts_per_day' => 30,
                'rate_limit_per_hour' => 5,
                'supported_media_types' => json_encode(['video', 'image', 'text']),
                'post_settings' => json_encode([
                    'max_video_length' => 180, // 3 minutes
                    'max_file_size' => 287 * 1024 * 1024, // 287MB
                    'max_hashtags' => 30,
                    'video_formats' => ['mp4', 'mov', 'avi'],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Facebook',
                'code' => 'facebook',
                'icon' => 'fab fa-facebook',
                'description' => 'โพสคอนเทนต์บน Facebook Page และ Profile',
                'required_credentials' => json_encode(['access_token', 'page_id']),
                'api_endpoints' => json_encode([
                    'auth' => 'https://www.facebook.com/v18.0/dialog/oauth',
                    'post' => 'https://graph.facebook.com/v18.0/{page-id}/feed',
                    'photo' => 'https://graph.facebook.com/v18.0/{page-id}/photos',
                    'video' => 'https://graph.facebook.com/v18.0/{page-id}/videos',
                    'analytics' => 'https://graph.facebook.com/v18.0/{post-id}/insights',
                ]),
                'is_active' => true,
                'max_posts_per_day' => 50,
                'rate_limit_per_hour' => 10,
                'supported_media_types' => json_encode(['text', 'image', 'video', 'link']),
                'post_settings' => json_encode([
                    'max_text_length' => 63206,
                    'max_images' => 10,
                    'max_video_length' => 240, // 4 hours
                    'max_file_size' => 4 * 1024 * 1024 * 1024, // 4GB
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'LINE Official Account',
                'code' => 'line',
                'icon' => 'fab fa-line',
                'description' => 'ส่งข้อความและคอนเทนต์ผ่าน LINE Official Account',
                'required_credentials' => json_encode(['channel_access_token', 'channel_secret']),
                'api_endpoints' => json_encode([
                    'broadcast' => 'https://api.line.me/v2/bot/message/broadcast',
                    'push' => 'https://api.line.me/v2/bot/message/push',
                    'multicast' => 'https://api.line.me/v2/bot/message/multicast',
                    'analytics' => 'https://api.line.me/v2/bot/insight/message/delivery',
                ]),
                'is_active' => true,
                'max_posts_per_day' => 500,
                'rate_limit_per_hour' => 50,
                'supported_media_types' => json_encode(['text', 'image', 'video', 'audio', 'flex']),
                'post_settings' => json_encode([
                    'max_text_length' => 5000,
                    'max_broadcast_messages' => 500,
                    'flex_message_support' => true,
                    'rich_menu_support' => true,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Instagram',
                'code' => 'instagram',
                'icon' => 'fab fa-instagram',
                'description' => 'โพสรูปภาพและวิดีโอบน Instagram',
                'required_credentials' => json_encode(['access_token', 'instagram_business_account']),
                'api_endpoints' => json_encode([
                    'auth' => 'https://api.instagram.com/oauth/authorize',
                    'post' => 'https://graph.facebook.com/v18.0/{ig-user-id}/media',
                    'publish' => 'https://graph.facebook.com/v18.0/{ig-user-id}/media_publish',
                    'analytics' => 'https://graph.facebook.com/v18.0/{media-id}/insights',
                ]),
                'is_active' => true,
                'max_posts_per_day' => 25,
                'rate_limit_per_hour' => 5,
                'supported_media_types' => json_encode(['image', 'video', 'carousel']),
                'post_settings' => json_encode([
                    'max_caption_length' => 2200,
                    'max_hashtags' => 30,
                    'max_video_length' => 60,
                    'aspect_ratios' => ['1:1', '4:5', '16:9'],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Twitter (X)',
                'code' => 'twitter',
                'icon' => 'fab fa-twitter',
                'description' => 'โพสทวีตและคอนเทนต์บน Twitter/X',
                'required_credentials' => json_encode(['api_key', 'api_secret', 'access_token', 'access_token_secret']),
                'api_endpoints' => json_encode([
                    'auth' => 'https://api.twitter.com/oauth/authorize',
                    'tweet' => 'https://api.twitter.com/2/tweets',
                    'media' => 'https://upload.twitter.com/1.1/media/upload.json',
                    'analytics' => 'https://api.twitter.com/2/tweets/{id}',
                ]),
                'is_active' => true,
                'max_posts_per_day' => 100,
                'rate_limit_per_hour' => 20,
                'supported_media_types' => json_encode(['text', 'image', 'video', 'gif']),
                'post_settings' => json_encode([
                    'max_text_length' => 280,
                    'max_images' => 4,
                    'max_video_length' => 140,
                    'max_file_size' => 512 * 1024 * 1024, // 512MB
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'YouTube',
                'code' => 'youtube',
                'icon' => 'fab fa-youtube',
                'description' => 'อัปโหลดวิดีโอบน YouTube',
                'required_credentials' => json_encode(['access_token', 'refresh_token', 'channel_id']),
                'api_endpoints' => json_encode([
                    'auth' => 'https://accounts.google.com/o/oauth2/v2/auth',
                    'upload' => 'https://www.googleapis.com/upload/youtube/v3/videos',
                    'analytics' => 'https://youtubeanalytics.googleapis.com/v2/reports',
                ]),
                'is_active' => true,
                'max_posts_per_day' => 10,
                'rate_limit_per_hour' => 3,
                'supported_media_types' => json_encode(['video']),
                'post_settings' => json_encode([
                    'max_title_length' => 100,
                    'max_description_length' => 5000,
                    'max_file_size' => 256 * 1024 * 1024 * 1024, // 256GB
                    'video_formats' => ['mp4', 'mov', 'avi', 'flv', 'wmv'],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Use updateOrInsert to make this seeder idempotent
        foreach ($platforms as $platform) {
            DB::table('bot_social_platforms')->updateOrInsert(
                ['code' => $platform['code']], // unique key
                $platform
            );
        }

        $this->command->info('Bot social platforms seeded successfully!');
    }
}

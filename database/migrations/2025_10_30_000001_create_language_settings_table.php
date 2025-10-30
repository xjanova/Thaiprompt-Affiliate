<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('language_settings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Language code (e.g., en, th, ja)');
            $table->string('name', 100)->comment('Language name in English');
            $table->string('native_name', 100)->comment('Language name in native script');
            $table->string('flag_emoji', 10)->nullable()->comment('Flag emoji representation');
            $table->string('flag_image_url', 255)->nullable()->comment('Custom flag image URL');
            $table->boolean('is_enabled')->default(true)->comment('Whether this language is active');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->boolean('is_default')->default(false)->comment('Is this the default language');
            $table->timestamps();

            $table->index('is_enabled');
            $table->index('sort_order');
            $table->index('is_default');
        });

        // Insert default languages
        DB::table('language_settings')->insert([
            [
                'code' => 'th',
                'name' => 'Thai',
                'native_name' => 'ไทย',
                'flag_emoji' => '🇹🇭',
                'is_enabled' => true,
                'sort_order' => 1,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag_emoji' => '🇬🇧',
                'is_enabled' => true,
                'sort_order' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'zh',
                'name' => 'Chinese',
                'native_name' => '中文',
                'flag_emoji' => '🇨🇳',
                'is_enabled' => true,
                'sort_order' => 3,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ja',
                'name' => 'Japanese',
                'native_name' => '日本語',
                'flag_emoji' => '🇯🇵',
                'is_enabled' => true,
                'sort_order' => 4,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ko',
                'name' => 'Korean',
                'native_name' => '한국어',
                'flag_emoji' => '🇰🇷',
                'is_enabled' => true,
                'sort_order' => 5,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'vi',
                'name' => 'Vietnamese',
                'native_name' => 'Tiếng Việt',
                'flag_emoji' => '🇻🇳',
                'is_enabled' => false,
                'sort_order' => 6,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'es',
                'name' => 'Spanish',
                'native_name' => 'Español',
                'flag_emoji' => '🇪🇸',
                'is_enabled' => false,
                'sort_order' => 7,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'flag_emoji' => '🇫🇷',
                'is_enabled' => false,
                'sort_order' => 8,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'de',
                'name' => 'German',
                'native_name' => 'Deutsch',
                'flag_emoji' => '🇩🇪',
                'is_enabled' => false,
                'sort_order' => 9,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'pt',
                'name' => 'Portuguese',
                'native_name' => 'Português',
                'flag_emoji' => '🇵🇹',
                'is_enabled' => false,
                'sort_order' => 10,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ru',
                'name' => 'Russian',
                'native_name' => 'Русский',
                'flag_emoji' => '🇷🇺',
                'is_enabled' => false,
                'sort_order' => 11,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'flag_emoji' => '🇸🇦',
                'is_enabled' => false,
                'sort_order' => 12,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'hi',
                'name' => 'Hindi',
                'native_name' => 'हिन्दी',
                'flag_emoji' => '🇮🇳',
                'is_enabled' => false,
                'sort_order' => 13,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_settings');
    }
};

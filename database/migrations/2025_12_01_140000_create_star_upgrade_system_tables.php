<?php

/**
 * สร้างตารางระบบอัพเกรดดาวด้วย Coins
 *
 * - star_upgrade_prices: ราคาอัพเกรดแต่ละดาว
 * - star_upgrade_history: ประวัติการอัพเกรด
 * - เพิ่ม purchased_stars ใน user_video_levels
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง
     */
    public function up(): void
    {
        // 1. ตารางราคาอัพเกรดดาว
        if (! Schema::hasTable('star_upgrade_prices')) {
            Schema::create('star_upgrade_prices', function (Blueprint $table) {
                $table->id();

                // ระดับดาว (1-5)
                $table->integer('stars')->unique();              // จำนวนดาว
                $table->string('name');                          // ชื่อ เช่น "1 ดาว ทองแดง"
                $table->string('name_th')->nullable();           // ชื่อภาษาไทย
                $table->text('description')->nullable();         // คำอธิบาย
                $table->text('description_th')->nullable();      // คำอธิบายภาษาไทย

                // สี (ใช้กับ VideoLevel)
                $table->string('star_color')->default('bronze'); // bronze, silver, gold, platinum, diamond

                // ราคา
                $table->decimal('price_coins', 15, 2);           // ราคา Coins ที่ต้องจ่าย
                $table->decimal('price_coins_from_previous', 15, 2)->nullable();  // ราคาอัพจากดาวก่อนหน้า (ถ้ามี)

                // เงื่อนไข
                $table->integer('min_level')->default(1);        // Level ขั้นต่ำที่ซื้อได้
                $table->boolean('require_previous_star')->default(true); // ต้องมีดาวก่อนหน้าหรือไม่

                // สิทธิประโยชน์
                $table->json('benefits')->nullable();            // สิทธิพิเศษที่ได้รับ
                $table->decimal('exp_multiplier', 5, 2)->default(1.00);  // ตัวคูณ EXP
                $table->decimal('coin_multiplier', 5, 2)->default(1.00); // ตัวคูณ Coins

                // สถานะ
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);

                $table->timestamps();

                // Indexes
                $table->index('stars');
                $table->index('is_active');
            });
        }

        // 2. ตารางประวัติการอัพเกรดดาว
        if (! Schema::hasTable('star_upgrade_history')) {
            Schema::create('star_upgrade_history', function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');

                // ดาวก่อนและหลัง
                $table->integer('from_stars')->default(0);       // ดาวก่อนอัพเกรด
                $table->integer('to_stars');                     // ดาวหลังอัพเกรด

                // ราคาที่จ่าย
                $table->decimal('coins_paid', 15, 2);            // Coins ที่จ่าย

                // สถานะ
                $table->enum('status', ['completed', 'refunded'])->default('completed');
                $table->text('note')->nullable();

                // Audit
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();

                $table->timestamps();

                // Indexes
                $table->index('user_id');
                $table->index('to_stars');
                $table->index('status');
                $table->index('created_at');
            });
        }

        // 3. เพิ่มคอลัมน์ purchased_stars ใน user_video_levels
        if (Schema::hasTable('user_video_levels')) {
            Schema::table('user_video_levels', function (Blueprint $table) {
                if (! Schema::hasColumn('user_video_levels', 'purchased_stars')) {
                    $table->integer('purchased_stars')->default(0)->after('total_exp');
                }
                if (! Schema::hasColumn('user_video_levels', 'purchased_star_color')) {
                    $table->string('purchased_star_color')->nullable()->after('purchased_stars');
                }
                if (! Schema::hasColumn('user_video_levels', 'last_star_upgrade_at')) {
                    $table->timestamp('last_star_upgrade_at')->nullable()->after('purchased_star_color');
                }
            });
        }
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('star_upgrade_history');
        Schema::dropIfExists('star_upgrade_prices');

        if (Schema::hasTable('user_video_levels')) {
            Schema::table('user_video_levels', function (Blueprint $table) {
                if (Schema::hasColumn('user_video_levels', 'purchased_stars')) {
                    $table->dropColumn('purchased_stars');
                }
                if (Schema::hasColumn('user_video_levels', 'purchased_star_color')) {
                    $table->dropColumn('purchased_star_color');
                }
                if (Schema::hasColumn('user_video_levels', 'last_star_upgrade_at')) {
                    $table->dropColumn('last_star_upgrade_at');
                }
            });
        }
    }
};

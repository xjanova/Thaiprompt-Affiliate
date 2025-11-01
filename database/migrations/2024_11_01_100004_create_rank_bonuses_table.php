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
        Schema::create('rank_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rank_id')->constrained()->onDelete('cascade');

            // Bonus Type
            $table->enum('bonus_type', [
                'one_time',     // โบนัสครั้งเดียว (เมื่อขึ้นระดับ)
                'monthly',      // โบนัสรายเดือน
                'commission',   // เพิ่ม commission rate
                'multiplier',   // ตัวคูณรายได้
                'privilege',    // สิทธิพิเศษ
                'reward'        // รางวัล
            ])->default('one_time');

            $table->string('name'); // ชื่อโบนัส
            $table->string('name_th')->nullable();
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();

            // Bonus Value
            $table->decimal('amount', 12, 2)->nullable(); // จำนวนเงิน
            $table->decimal('percentage', 5, 2)->nullable(); // เปอร์เซ็นต์
            $table->string('reward_type')->nullable(); // ประเภทรางวัล
            $table->text('reward_details')->nullable(); // รายละเอียดรางวัล

            // Conditions
            $table->json('conditions')->nullable(); // เงื่อนไขเพิ่มเติม

            // Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_apply')->default(true); // ให้อัตโนมัติหรือไม่
            $table->integer('priority')->default(0);

            $table->timestamps();

            $table->index(['rank_id', 'bonus_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_bonuses');
    }
};

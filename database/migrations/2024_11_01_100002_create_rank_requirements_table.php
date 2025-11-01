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
        Schema::create('rank_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rank_id')->constrained()->onDelete('cascade');

            // Requirement Type
            $table->enum('requirement_type', [
                'points',           // คะแนนสะสม
                'referrals',        // จำนวนคนแนะนำ
                'sales',            // ยอดขายรวม
                'active_referrals', // คนแนะนำที่ active
                'team_sales',       // ยอดขายของทีม
                'consecutive_months', // เดือนติดต่อกันที่ทำได้
                'custom'            // เงื่อนไขพิเศษ
            ])->default('points');

            $table->string('name'); // ชื่อเงื่อนไข
            $table->string('name_th')->nullable();
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();

            // Values
            $table->decimal('target_value', 15, 2); // เป้าหมายที่ต้องการ
            $table->string('operator')->default('>='); // >=, >, =, <, <=
            $table->string('unit')->nullable(); // หน่วย เช่น คะแนน, บาท, คน

            // Settings
            $table->boolean('is_required')->default(true); // required หรือ optional
            $table->integer('weight')->default(1); // น้ำหนักของเงื่อนไข
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['rank_id', 'requirement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_requirements');
    }
};

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
        Schema::table('affiliates', function (Blueprint $table) {
            // Binary tree parent (แยกจาก parent_id ของ affiliate hierarchy)
            $table->foreignId('binary_parent_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('affiliates')
                ->onDelete('set null');

            // Binary position: left or right
            $table->enum('binary_position', ['left', 'right'])
                ->nullable()
                ->after('binary_parent_id');

            // Binary level สำหรับ binary tree
            $table->integer('binary_level')
                ->default(1)
                ->after('binary_position');

            // Statistics สำหรับ binary tree
            $table->integer('binary_left_count')
                ->default(0)
                ->after('binary_level')
                ->comment('จำนวนสมาชิกฝั่งซ้ายทั้งหมด');

            $table->integer('binary_right_count')
                ->default(0)
                ->after('binary_left_count')
                ->comment('จำนวนสมาชิกฝั่งขวาทั้งหมด');

            // Indexes
            $table->index(['binary_parent_id', 'binary_position']);
            $table->index('binary_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropForeign(['binary_parent_id']);
            $table->dropIndex(['binary_parent_id', 'binary_position']);
            $table->dropIndex(['binary_level']);
            $table->dropColumn([
                'binary_parent_id',
                'binary_position',
                'binary_level',
                'binary_left_count',
                'binary_right_count'
            ]);
        });
    }
};

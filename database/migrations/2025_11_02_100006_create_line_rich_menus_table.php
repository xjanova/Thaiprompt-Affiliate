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
        Schema::create('line_rich_menus', function (Blueprint $table) {
            $table->id();
            $table->string('rich_menu_id')->unique()->nullable()->comment('LINE Rich Menu ID');
            $table->string('name')->comment('ชื่อ Rich Menu');
            $table->text('description')->nullable();
            $table->enum('size', ['full', 'half'])->default('half');
            $table->boolean('selected')->default(false)->comment('เลือกไว้โดยค่าเริ่มต้น');
            $table->string('chat_bar_text')->default('เมนู')->comment('ข้อความแสดงบน chat bar');
            $table->json('areas')->comment('พื้นที่และ actions ของเมนู');
            $table->string('image_path')->nullable()->comment('รูปภาพ Rich Menu');
            $table->boolean('is_default')->default(false)->comment('เป็น Rich Menu หลัก');
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_rich_menus');
    }
};

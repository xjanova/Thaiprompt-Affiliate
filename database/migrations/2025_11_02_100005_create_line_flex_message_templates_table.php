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
        Schema::create('line_flex_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อเทมเพลต');
            $table->string('category')->default('general')->comment('หมวดหมู่: promotion, info, product, etc.');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable()->comment('รูปตัวอย่าง');
            $table->json('flex_content')->comment('JSON ของ Flex Message');
            $table->boolean('is_seed')->default(false)->comment('เป็นตัวอย่างจากระบบ');
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_flex_message_templates');
    }
};

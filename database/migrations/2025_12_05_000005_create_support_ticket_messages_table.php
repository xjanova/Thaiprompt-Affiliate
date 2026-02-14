<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง support_ticket_messages สำหรับข้อความในระบบ Ticket
 */
return new class extends Migration
{
    /**
     * สร้างตาราง support_ticket_messages
     */
    public function up(): void
    {
        if (Schema::hasTable('support_ticket_messages')) {
            return;
        }

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')
                ->constrained('support_tickets')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // เนื้อหาข้อความ
            $table->text('message');
            $table->boolean('is_from_admin')->default(false);

            // ไฟล์แนบ (JSON array)
            $table->json('attachments')->nullable();

            // สถานะการอ่าน
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง support_ticket_messages
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};

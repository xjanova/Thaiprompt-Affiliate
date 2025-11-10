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
        Schema::create('bot_sales_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('bot_automations')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_sales_rep_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('conversation_id')->unique();
            $table->enum('stage', [
                'awareness',      // รู้จักสินค้า
                'interest',       // สนใจ
                'consideration',  // พิจารณา
                'intent',         // ตั้งใจซื้อ
                'purchase',       // ซื้อแล้ว
                'lost'            // สูญเสียโอกาส
            ])->default('awareness');

            $table->enum('status', ['active', 'inactive', 'converted', 'abandoned'])->default('active');
            $table->enum('channel', ['line', 'facebook', 'email', 'chat'])->default('chat');

            $table->json('customer_interests')->nullable(); // Products interested
            $table->json('recommended_products')->nullable(); // AI recommendations
            $table->decimal('estimated_order_value', 12, 2)->nullable();
            $table->integer('ai_confidence_score')->nullable(); // Likelihood to buy (0-100)

            $table->boolean('is_ai_handled')->default(true);
            $table->boolean('requires_human')->default(false);
            $table->text('ai_next_action')->nullable(); // Next recommended action

            $table->integer('message_count')->default(0);
            $table->integer('product_views')->default(0);
            $table->integer('products_recommended')->default(0);

            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('actual_order_value', 12, 2)->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'stage']);
            $table->index(['automation_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_sales_conversations');
    }
};

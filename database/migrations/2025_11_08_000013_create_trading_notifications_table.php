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
        Schema::create('trading_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bot_id')->nullable()->constrained('trading_bots')->onDelete('cascade');

            $table->enum('type', [
                'trade_opened',
                'trade_closed',
                'stop_loss_hit',
                'take_profit_hit',
                'signal_generated',
                'bot_started',
                'bot_stopped',
                'bot_error',
                'balance_low',
                'daily_summary',
                'system_alert'
            ]);

            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional notification data

            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->json('channels')->nullable(); // ['email', 'line', 'telegram', 'push']

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
            $table->index('bot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_notifications');
    }
};

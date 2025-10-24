<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referral/Invitation System
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('line_user_id')->nullable();

            $table->string('invitation_code')->unique();
            $table->string('invitation_link');

            $table->enum('channel', ['email', 'sms', 'line', 'direct'])->default('direct');
            $table->enum('status', ['sent', 'opened', 'registered', 'expired'])->default('sent');

            $table->foreignId('invited_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sent_at');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['inviter_id', 'status']);
            $table->index('invitation_code');
        });

        // Line OA Integration
        Schema::create('line_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('line_user_id');

            $table->enum('type', ['text', 'image', 'video', 'template'])->default('text');
            $table->text('message');
            $table->json('message_data')->nullable();

            $table->enum('direction', ['incoming', 'outgoing'])->default('outgoing');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');

            $table->string('line_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['line_user_id', 'direction']);
        });

        // Coupons/Discounts
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('cascade'); // null = admin coupon
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->decimal('min_purchase', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();

            $table->integer('usage_limit')->nullable(); // Total uses
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('times_used')->default(0);

            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
        });

        Schema::create('coupon_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usage');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('line_messages');
        Schema::dropIfExists('invitations');
    }
};

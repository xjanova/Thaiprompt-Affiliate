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
        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');

            // Booking Details
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('nights');
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->integer('rooms_count')->default(1);

            // Guest Information
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone');
            $table->string('guest_country')->nullable();
            $table->text('special_requests')->nullable();

            // Pricing
            $table->decimal('room_price', 10, 2); // Price per night
            $table->decimal('subtotal', 10, 2); // Room price * nights
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency')->default('THB');

            // Promotion (without foreign key constraint to avoid circular dependency)
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->string('promo_code')->nullable();

            // Payment
            $table->enum('payment_method', ['wallet', 'stripe', 'promptpay', 'cash', 'bank_transfer']);
            $table->string('payment_status')->default('pending'); // pending, paid, partially_paid, failed, refunded
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Booking Status
            $table->string('status')->default('pending'); // pending, confirmed, checked_in, checked_out, cancelled, no_show
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Commission (MLM)
            $table->boolean('commission_paid')->default(false);
            $table->decimal('vendor_commission', 10, 2)->default(0);
            $table->decimal('admin_commission', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['check_in_date', 'check_out_date']);
            $table->index(['status', 'payment_status']);
        });

        Schema::create('booking_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('hotel_bookings')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('id_type')->nullable(); // passport, national_id, driving_license
            $table->string('id_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_guests');
        Schema::dropIfExists('hotel_bookings');
    }
};

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
            $table->string('booking_number')->unique(); // e.g., HB20250107-0001

            // Relations
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained()->onDelete('cascade');

            // Guest Information
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone');
            $table->string('guest_id_card')->nullable();
            $table->integer('number_of_adults')->default(1);
            $table->integer('number_of_children')->default(0);

            // Booking Dates
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('number_of_nights');
            $table->integer('number_of_rooms')->default(1);

            // Pricing
            $table->decimal('room_price', 10, 2); // Base room price
            $table->decimal('subtotal', 10, 2); // Total room charges
            $table->decimal('tax_amount', 10, 2)->default(0); // VAT/Tax
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2); // Final amount to pay
            $table->string('currency', 3)->default('THB');

            // Special Requests
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable(); // For admin/hotel staff

            // Status
            $table->enum('status', [
                'pending',           // Waiting for payment
                'confirmed',         // Payment received, booking confirmed
                'checked_in',        // Guest has checked in
                'checked_out',       // Guest has checked out
                'cancelled',         // Booking cancelled
                'no_show',           // Guest didn't show up
                'completed'          // Booking completed
            ])->default('pending');

            // Payment
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->timestamp('payment_date')->nullable();

            // Affiliate Commission
            $table->foreignId('affiliate_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->boolean('commission_paid')->default(false);

            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);

            // Check-in/Check-out
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();

            // Booking Source
            $table->enum('booking_source', ['website', 'admin', 'api', 'mobile'])->default('website');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('booking_number');
            $table->index(['hotel_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['check_in_date', 'check_out_date']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_bookings');
    }
};

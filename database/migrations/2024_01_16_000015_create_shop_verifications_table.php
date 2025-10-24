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
        Schema::create('shop_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending')->comment('pending, approved, rejected, resubmit');

            // Business Documents
            $table->string('business_registration_number')->nullable();
            $table->string('business_registration_document')->nullable();
            $table->string('tax_certificate')->nullable();
            $table->string('business_license')->nullable();

            // Owner Identity Documents
            $table->string('id_card_number')->nullable();
            $table->string('id_card_front')->nullable();
            $table->string('id_card_back')->nullable();
            $table->string('selfie_with_id')->nullable();

            // Business Information
            $table->string('business_type')->nullable();
            $table->text('business_address')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();
            $table->string('website')->nullable();

            // Bank Verification
            $table->string('bank_statement')->nullable();
            $table->string('bank_book_photo')->nullable();

            // Additional Documents
            $table->json('additional_documents')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Verification Details
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');

            // Verification Badge
            $table->string('verification_badge')->nullable()->comment('bronze, silver, gold, platinum');
            $table->string('verification_badge_icon')->nullable();
            $table->string('verification_badge_color')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
            $table->index('status');
        });

        // Add verification status to vendors table
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('status');
            $table->string('verification_badge')->nullable()->after('is_verified');
            $table->timestamp('verified_at')->nullable()->after('verification_badge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'verification_badge', 'verified_at']);
        });

        Schema::dropIfExists('shop_verifications');
    }
};

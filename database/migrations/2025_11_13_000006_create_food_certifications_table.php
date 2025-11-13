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
        Schema::create('food_certifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_product_id');

            // Certification Type
            $table->string('certification_type', 100)->comment('e.g., "Organic", "Fair Trade", "GMP", "HACCP"');
            $table->string('certification_name');
            $table->string('certifying_body')->comment('Organization that issued');

            // Certificate Details
            $table->string('certificate_number', 100)->unique();
            $table->date('issued_date');
            $table->date('expiry_date');
            $table->enum('status', ['active', 'expired', 'revoked', 'suspended'])->default('active');

            // Scope
            $table->text('scope')->nullable()->comment('What this certification covers');
            $table->string('standards_version', 50)->nullable()->comment('e.g., "ISO 22000:2018"');

            // Documentation
            $table->string('certificate_url', 500)->nullable()->comment('PDF or image of certificate');
            $table->string('audit_report_url', 500)->nullable();

            // Verification
            $table->string('verification_code', 100)->nullable()->comment('For public verification');
            $table->string('qr_code_url', 500)->nullable();

            // Blockchain
            $table->string('blockchain_hash', 66)->nullable();
            $table->string('ipfs_hash', 100)->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('food_product_id')->references('id')->on('food_products')->onDelete('cascade');

            // Indexes
            $table->index('certification_type');
            $table->index('status');
            $table->index('expiry_date');
            $table->index('food_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_certifications');
    }
};

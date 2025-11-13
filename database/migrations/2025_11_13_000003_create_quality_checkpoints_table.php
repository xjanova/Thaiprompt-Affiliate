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
        Schema::create('quality_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_product_id');
            $table->unsignedBigInteger('journey_stage_id')->nullable()->comment('Link to product_journey');

            // Checkpoint Information
            $table->enum('checkpoint_type', ['visual', 'laboratory', 'sensor', 'certification', 'audit']);
            $table->string('checkpoint_name');
            $table->timestamp('checked_at');

            // Inspector
            $table->unsignedBigInteger('inspector_id')->nullable();
            $table->string('inspector_name');
            $table->string('inspector_license', 100)->nullable()->comment('Professional license number');
            $table->string('organization')->nullable();

            // Test Results
            $table->json('test_parameters')->nullable()->comment('[{"name", "value", "unit", "standard", "status"}]');

            $table->enum('overall_result', ['pass', 'conditional_pass', 'fail']);
            $table->decimal('pass_score', 5, 2)->nullable()->comment('Percentage score');

            // Standards & Certifications
            $table->json('standards_applied')->nullable()->comment('["ISO 22000", "HACCP", "GMP"]');
            $table->boolean('certification_issued')->default(false);
            $table->string('certificate_number', 100)->nullable();
            $table->date('certificate_expiry')->nullable();

            // Documentation
            $table->string('test_report_url', 500)->nullable();
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();

            // Corrective Actions (if failed)
            $table->text('corrective_actions')->nullable();
            $table->boolean('retest_required')->default(false);
            $table->unsignedBigInteger('retest_checkpoint_id')->nullable()->comment('Link to retest');

            // Blockchain
            $table->string('blockchain_hash', 66)->nullable();
            $table->string('ipfs_hash', 100)->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('food_product_id')->references('id')->on('food_products')->onDelete('cascade');
            $table->foreign('journey_stage_id')->references('id')->on('product_journey')->onDelete('set null');
            $table->foreign('inspector_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('retest_checkpoint_id')->references('id')->on('quality_checkpoints')->onDelete('set null');

            // Indexes
            $table->index('overall_result');
            $table->index('checkpoint_type');
            $table->index('food_product_id');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_checkpoints');
    }
};

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
        Schema::create('stakeholder_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            // Role Information
            $table->enum('role_type', ['farmer', 'processor', 'quality_inspector', 'distributor', 'retailer', 'certifier', 'consumer']);
            $table->string('organization_name')->nullable();
            $table->string('license_number', 100)->nullable();

            // Credentials
            $table->json('certifications')->nullable()->comment('Professional certifications');
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_id')->nullable();

            // Location
            $table->json('operating_location')->nullable()->comment('{"address": "", "coordinates": {}}');
            $table->json('service_areas')->nullable()->comment('Areas they can operate in');

            // Permissions
            $table->boolean('can_add_quality_checkpoints')->default(false);
            $table->boolean('can_issue_certificates')->default(false);
            $table->boolean('can_verify_carbon_data')->default(false);

            // Reputation
            $table->decimal('reputation_score', 3, 2)->default(0)->comment('0-5 stars');
            $table->integer('total_products_handled')->default(0);
            $table->integer('total_quality_checks')->default(0);

            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');

            $table->timestamps();

            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('verified_by_id')->references('id')->on('users')->onDelete('set null');

            // Unique constraint
            $table->unique(['user_id', 'role_type'], 'unique_user_role');

            // Indexes
            $table->index('role_type');
            $table->index('status');
            $table->index('verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stakeholder_roles');
    }
};

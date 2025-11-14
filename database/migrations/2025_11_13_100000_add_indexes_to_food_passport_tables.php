<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * เพิ่ม indexes สำหรับ performance ของระบบ Food Passport
     * - เร่งความเร็วการ query
     * - ลดเวลาการค้นหา
     * - รองรับ TPIX blockchain queries
     */
    public function up(): void
    {
        // Food Products Table Indexes
        Schema::table('food_products', function (Blueprint $table) {
            // Primary search indexes
            $table->index('food_passport_id', 'idx_food_products_passport_id');
            $table->index('farmer_id', 'idx_food_products_farmer_id');
            $table->index('product_type', 'idx_food_products_type');

            // Performance indexes
            $table->index('carbon_score', 'idx_food_products_carbon_score');

            // Blockchain indexes
            $table->index('blockchain_hash', 'idx_food_products_blockchain');
            $table->index('ipfs_hash', 'idx_food_products_ipfs');

            // Date range queries
            $table->index('harvest_date', 'idx_food_products_harvest_date');
            $table->index('created_at', 'idx_food_products_created');

            // Composite indexes for common queries
            $table->index(['farmer_id', 'product_type'], 'idx_food_products_farmer_type');
        });

        // Product Journey Table Indexes
        Schema::table('product_journey', function (Blueprint $table) {
            // Foreign key index
            $table->index('food_product_id', 'idx_product_journey_product_id');
            $table->index('handler_id', 'idx_product_journey_handler');

            // Journey tracking indexes
            $table->index('stage', 'idx_product_journey_stage');
            $table->index('sequence_order', 'idx_product_journey_sequence');
            $table->index('quality_status', 'idx_product_journey_quality');

            // Blockchain index
            $table->index('blockchain_hash', 'idx_product_journey_blockchain');

            // Date range queries
            $table->index('arrived_at', 'idx_product_journey_arrived');
            $table->index('departed_at', 'idx_product_journey_departed');

            // Composite indexes
            $table->index(['food_product_id', 'sequence_order'], 'idx_product_journey_product_seq');
            $table->index(['food_product_id', 'stage'], 'idx_product_journey_product_stage');
        });

        // Quality Checkpoints Table Indexes
        Schema::table('quality_checkpoints', function (Blueprint $table) {
            // Foreign key indexes
            $table->index('food_product_id', 'idx_quality_checkpoints_product');
            $table->index('product_journey_id', 'idx_quality_checkpoints_journey');
            $table->index('inspector_id', 'idx_quality_checkpoints_inspector');

            // Quality tracking indexes
            $table->index('checkpoint_type', 'idx_quality_checkpoints_type');
            $table->index('overall_result', 'idx_quality_checkpoints_result');
            $table->index('certification_issued', 'idx_quality_checkpoints_cert');

            // Blockchain index
            $table->index('blockchain_hash', 'idx_quality_checkpoints_blockchain');

            // Date queries
            $table->index('checked_at', 'idx_quality_checkpoints_checked');
            $table->index('certificate_expiry', 'idx_quality_checkpoints_expiry');

            // Composite indexes
            $table->index(['food_product_id', 'overall_result'], 'idx_quality_checkpoints_product_result');
            $table->index(['inspector_id', 'checked_at'], 'idx_quality_checkpoints_inspector_date');
        });

        // Carbon Footprint Records Table Indexes
        Schema::table('carbon_footprint_records', function (Blueprint $table) {
            // Foreign key indexes
            $table->index('food_product_id', 'idx_carbon_records_product');
            $table->index('product_journey_id', 'idx_carbon_records_journey');
            $table->index('verified_by_id', 'idx_carbon_records_verifier');

            // Category and tracking
            $table->index('emission_category', 'idx_carbon_records_category');
            $table->index('verification_status', 'idx_carbon_records_verification');

            // Calculation date
            $table->index('calculation_date', 'idx_carbon_records_calc_date');

            // Composite indexes for reporting
            $table->index(['food_product_id', 'emission_category'], 'idx_carbon_records_product_category');
            $table->index(['emission_category', 'calculation_date'], 'idx_carbon_records_category_date');
        });

        // Carbon Credits Table Indexes
        Schema::table('carbon_credits', function (Blueprint $table) {
            // Foreign key indexes
            $table->index('user_id', 'idx_carbon_credits_user');
            $table->index('food_product_id', 'idx_carbon_credits_product');
            $table->index('traded_to_user_id', 'idx_carbon_credits_traded_to');

            // Trading indexes
            $table->index('status', 'idx_carbon_credits_status');
            $table->index('tradeable', 'idx_carbon_credits_tradeable');
            $table->index('credit_type', 'idx_carbon_credits_type');

            // Blockchain indexes (TPIX!)
            $table->index('blockchain_hash', 'idx_carbon_credits_blockchain');
            $table->index('token_id', 'idx_carbon_credits_token_id');

            // Date queries
            $table->index('issued_date', 'idx_carbon_credits_issued');
            $table->index('expiry_date', 'idx_carbon_credits_expiry');
            $table->index('traded_at', 'idx_carbon_credits_traded_at');

            // Marketplace queries
            $table->index(['status', 'tradeable'], 'idx_carbon_credits_marketplace');
            $table->index(['user_id', 'status'], 'idx_carbon_credits_user_status');
            $table->index(['expiry_date', 'status'], 'idx_carbon_credits_expiry_status');
        });

        // Food Certifications Table Indexes
        Schema::table('food_certifications', function (Blueprint $table) {
            // Foreign key index
            $table->index('food_product_id', 'idx_food_certifications_product');

            // Certification tracking
            $table->index('certification_type', 'idx_food_certifications_type');
            $table->index('certifying_body', 'idx_food_certifications_body');
            $table->index('certificate_number', 'idx_food_certifications_cert_number');
            $table->index('status', 'idx_food_certifications_status');

            // Blockchain indexes (TPIX NFT!)
            $table->index('blockchain_hash', 'idx_food_certifications_blockchain');
            $table->index('ipfs_hash', 'idx_food_certifications_ipfs');

            // Verification
            $table->index('verification_code', 'idx_food_certifications_verification');

            // Date queries
            $table->index('issued_date', 'idx_food_certifications_issued');
            $table->index('expiry_date', 'idx_food_certifications_expiry');

            // Composite indexes
            $table->index(['food_product_id', 'certification_type'], 'idx_food_certifications_product_type');
            $table->index(['status', 'expiry_date'], 'idx_food_certifications_status_expiry');
        });

        // Stakeholder Roles Table Indexes
        Schema::table('stakeholder_roles', function (Blueprint $table) {
            // Foreign key indexes
            $table->index('food_product_id', 'idx_stakeholder_roles_product');
            $table->index('user_id', 'idx_stakeholder_roles_user');

            // Role tracking
            $table->index('role', 'idx_stakeholder_roles_role');
            $table->index('is_active', 'idx_stakeholder_roles_active');

            // Composite indexes
            $table->index(['food_product_id', 'role'], 'idx_stakeholder_roles_product_role');
            $table->index(['user_id', 'role', 'is_active'], 'idx_stakeholder_roles_user_role_active');
        });

        // Consumer Scans Table Indexes
        Schema::table('consumer_scans', function (Blueprint $table) {
            // Foreign key index
            $table->index('food_product_id', 'idx_consumer_scans_product');

            // Analytics indexes
            $table->index('scanned_at', 'idx_consumer_scans_scanned_at');
            $table->index('device_type', 'idx_consumer_scans_device');

            // Composite indexes for analytics
            $table->index(['food_product_id', 'scanned_at'], 'idx_consumer_scans_product_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Food Products
        Schema::table('food_products', function (Blueprint $table) {
            $table->dropIndex('idx_food_products_passport_id');
            $table->dropIndex('idx_food_products_farmer_id');
            $table->dropIndex('idx_food_products_type');
            $table->dropIndex('idx_food_products_carbon_score');
            $table->dropIndex('idx_food_products_blockchain');
            $table->dropIndex('idx_food_products_ipfs');
            $table->dropIndex('idx_food_products_harvest_date');
            $table->dropIndex('idx_food_products_created');
            $table->dropIndex('idx_food_products_farmer_type');
        });

        // Product Journey
        Schema::table('product_journey', function (Blueprint $table) {
            $table->dropIndex('idx_product_journey_product_id');
            $table->dropIndex('idx_product_journey_handler');
            $table->dropIndex('idx_product_journey_stage');
            $table->dropIndex('idx_product_journey_sequence');
            $table->dropIndex('idx_product_journey_quality');
            $table->dropIndex('idx_product_journey_blockchain');
            $table->dropIndex('idx_product_journey_arrived');
            $table->dropIndex('idx_product_journey_departed');
            $table->dropIndex('idx_product_journey_product_seq');
            $table->dropIndex('idx_product_journey_product_stage');
        });

        // Quality Checkpoints
        Schema::table('quality_checkpoints', function (Blueprint $table) {
            $table->dropIndex('idx_quality_checkpoints_product');
            $table->dropIndex('idx_quality_checkpoints_journey');
            $table->dropIndex('idx_quality_checkpoints_inspector');
            $table->dropIndex('idx_quality_checkpoints_type');
            $table->dropIndex('idx_quality_checkpoints_result');
            $table->dropIndex('idx_quality_checkpoints_cert');
            $table->dropIndex('idx_quality_checkpoints_blockchain');
            $table->dropIndex('idx_quality_checkpoints_checked');
            $table->dropIndex('idx_quality_checkpoints_expiry');
            $table->dropIndex('idx_quality_checkpoints_product_result');
            $table->dropIndex('idx_quality_checkpoints_inspector_date');
        });

        // Carbon Footprint Records
        Schema::table('carbon_footprint_records', function (Blueprint $table) {
            $table->dropIndex('idx_carbon_records_product');
            $table->dropIndex('idx_carbon_records_journey');
            $table->dropIndex('idx_carbon_records_verifier');
            $table->dropIndex('idx_carbon_records_category');
            $table->dropIndex('idx_carbon_records_verification');
            $table->dropIndex('idx_carbon_records_calc_date');
            $table->dropIndex('idx_carbon_records_product_category');
            $table->dropIndex('idx_carbon_records_category_date');
        });

        // Carbon Credits
        Schema::table('carbon_credits', function (Blueprint $table) {
            $table->dropIndex('idx_carbon_credits_user');
            $table->dropIndex('idx_carbon_credits_product');
            $table->dropIndex('idx_carbon_credits_traded_to');
            $table->dropIndex('idx_carbon_credits_status');
            $table->dropIndex('idx_carbon_credits_tradeable');
            $table->dropIndex('idx_carbon_credits_type');
            $table->dropIndex('idx_carbon_credits_blockchain');
            $table->dropIndex('idx_carbon_credits_token_id');
            $table->dropIndex('idx_carbon_credits_issued');
            $table->dropIndex('idx_carbon_credits_expiry');
            $table->dropIndex('idx_carbon_credits_traded_at');
            $table->dropIndex('idx_carbon_credits_marketplace');
            $table->dropIndex('idx_carbon_credits_user_status');
            $table->dropIndex('idx_carbon_credits_expiry_status');
        });

        // Food Certifications
        Schema::table('food_certifications', function (Blueprint $table) {
            $table->dropIndex('idx_food_certifications_product');
            $table->dropIndex('idx_food_certifications_type');
            $table->dropIndex('idx_food_certifications_body');
            $table->dropIndex('idx_food_certifications_cert_number');
            $table->dropIndex('idx_food_certifications_status');
            $table->dropIndex('idx_food_certifications_blockchain');
            $table->dropIndex('idx_food_certifications_ipfs');
            $table->dropIndex('idx_food_certifications_verification');
            $table->dropIndex('idx_food_certifications_issued');
            $table->dropIndex('idx_food_certifications_expiry');
            $table->dropIndex('idx_food_certifications_product_type');
            $table->dropIndex('idx_food_certifications_status_expiry');
        });

        // Stakeholder Roles
        Schema::table('stakeholder_roles', function (Blueprint $table) {
            $table->dropIndex('idx_stakeholder_roles_product');
            $table->dropIndex('idx_stakeholder_roles_user');
            $table->dropIndex('idx_stakeholder_roles_role');
            $table->dropIndex('idx_stakeholder_roles_active');
            $table->dropIndex('idx_stakeholder_roles_product_role');
            $table->dropIndex('idx_stakeholder_roles_user_role_active');
        });

        // Consumer Scans
        Schema::table('consumer_scans', function (Blueprint $table) {
            $table->dropIndex('idx_consumer_scans_product');
            $table->dropIndex('idx_consumer_scans_scanned_at');
            $table->dropIndex('idx_consumer_scans_device');
            $table->dropIndex('idx_consumer_scans_product_date');
        });
    }
};

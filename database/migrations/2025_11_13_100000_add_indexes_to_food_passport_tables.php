<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        $this->safeAddIndexes('food_products', [
            ['food_passport_id', 'idx_food_products_passport_id'],
            ['farmer_id', 'idx_food_products_farmer_id'],
            ['product_type', 'idx_food_products_type'],
            ['carbon_score', 'idx_food_products_carbon_score'],
            ['blockchain_hash', 'idx_food_products_blockchain'],
            ['ipfs_hash', 'idx_food_products_ipfs'],
            ['harvest_date', 'idx_food_products_harvest_date'],
            ['created_at', 'idx_food_products_created'],
            [['farmer_id', 'product_type'], 'idx_food_products_farmer_type'],
        ]);

        // Product Journey Table Indexes
        $this->safeAddIndexes('product_journey', [
            ['food_product_id', 'idx_product_journey_product_id'],
            ['handler_id', 'idx_product_journey_handler'],
            ['stage', 'idx_product_journey_stage'],
            ['sequence_order', 'idx_product_journey_sequence'],
            ['quality_status', 'idx_product_journey_quality'],
            ['blockchain_hash', 'idx_product_journey_blockchain'],
            ['arrived_at', 'idx_product_journey_arrived'],
            ['departed_at', 'idx_product_journey_departed'],
            [['food_product_id', 'sequence_order'], 'idx_product_journey_product_seq'],
            [['food_product_id', 'stage'], 'idx_product_journey_product_stage'],
        ]);

        // Quality Checkpoints Table Indexes
        $this->safeAddIndexes('quality_checkpoints', [
            ['food_product_id', 'idx_quality_checkpoints_product'],
            ['product_journey_id', 'idx_quality_checkpoints_journey'],
            ['inspector_id', 'idx_quality_checkpoints_inspector'],
            ['checkpoint_type', 'idx_quality_checkpoints_type'],
            ['overall_result', 'idx_quality_checkpoints_result'],
            ['certification_issued', 'idx_quality_checkpoints_cert'],
            ['blockchain_hash', 'idx_quality_checkpoints_blockchain'],
            ['checked_at', 'idx_quality_checkpoints_checked'],
            ['certificate_expiry', 'idx_quality_checkpoints_expiry'],
            [['food_product_id', 'overall_result'], 'idx_quality_checkpoints_product_result'],
            [['inspector_id', 'checked_at'], 'idx_quality_checkpoints_inspector_date'],
        ]);

        // Carbon Footprint Records Table Indexes
        $this->safeAddIndexes('carbon_footprint_records', [
            ['food_product_id', 'idx_carbon_records_product'],
            ['product_journey_id', 'idx_carbon_records_journey'],
            ['verified_by_id', 'idx_carbon_records_verifier'],
            ['emission_category', 'idx_carbon_records_category'],
            ['verification_status', 'idx_carbon_records_verification'],
            ['calculation_date', 'idx_carbon_records_calc_date'],
            [['food_product_id', 'emission_category'], 'idx_carbon_records_product_category'],
            [['emission_category', 'calculation_date'], 'idx_carbon_records_category_date'],
        ]);

        // Carbon Credits Table Indexes
        $this->safeAddIndexes('carbon_credits', [
            ['user_id', 'idx_carbon_credits_user'],
            ['food_product_id', 'idx_carbon_credits_product'],
            ['traded_to_user_id', 'idx_carbon_credits_traded_to'],
            ['status', 'idx_carbon_credits_status'],
            ['tradeable', 'idx_carbon_credits_tradeable'],
            ['credit_type', 'idx_carbon_credits_type'],
            ['blockchain_hash', 'idx_carbon_credits_blockchain'],
            ['token_id', 'idx_carbon_credits_token_id'],
            ['issued_date', 'idx_carbon_credits_issued'],
            ['expiry_date', 'idx_carbon_credits_expiry'],
            ['traded_at', 'idx_carbon_credits_traded_at'],
            [['status', 'tradeable'], 'idx_carbon_credits_marketplace'],
            [['user_id', 'status'], 'idx_carbon_credits_user_status'],
            [['expiry_date', 'status'], 'idx_carbon_credits_expiry_status'],
        ]);

        // Food Certifications Table Indexes
        $this->safeAddIndexes('food_certifications', [
            ['food_product_id', 'idx_food_certifications_product'],
            ['certification_type', 'idx_food_certifications_type'],
            ['certifying_body', 'idx_food_certifications_body'],
            ['certificate_number', 'idx_food_certifications_cert_number'],
            ['status', 'idx_food_certifications_status'],
            ['blockchain_hash', 'idx_food_certifications_blockchain'],
            ['ipfs_hash', 'idx_food_certifications_ipfs'],
            ['verification_code', 'idx_food_certifications_verification'],
            ['issued_date', 'idx_food_certifications_issued'],
            ['expiry_date', 'idx_food_certifications_expiry'],
            [['food_product_id', 'certification_type'], 'idx_food_certifications_product_type'],
            [['status', 'expiry_date'], 'idx_food_certifications_status_expiry'],
        ]);

        // Stakeholder Roles Table Indexes
        $this->safeAddIndexes('stakeholder_roles', [
            ['food_product_id', 'idx_stakeholder_roles_product'],
            ['user_id', 'idx_stakeholder_roles_user'],
            ['role', 'idx_stakeholder_roles_role'],
            ['is_active', 'idx_stakeholder_roles_active'],
            [['food_product_id', 'role'], 'idx_stakeholder_roles_product_role'],
            [['user_id', 'role', 'is_active'], 'idx_stakeholder_roles_user_role_active'],
        ]);

        // Consumer Scans Table Indexes
        $this->safeAddIndexes('consumer_scans', [
            ['food_product_id', 'idx_consumer_scans_product'],
            ['scanned_at', 'idx_consumer_scans_scanned_at'],
            ['device_type', 'idx_consumer_scans_device'],
            [['food_product_id', 'scanned_at'], 'idx_consumer_scans_product_date'],
        ]);
    }

    /**
     * เพิ่ม indexes อย่างปลอดภัย - ตรวจสอบ column และ index ก่อนสร้าง
     */
    private function safeAddIndexes(string $table, array $indexes): void
    {
        // ตรวจสอบว่าตารางมีอยู่
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($indexes as $indexDef) {
            $columns = $indexDef[0];
            $indexName = $indexDef[1];

            // แปลง column เดียวเป็น array
            if (!is_array($columns)) {
                $columns = [$columns];
            }

            // ตรวจสอบว่าทุก column มีอยู่
            $allColumnsExist = true;
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $allColumnsExist = false;
                    break;
                }
            }

            if (!$allColumnsExist) {
                continue; // ข้าม index นี้ถ้า column ไม่มี
            }

            // ตรวจสอบว่า index มีอยู่แล้วหรือไม่
            if ($this->indexExists($table, $indexName)) {
                continue; // ข้าม index นี้ถ้ามีอยู่แล้ว
            }

            // สร้าง index
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                    $blueprint->index($columns, $indexName);
                });
            } catch (\Exception $e) {
                // ถ้าสร้างไม่ได้ก็ข้าม
            }
        }
    }

    /**
     * ตรวจสอบว่า index มีอยู่แล้วหรือไม่
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$databaseName, $table, $indexName]);

        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->safeDropIndexes('food_products', [
            'idx_food_products_passport_id',
            'idx_food_products_farmer_id',
            'idx_food_products_type',
            'idx_food_products_carbon_score',
            'idx_food_products_blockchain',
            'idx_food_products_ipfs',
            'idx_food_products_harvest_date',
            'idx_food_products_created',
            'idx_food_products_farmer_type',
        ]);

        $this->safeDropIndexes('product_journey', [
            'idx_product_journey_product_id',
            'idx_product_journey_handler',
            'idx_product_journey_stage',
            'idx_product_journey_sequence',
            'idx_product_journey_quality',
            'idx_product_journey_blockchain',
            'idx_product_journey_arrived',
            'idx_product_journey_departed',
            'idx_product_journey_product_seq',
            'idx_product_journey_product_stage',
        ]);

        $this->safeDropIndexes('quality_checkpoints', [
            'idx_quality_checkpoints_product',
            'idx_quality_checkpoints_journey',
            'idx_quality_checkpoints_inspector',
            'idx_quality_checkpoints_type',
            'idx_quality_checkpoints_result',
            'idx_quality_checkpoints_cert',
            'idx_quality_checkpoints_blockchain',
            'idx_quality_checkpoints_checked',
            'idx_quality_checkpoints_expiry',
            'idx_quality_checkpoints_product_result',
            'idx_quality_checkpoints_inspector_date',
        ]);

        $this->safeDropIndexes('carbon_footprint_records', [
            'idx_carbon_records_product',
            'idx_carbon_records_journey',
            'idx_carbon_records_verifier',
            'idx_carbon_records_category',
            'idx_carbon_records_verification',
            'idx_carbon_records_calc_date',
            'idx_carbon_records_product_category',
            'idx_carbon_records_category_date',
        ]);

        $this->safeDropIndexes('carbon_credits', [
            'idx_carbon_credits_user',
            'idx_carbon_credits_product',
            'idx_carbon_credits_traded_to',
            'idx_carbon_credits_status',
            'idx_carbon_credits_tradeable',
            'idx_carbon_credits_type',
            'idx_carbon_credits_blockchain',
            'idx_carbon_credits_token_id',
            'idx_carbon_credits_issued',
            'idx_carbon_credits_expiry',
            'idx_carbon_credits_traded_at',
            'idx_carbon_credits_marketplace',
            'idx_carbon_credits_user_status',
            'idx_carbon_credits_expiry_status',
        ]);

        $this->safeDropIndexes('food_certifications', [
            'idx_food_certifications_product',
            'idx_food_certifications_type',
            'idx_food_certifications_body',
            'idx_food_certifications_cert_number',
            'idx_food_certifications_status',
            'idx_food_certifications_blockchain',
            'idx_food_certifications_ipfs',
            'idx_food_certifications_verification',
            'idx_food_certifications_issued',
            'idx_food_certifications_expiry',
            'idx_food_certifications_product_type',
            'idx_food_certifications_status_expiry',
        ]);

        $this->safeDropIndexes('stakeholder_roles', [
            'idx_stakeholder_roles_product',
            'idx_stakeholder_roles_user',
            'idx_stakeholder_roles_role',
            'idx_stakeholder_roles_active',
            'idx_stakeholder_roles_product_role',
            'idx_stakeholder_roles_user_role_active',
        ]);

        $this->safeDropIndexes('consumer_scans', [
            'idx_consumer_scans_product',
            'idx_consumer_scans_scanned_at',
            'idx_consumer_scans_device',
            'idx_consumer_scans_product_date',
        ]);
    }

    /**
     * ลบ indexes อย่างปลอดภัย
     */
    private function safeDropIndexes(string $table, array $indexNames): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($indexNames as $indexName) {
            if ($this->indexExists($table, $indexName)) {
                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                        $blueprint->dropIndex($indexName);
                    });
                } catch (\Exception $e) {
                    // ถ้าลบไม่ได้ก็ข้าม
                }
            }
        }
    }
};

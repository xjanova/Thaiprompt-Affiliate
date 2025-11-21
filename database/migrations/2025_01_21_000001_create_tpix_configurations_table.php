<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง tpix_configurations
     *
     * ตารางนี้เก็บการตั้งค่าสำหรับ TPIX Native Coin Deployment Wizard
     * ใช้สำหรับติดตาม progress และเก็บค่าต่างๆ ก่อน deploy
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('tpix_configurations')) {
            return;
        }

        Schema::create('tpix_configurations', function (Blueprint $table) {
            $table->id();

            // ผู้สร้าง configuration
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้สร้าง configuration');

            // ชื่อและระบุ config
            $table->string('name', 100)->comment('ชื่อ configuration');
            $table->string('slug', 100)->unique()->comment('Slug สำหรับ URL');

            // สถานะและ progress
            $table->enum('status', [
                'draft',              // กำลังสร้าง
                'prerequisites_done', // ผ่าน step 1
                'config_done',        // ผ่าน step 2
                'tokenomics_done',    // ผ่าน step 3
                'contract_done',      // ผ่าน step 4
                'deployed',           // deploy แล้ว (step 5)
                'dex_ready',          // DEX พร้อม (step 6)
                'listed',             // list แล้ว (step 7)
                'failed',             // ล้มเหลว
            ])->default('draft')->comment('สถานะปัจจุบัน');

            $table->tinyInteger('current_step')->default(1)->comment('ขั้นตอนปัจจุบัน (1-7)');

            // =====================================
            // STEP 1: Prerequisites Data
            // =====================================
            $table->json('prerequisites')->nullable()->comment('ข้อมูล prerequisites check');

            // =====================================
            // STEP 2: Token Configuration
            // =====================================
            // Basic Info
            $table->string('token_name', 100)->nullable()->comment('ชื่อเหรียญ');
            $table->string('token_symbol', 20)->nullable()->comment('สัญลักษณ์เหรียญ');
            $table->decimal('total_supply', 30, 8)->nullable()->comment('จำนวนเหรียญทั้งหมด');
            $table->tinyInteger('decimals')->default(18)->comment('จำนวนทศนิยม');
            $table->text('description')->nullable()->comment('คำอธิบาย');
            $table->string('logo_url')->nullable()->comment('URL โลโก้');
            $table->string('website_url')->nullable()->comment('เว็บไซต์');
            $table->enum('category', [
                'defi', 'gamefi', 'meme', 'utility',
                'stablecoin', 'nft', 'dao', 'other'
            ])->nullable()->comment('หมวดหมู่');

            // Social Links
            $table->json('social_links')->nullable()->comment('ลิงก์ social media');

            // =====================================
            // STEP 3: Tokenomics
            // =====================================
            $table->json('supply_distribution')->nullable()->comment('การกระจายเหรียญ');
            $table->decimal('initial_price_tpix', 20, 8)->nullable()->comment('ราคาเริ่มต้น (TPIX)');
            $table->decimal('initial_price_usd', 20, 8)->nullable()->comment('ราคาเริ่มต้น (USD)');
            $table->decimal('market_cap_target', 30, 2)->nullable()->comment('Market cap เป้าหมาย');

            // Token Features
            $table->boolean('is_mintable')->default(false)->comment('สามารถสร้างเพิ่มได้');
            $table->boolean('is_burnable')->default(true)->comment('สามารถเผาได้');
            $table->boolean('is_pausable')->default(false)->comment('สามารถหยุดชั่วคราวได้');
            $table->boolean('is_freezable')->default(false)->comment('สามารถแช่แข็งได้');
            $table->decimal('max_supply', 30, 8)->nullable()->comment('Supply สูงสุด (ถ้า mintable)');

            // Fee Structure
            $table->json('fee_structure')->nullable()->comment('โครงสร้างค่าธรรมเนียม');

            // =====================================
            // STEP 4: Smart Contract Customization
            // =====================================
            $table->json('contract_features')->nullable()->comment('ฟีเจอร์ของ smart contract');
            $table->text('contract_code')->nullable()->comment('โค้ด smart contract ที่ customize');
            $table->enum('access_control', ['owner', 'role_based', 'dao', 'multi_sig'])
                ->default('owner')
                ->comment('ระบบควบคุมการเข้าถึง');

            $table->json('safety_features')->nullable()->comment('ฟีเจอร์ความปลอดภัย');
            $table->boolean('is_upgradeable')->default(false)->comment('สามารถ upgrade ได้');

            // =====================================
            // STEP 5: Deployment
            // =====================================
            $table->string('contract_address', 42)->nullable()->comment('Contract address หลัง deploy');
            $table->string('deployer_address', 42)->nullable()->comment('Address ผู้ deploy');
            $table->string('deploy_tx_hash', 66)->nullable()->comment('Transaction hash ของการ deploy');
            $table->timestamp('deployed_at')->nullable()->comment('เวลาที่ deploy');
            $table->boolean('is_verified')->default(false)->comment('ยืนยันบน explorer แล้ว');
            $table->string('explorer_url')->nullable()->comment('URL บน explorer');

            // Gas และ cost
            $table->decimal('deploy_gas_used', 20, 0)->nullable()->comment('Gas ที่ใช้ใน deploy');
            $table->decimal('deploy_cost_tpix', 20, 8)->nullable()->comment('ค่าใช้จ่าย deploy (TPIX)');

            // =====================================
            // STEP 6: DEX Integration
            // =====================================
            $table->boolean('dex_enabled')->default(false)->comment('เปิดใช้ DEX');
            $table->foreignId('liquidity_pool_id')
                ->nullable()
                ->constrained('tpix_liquidity_pools')
                ->nullOnDelete()
                ->comment('Pool ID บน DEX');

            $table->decimal('initial_liquidity_tpix', 20, 8)->nullable()->comment('Liquidity เริ่มต้น (TPIX)');
            $table->decimal('initial_liquidity_token', 30, 8)->nullable()->comment('Liquidity เริ่มต้น (Token)');
            $table->decimal('liquidity_lock_days', 10, 2)->nullable()->comment('ระยะเวลาล็อค liquidity (วัน)');

            // Trading Parameters
            $table->json('trading_params')->nullable()->comment('พารามิเตอร์การซื้อขาย');
            $table->timestamp('trading_enabled_at')->nullable()->comment('เวลาที่เปิดการซื้อขาย');

            // =====================================
            // STEP 7: Listing & Marketing
            // =====================================
            // CoinMarketCap
            $table->boolean('cmc_submitted')->default(false)->comment('ส่ง CoinMarketCap แล้ว');
            $table->string('cmc_id')->nullable()->comment('CoinMarketCap ID');
            $table->timestamp('cmc_submitted_at')->nullable()->comment('เวลาที่ส่ง CMC');
            $table->enum('cmc_status', ['pending', 'approved', 'rejected'])->nullable();

            // CoinGecko
            $table->boolean('coingecko_submitted')->default(false)->comment('ส่ง CoinGecko แล้ว');
            $table->string('coingecko_id')->nullable()->comment('CoinGecko ID');
            $table->timestamp('coingecko_submitted_at')->nullable()->comment('เวลาที่ส่ง CoinGecko');
            $table->enum('coingecko_status', ['pending', 'approved', 'rejected'])->nullable();

            // Marketing Materials
            $table->json('marketing_materials')->nullable()->comment('เอกสารการตลาด');
            $table->text('whitepaper_url')->nullable()->comment('URL whitepaper');
            $table->text('pitch_deck_url')->nullable()->comment('URL pitch deck');

            // =====================================
            // Metadata และ Tracking
            // =====================================
            $table->json('deployment_logs')->nullable()->comment('Log การ deploy แต่ละขั้นตอน');
            $table->json('error_logs')->nullable()->comment('Log ข้อผิดพลาด');
            $table->text('notes')->nullable()->comment('หมายเหตุเพิ่มเติม');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status'], 'user_status_idx');
            $table->index('current_step', 'step_idx');
            $table->index('contract_address', 'contract_idx');
            $table->index('created_at', 'created_idx');
        });
    }

    /**
     * ลบตาราง tpix_configurations
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tpix_configurations');
    }
};

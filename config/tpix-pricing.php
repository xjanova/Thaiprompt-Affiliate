<?php

/**
 * TPIX Deployment Wizard - Pricing Configuration
 *
 * กำหนดราคาค่าบริการสำหรับการ Deploy Native Coin บน TPIX Blockchain
 * ราคาทั้งหมดเป็น TPIX tokens
 *
 * @version 1.0.0
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Base Deployment Cost
    |--------------------------------------------------------------------------
    |
    | ค่าใช้จ่ายพื้นฐานสำหรับการ Deploy Token แบบมาตรฐาน
    | รวม: Token Configuration, Basic Smart Contract, Deployment, Verification
    |
    */
    'base_deployment' => 100, // TPIX

    /*
    |--------------------------------------------------------------------------
    | Contract Features Pricing (Step 4)
    |--------------------------------------------------------------------------
    |
    | ราคาสำหรับฟีเจอร์เพิ่มเติมของ Smart Contract
    | แต่ละฟีเจอร์เพิ่มความซับซ้อนและค่าใช้จ่ายในการพัฒนา/audit
    |
    */
    'contract_features' => [
        'burnable' => 50,      // ฟีเจอร์เผา token (ลดความซับซ้อน)
        'mintable' => 100,     // สร้าง token เพิ่มได้ (ต้องระวัง security)
        'pausable' => 75,      // หยุดการทำธุรกรรมชั่วคราว (emergency)
        'freezable' => 75,     // แช่แข็ง account เฉพาะ (anti-fraud)
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control Pricing (Step 4)
    |--------------------------------------------------------------------------
    |
    | ราคาสำหรับระบบควบคุมการเข้าถึง
    | ยิ่งซับซ้อนยิ่งมีค่าใช้จ่ายสูง
    |
    */
    'access_control' => [
        'owner'        => 0,     // Owner Only (default, ไม่คิดค่าใช้จ่าย)
        'role_based'   => 150,   // Role-Based Access Control (RBAC)
        'dao'          => 300,   // DAO Governance (ซับซ้อนมาก)
        'multi_sig'    => 250,   // Multi-Signature (ต้องมี consensus)
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Contract Features
    |--------------------------------------------------------------------------
    |
    | ฟีเจอร์ขั้นสูงที่ต้องใช้เทคโนโลยีซับซ้อน
    |
    */
    'advanced_features' => [
        'upgradeable' => 200,  // Upgradeable Contract (Proxy Pattern)
    ],

    /*
    |--------------------------------------------------------------------------
    | DEX Integration Pricing (Step 6)
    |--------------------------------------------------------------------------
    |
    | ค่าบริการสำหรับการ integrate กับ DEX
    |
    */
    'dex_integration' => [
        'create_pool'       => 200,  // สร้าง Liquidity Pool
        'initial_liquidity' => 0,    // เพิ่ม Liquidity ครั้งแรก (ไม่คิดค่าบริการเพิ่ม)
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification & Listing Pricing (Step 7)
    |--------------------------------------------------------------------------
    |
    | ค่าบริการสำหรับการ Verify และ List บน Aggregators
    |
    */
    'verification_listing' => [
        'contract_verification' => 50,    // Verify Contract บน Block Explorer
        'coinmarketcap'        => 500,    // Submit ไปยัง CoinMarketCap (Premium Service)
        'coingecko'            => 500,    // Submit ไปยัง CoinGecko (Premium Service)
    ],

    /*
    |--------------------------------------------------------------------------
    | Tokenomics Features Pricing (Step 3)
    |--------------------------------------------------------------------------
    |
    | ค่าบริการสำหรับฟีเจอร์ Tokenomics ขั้นสูง
    |
    */
    'tokenomics_features' => [
        'vesting_schedule'  => 100,  // Vesting Schedule สำหรับ Team/Advisors
        'token_lock'        => 75,   // Token Lock Mechanism
        'auto_burn'         => 50,   // Auto-Burn บน Transaction
        'reflection'        => 150,  // Reflection/Redistribution
        'transaction_tax'   => 100,  // Transaction Tax (Buy/Sell)
    ],

    /*
    |--------------------------------------------------------------------------
    | Premium Support & Services
    |--------------------------------------------------------------------------
    |
    | บริการเสริมและ Support
    |
    */
    'premium_services' => [
        'audit_report'           => 1000,  // Smart Contract Audit Report
        'marketing_package'      => 500,   // Marketing Package (Social Media Setup)
        'whitepaper_creation'    => 750,   // Whitepaper Creation Service
        'priority_support'       => 300,   // Priority Support (30 วัน)
        'custom_tokenomics'      => 500,   // Custom Tokenomics Consultation
    ],

    /*
    |--------------------------------------------------------------------------
    | Discounts & Promotions
    |--------------------------------------------------------------------------
    |
    | ส่วนลดและโปรโมชั่น
    |
    */
    'discounts' => [
        'early_adopter'    => 20,  // Early Adopter Discount (20%)
        'bulk_deployment'  => 15,  // Deploy 5+ tokens (15% per token)
        'referral'         => 10,  // Referral Discount (10%)
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum Balance Requirements
    |--------------------------------------------------------------------------
    |
    | ยอด TPIX ขั้นต่ำที่ต้องมีใน Wallet
    |
    */
    'minimum_balance' => [
        'deployment'     => 50,   // ต้องมี TPIX อย่างน้อย 50 เพื่อเริ่ม Deploy
        'gas_reserve'    => 10,   // สำรอง TPIX สำหรับค่า Gas
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าการชำระเงิน
    |
    */
    'payment' => [
        'wallet_required'        => true,   // บังคับต้องเชื่อม wallet
        'pay_before_deployment'  => true,   // ชำระก่อน Deploy
        'refund_on_failure'      => true,   // คืนเงินถ้า Deploy ล้มเหลว
        'refund_percentage'      => 80,     // คืนเงิน 80% (หัก 20% ค่าดำเนินการ)
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Fee Wallet
    |--------------------------------------------------------------------------
    |
    | กระเป๋าที่รับค่าบริการ
    |
    */
    'service_fee_wallet' => env('TPIX_SERVICE_FEE_WALLET', '0x0000000000000000000000000000000000000000'),

    /*
    |--------------------------------------------------------------------------
    | Price Display Format
    |--------------------------------------------------------------------------
    |
    | รูปแบบการแสดงราคา
    |
    */
    'display' => [
        'currency'       => 'TPIX',
        'decimal_places' => 2,
        'show_usd'       => true,  // แสดงราคาเป็น USD (ถ้ามี exchange rate)
    ],

];

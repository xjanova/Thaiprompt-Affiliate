<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->timestamps();
        });

        // Create permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('category')->default('general');
            $table->timestamps();
        });

        // Create role_permissions pivot table
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // Insert default roles
        DB::table('roles')->insert([
            [
                'name' => 'user',
                'display_name' => 'ผู้ใช้ทั่วไป',
                'description' => 'ผู้ใช้งานระดับพื้นฐาน สามารถใช้งานระบบแอฟฟิลิเอทได้',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'seller',
                'display_name' => 'ผู้ขาย',
                'description' => 'ผู้ขายสินค้า สามารถจัดการร้านค้าและสินค้าของตัวเอง',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'display_name' => 'ผู้ดูแลระบบ',
                'description' => 'ผู้ดูแลระบบ มีสิทธิ์ตามที่กำหนดโดย Super Admin',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'super_admin',
                'display_name' => 'ผู้ดูแลระบบสูงสุด',
                'description' => 'ผู้ดูแลระบบสูงสุด มีสิทธิ์เข้าถึงทุกอย่างในระบบ',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Insert all available permissions
        $permissions = [
            // System Core
            ['name' => 'view_dashboard', 'display_name' => 'ดูแดชบอร์ด', 'category' => 'system', 'description' => 'เข้าถึงและดูแดชบอร์ดของระบบ'],
            ['name' => 'view_reports', 'display_name' => 'ดูรายงาน', 'category' => 'system', 'description' => 'ดูรายงานและสถิติต่างๆ'],
            ['name' => 'manage_settings', 'display_name' => 'จัดการการตั้งค่า', 'category' => 'system', 'description' => 'จัดการการตั้งค่าระบบ'],
            ['name' => 'manage_branding', 'display_name' => 'จัดการแบรนด์', 'category' => 'system', 'description' => 'จัดการโลโก้ สี และแบรนด์ของระบบ'],

            // User Management
            ['name' => 'manage_users', 'display_name' => 'จัดการผู้ใช้', 'category' => 'users', 'description' => 'สร้าง แก้ไข และลบผู้ใช้'],
            ['name' => 'manage_permissions', 'display_name' => 'จัดการสิทธิ์', 'category' => 'users', 'description' => 'กำหนดสิทธิ์ให้กับบทบาทต่างๆ'],

            // Affiliate & Commission
            ['name' => 'manage_affiliates', 'display_name' => 'จัดการแอฟฟิลิเอท', 'category' => 'affiliate', 'description' => 'จัดการระบบแอฟฟิลิเอท'],
            ['name' => 'manage_commissions', 'display_name' => 'จัดการค่าคอมมิชชั่น', 'category' => 'affiliate', 'description' => 'จัดการค่าคอมมิชชั่นและการจ่าย'],

            // Wallet & Finance
            ['name' => 'manage_wallets', 'display_name' => 'จัดการกระเป๋าเงิน', 'category' => 'finance', 'description' => 'จัดการกระเป๋าเงินของผู้ใช้'],
            ['name' => 'approve_withdrawals', 'display_name' => 'อนุมัติการถอนเงิน', 'category' => 'finance', 'description' => 'อนุมัติคำขอถอนเงิน'],
            ['name' => 'view_all_wallets', 'display_name' => 'ดูกระเป๋าเงินทั้งหมด', 'category' => 'finance', 'description' => 'ดูกระเป๋าเงินของผู้ใช้ทั้งหมด'],
            ['name' => 'manage_wallet_settings', 'display_name' => 'จัดการตั้งค่ากระเป๋าเงิน', 'category' => 'finance', 'description' => 'จัดการการตั้งค่ากระเป๋าเงิน'],

            // Rank System
            ['name' => 'manage_ranks', 'display_name' => 'จัดการระดับ', 'category' => 'ranks', 'description' => 'จัดการระบบระดับและเงื่อนไข'],
            ['name' => 'approve_rank_promotions', 'display_name' => 'อนุมัติการเลื่อนระดับ', 'category' => 'ranks', 'description' => 'อนุมัติการเลื่อนระดับของสมาชิก'],
            ['name' => 'view_rank_analytics', 'display_name' => 'ดูสถิติระดับ', 'category' => 'ranks', 'description' => 'ดูสถิติและวิเคราะห์ระบบระดับ'],

            // KYC Management
            ['name' => 'view_kyc_verifications', 'display_name' => 'ดูการยืนยันตัวตน', 'category' => 'kyc', 'description' => 'ดูข้อมูลการยืนยันตัวตน KYC'],
            ['name' => 'approve_kyc', 'display_name' => 'อนุมัติ KYC', 'category' => 'kyc', 'description' => 'อนุมัติการยืนยันตัวตน KYC'],
            ['name' => 'manage_kyc', 'display_name' => 'จัดการ KYC', 'category' => 'kyc', 'description' => 'จัดการระบบยืนยันตัวตนทั้งหมด'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'category' => $permission['category'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};

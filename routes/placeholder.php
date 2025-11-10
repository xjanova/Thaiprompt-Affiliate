<?php

use Illuminate\Support\Facades\Route;

/**
 * Placeholder Routes for Menu System
 *
 * These routes are temporary placeholders for menu items that don't have
 * actual controllers yet. They will show a "Coming Soon" page.
 *
 * HOW TO USE:
 * 1. Include this file in routes/web.php:
 *    require __DIR__.'/placeholder.php';
 *
 * 2. As you build actual features, remove the placeholder and create real route
 *
 * IMPORTANT: These routes use a simple closure that returns a view.
 * You can customize the view or create a proper controller later.
 */

// Helper function to generate "Coming Soon" page
if (!function_exists('comingSoonPage')) {
    function comingSoonPage($title = 'หน้านี้') {
        return view('layouts.coming-soon', compact('title'));
    }
}

// ============================================================================
// ADMIN ROUTES - Placeholders
// ============================================================================

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', fn() => comingSoonPage('แดชบอร์ด Admin'))->name('dashboard');

    // Users Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', fn() => comingSoonPage('รายชื่อผู้ใช้'))->name('index');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', fn() => comingSoonPage('บทบาท (Roles)'))->name('index');
    });

    // KYC
    Route::prefix('kyc')->name('kyc.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ยืนยันตัวตน KYC'))->name('index');
    });

    // Tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Ticket Support'))->name('index');
    });

    // AI Bots
    Route::prefix('ai-bots')->name('ai-bots.')->group(function () {
        Route::get('/', fn() => comingSoonPage('จัดการ AI Bots'))->name('index');
    });

    Route::prefix('ai-providers')->name('ai-providers.')->group(function () {
        Route::get('/', fn() => comingSoonPage('AI Providers'))->name('index');
    });

    Route::prefix('ai-installation')->name('ai-installation.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ติดตั้ง AI'))->name('index');
    });

    // Hotels
    Route::prefix('hotels')->name('hotels.')->group(function () {
        Route::get('/', fn() => comingSoonPage('โรงแรมทั้งหมด'))->name('index');

        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', fn() => comingSoonPage('การจองทั้งหมด'))->name('index');
            Route::get('/analytics', fn() => comingSoonPage('สถิติการจอง'))->name('analytics');
        });

        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/', fn() => comingSoonPage('จัดการรีวิว'))->name('index');
        });

        Route::prefix('facilities')->name('facilities.')->group(function () {
            Route::get('/', fn() => comingSoonPage('สิ่งอำนวยความสะดวก'))->name('index');
        });

        Route::prefix('special-offers')->name('special-offers.')->group(function () {
            Route::get('/', fn() => comingSoonPage('โปรโมชั่นพิเศษ'))->name('index');
        });
    });

    // Ecommerce
    Route::prefix('ecommerce')->name('ecommerce.')->group(function () {
        Route::get('/dashboard', fn() => comingSoonPage('แดชบอร์ดอีคอมเมิร์ซ'))->name('dashboard');

        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', fn() => comingSoonPage('สินค้าทั้งหมด'))->name('index');
        });

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', fn() => comingSoonPage('คำสั่งซื้อ'))->name('index');
        });

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', fn() => comingSoonPage('หมวดหมู่สินค้า'))->name('index');
        });

        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/', fn() => comingSoonPage('รีวิวสินค้า'))->name('index');
        });
    });

    // POS System
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/dashboard', fn() => comingSoonPage('แดชบอร์ด POS'))->name('dashboard');

        Route::prefix('devices')->name('devices.')->group(function () {
            Route::get('/', fn() => comingSoonPage('อุปกรณ์ POS'))->name('index');
        });

        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ธุรกรรม POS'))->name('index');
        });

        Route::get('/analytics', fn() => comingSoonPage('วิเคราะห์ข้อมูล POS'))->name('analytics');
    });

    // Wallet THB
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', fn() => comingSoonPage('กระเป๋าเงินทั้งหมด'))->name('index');
        Route::get('/transactions', fn() => comingSoonPage('ประวัติธุรกรรม'))->name('transactions');
    });

    Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
        Route::get('/pending', fn() => comingSoonPage('คำขอถอนเงิน'))->name('pending');
        Route::get('/', fn() => comingSoonPage('ประวัติการถอน'))->name('index');
    });

    Route::prefix('payment-gateways')->name('payment-gateways.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ตั้งค่า Payment Gateway'))->name('index');
    });

    // Crypto Wallet
    Route::prefix('crypto')->name('crypto.')->group(function () {
        Route::get('/dashboard', fn() => comingSoonPage('แดชบอร์ดคริปโต'))->name('dashboard');
        Route::get('/wallets', fn() => comingSoonPage('จัดการ Wallets'))->name('wallets');
        Route::get('/transactions', fn() => comingSoonPage('ธุรกรรมคริปโต'))->name('transactions');
        Route::get('/withdrawals', fn() => comingSoonPage('คำขอถอนคริปโต'))->name('withdrawals');
        Route::get('/currencies', fn() => comingSoonPage('จัดการเหรียญ/สกุลเงิน'))->name('currencies');
        Route::get('/settings', fn() => comingSoonPage('ตั้งค่าคริปโต'))->name('settings');
    });

    Route::prefix('wallet-settings')->name('wallet-settings.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ตั้งค่ากระเป๋าเงิน'))->name('index');
    });

    // Commissions
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', fn() => comingSoonPage('รายการคอมมิชชั่น'))->name('index');
    });

    // Email Management
    Route::prefix('email')->name('email.')->group(function () {
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', fn() => comingSoonPage('เทมเพลตอีเมล'))->name('index');
        });
        Route::get('/providers', fn() => comingSoonPage('ผู้ให้บริการอีเมล'))->name('providers');
        Route::get('/logs', fn() => comingSoonPage('ประวัติการส่งอีเมล'))->name('logs');
    });

    // LINE OA & AI
    Route::prefix('line-oa')->name('line-oa.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ตั้งค่า LINE OA'))->name('index');
    });

    Route::prefix('line-bot')->name('line-bot.')->group(function () {
        Route::prefix('ai')->name('ai.')->group(function () {
            Route::get('/', fn() => comingSoonPage('AI Chat Bot'))->name('index');
        });

        Route::prefix('broadcast')->name('broadcast.')->group(function () {
            Route::get('/', fn() => comingSoonPage('Broadcast'))->name('index');
        });

        Route::prefix('avatars')->name('avatars.')->group(function () {
            Route::get('/', fn() => comingSoonPage('Avatar'))->name('index');
        });

        Route::prefix('chat-widget')->name('chat-widget.')->group(function () {
            Route::get('/', fn() => comingSoonPage('Chat Widget'))->name('index');
        });
    });

    // Academy System
    Route::prefix('academy')->name('academy.')->group(function () {
        Route::prefix('courses')->name('courses.')->group(function () {
            Route::get('/', fn() => comingSoonPage('คอร์สเรียน'))->name('index');
        });

        Route::prefix('certificates')->name('certificates.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ใบประกาศ'))->name('index');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ตั้งค่า Academy'))->name('index');
        });
    });

    // Learning Center
    Route::prefix('articles')->name('articles.')->group(function () {
        Route::get('/', fn() => comingSoonPage('บทความ'))->name('index');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', fn() => comingSoonPage('หมวดหมู่'))->name('index');
    });

    Route::prefix('learning-center')->name('learning-center.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ศูนย์เรียนรู้'))->name('index');
    });

    // MLM System
    Route::prefix('mlm')->name('mlm.')->group(function () {
        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/', fn() => comingSoonPage('สมาชิก MLM'))->name('index');
        });

        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', fn() => comingSoonPage('แผน MLM'))->name('index');
        });

        Route::prefix('genealogy')->name('genealogy.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ผังสายงาน'))->name('index');
        });

        Route::prefix('commissions')->name('commissions.')->group(function () {
            Route::get('/', fn() => comingSoonPage('คอมมิชชั่น MLM'))->name('index');
        });

        Route::prefix('product-pv')->name('product-pv.')->group(function () {
            Route::get('/', fn() => comingSoonPage('Product PV'))->name('index');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/dashboard', fn() => comingSoonPage('รายงาน MLM'))->name('dashboard');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ตั้งค่า MLM'))->name('index');
        });
    });

    // Marketing System
    Route::prefix('affiliates')->name('affiliates.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Affiliates'))->name('index');
        Route::get('/tree', fn() => comingSoonPage('โครงสร้างทีม'))->name('tree');
    });

    Route::prefix('retention')->name('retention.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ระบบรักษายอด'))->name('index');
    });

    Route::prefix('ranks')->name('ranks.')->group(function () {
        Route::get('/', fn() => comingSoonPage('จัดการระดับ Rank'))->name('index');

        Route::prefix('promotions')->name('promotions.')->group(function () {
            Route::get('/', fn() => comingSoonPage('การเลื่อนระดับ'))->name('index');
        });
    });

    Route::prefix('cashback')->name('cashback.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Cashback'))->name('index');
    });

    // HRM System
    Route::prefix('hrm')->name('hrm.')->group(function () {
        Route::get('/dashboard', fn() => comingSoonPage('แดชบอร์ด HR'))->name('dashboard');

        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/', fn() => comingSoonPage('พนักงาน'))->name('index');
        });

        Route::prefix('departments')->name('departments.')->group(function () {
            Route::get('/', fn() => comingSoonPage('แผนก'))->name('index');
        });

        Route::prefix('positions')->name('positions.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ตำแหน่ง'))->name('index');
        });

        Route::prefix('leave')->name('leave.')->group(function () {
            Route::get('/', fn() => comingSoonPage('การลา'))->name('index');
        });

        Route::prefix('payroll')->name('payroll.')->group(function () {
            Route::get('/', fn() => comingSoonPage('เงินเดือน'))->name('index');
        });
    });

    // Accounting
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/dashboard', fn() => comingSoonPage('แดชบอร์ดบัญชี'))->name('dashboard');

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ใบแจ้งหนี้'))->name('index');
        });

        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ค่าใช้จ่าย'))->name('index');
        });

        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ผู้ติดต่อ'))->name('index');
        });

        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', fn() => comingSoonPage('สินค้าบัญชี'))->name('index');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', fn() => comingSoonPage('รายงานบัญชี'))->name('index');
        });

        Route::prefix('flowaccount')->name('flowaccount.')->group(function () {
            Route::get('/', fn() => comingSoonPage('FlowAccount'))->name('index');
        });
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/create', fn() => comingSoonPage('ส่งการแจ้งเตือน'))->name('create');
        Route::get('/', fn() => comingSoonPage('ประวัติการแจ้งเตือน'))->name('index');
        Route::get('/statistics', fn() => comingSoonPage('สถิติการแจ้งเตือน'))->name('statistics');
    });

    Route::prefix('notification-templates')->name('notification-templates.')->group(function () {
        Route::get('/', fn() => comingSoonPage('เทมเพลตการแจ้งเตือน'))->name('index');
    });

    // Security
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ความปลอดภัย'))->name('index');
        Route::get('/threat-intelligence', fn() => comingSoonPage('Threat Intelligence'))->name('threat-intelligence');
        Route::get('/analytics', fn() => comingSoonPage('Security Analytics'))->name('analytics');
    });

    Route::prefix('otp')->name('otp.')->group(function () {
        Route::get('/settings', fn() => comingSoonPage('OTP Settings'))->name('settings');
    });

    // Pages & SEO
    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', fn() => comingSoonPage('จัดการเพจ'))->name('index');
    });

    Route::prefix('seo')->name('seo.')->group(function () {
        Route::get('/', fn() => comingSoonPage('SEO Settings'))->name('index');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Analytics'))->name('index');
    });

    // Theme & UI
    Route::prefix('themes')->name('themes.')->group(function () {
        Route::get('/builder', fn() => comingSoonPage('Theme Builder'))->name('builder');
    });

    Route::prefix('page-builder')->name('page-builder.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Page Builder'))->name('index');
    });

    Route::prefix('windows-ui')->name('windows-ui.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Windows UI'))->name('index');
    });

    Route::prefix('icons')->name('icons.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Icons'))->name('index');
    });

    Route::prefix('floating-tools')->name('floating-tools.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Floating Tools'))->name('index');
    });

    // Languages & Translation
    Route::prefix('translations')->name('translations.')->group(function () {
        Route::get('/', fn() => comingSoonPage('การแปล'))->name('index');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ตั้งค่าทั่วไป'))->name('index');
        Route::get('/languages', fn() => comingSoonPage('ตั้งค่าภาษา'))->name('languages');
        Route::get('/ocr', fn() => comingSoonPage('ตั้งค่า OCR'))->name('ocr');
    });

    Route::prefix('app-management')->name('app-management.')->group(function () {
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', fn() => comingSoonPage('ตั้งค่า Mobile App'))->name('index');
        });
    });

    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::get('/settings', fn() => comingSoonPage('ตั้งค่า 2FA'))->name('settings');
    });
});

// ============================================================================
// SELLER ROUTES - Placeholders
// ============================================================================

Route::middleware(['auth', 'role:seller'])->prefix('seller')->name('seller.')->group(function () {

    Route::get('/dashboard', fn() => comingSoonPage('แดชบอร์ด Seller'))->name('dashboard');

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', fn() => comingSoonPage('รายการสินค้า'))->name('index');
        Route::get('/create', fn() => comingSoonPage('เพิ่มสินค้า'))->name('create');
    });

    // POS
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/terminal', fn() => comingSoonPage('ขายสินค้า'))->name('terminal');
        Route::get('/transactions', fn() => comingSoonPage('รายการขาย'))->name('transactions');
        Route::get('/sessions', fn() => comingSoonPage('Session POS'))->name('sessions');
        Route::get('/settings', fn() => comingSoonPage('ตั้งค่า POS'))->name('settings');
    });

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', fn() => comingSoonPage('คำสั่งซื้อ'))->name('index');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', fn() => comingSoonPage('รายงานยอดขาย'))->name('sales');
    });

    // Wallet
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', fn() => comingSoonPage('กระเป๋าของฉัน'))->name('index');
        Route::get('/withdraw', fn() => comingSoonPage('ถอนเงิน'))->name('withdraw');
    });

    // Commissions
    Route::get('/commissions', fn() => comingSoonPage('คอมมิชชั่น Seller'))->name('commissions');

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Dashboard วิเคราะห์'))->name('index');
        Route::get('/ai-insights', fn() => comingSoonPage('AI Insights'))->name('ai-insights');
        Route::get('/segmentation', fn() => comingSoonPage('Customer Segments'))->name('segmentation');
        Route::get('/cohort', fn() => comingSoonPage('Cohort Analysis'))->name('cohort');
        Route::get('/products', fn() => comingSoonPage('Products Ranking'))->name('products');
        Route::get('/system-monitoring', fn() => comingSoonPage('System Monitoring'))->name('system-monitoring');
        Route::get('/settings', fn() => comingSoonPage('Analytics Settings'))->name('settings');
    });

    // Settings & Profile
    Route::get('/settings', fn() => comingSoonPage('ตั้งค่าร้าน'))->name('settings');
    Route::get('/profile', fn() => comingSoonPage('โปรไฟล์ Seller'))->name('profile');
});

// ============================================================================
// USER ROUTES - Placeholders
// ============================================================================

Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {

    Route::get('/dashboard', fn() => comingSoonPage('แดชบอร์ด User'))->name('dashboard');
    Route::get('/profile', fn() => comingSoonPage('โปรไฟล์'))->name('profile');

    // KYC
    Route::prefix('kyc')->name('kyc.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ยืนยันตัวตน KYC'))->name('index');
    });

    // Commissions
    Route::get('/commissions', fn() => comingSoonPage('คอมมิชชั่น'))->name('commissions');

    // Tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', fn() => comingSoonPage('Ticket Support'))->name('index');
    });

    // Wallet
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', fn() => comingSoonPage('กระเป๋าของฉัน'))->name('index');
        Route::get('/withdraw', fn() => comingSoonPage('ถอนเงิน'))->name('withdraw');
    });

    // Crypto Wallet
    Route::prefix('crypto-wallet')->name('crypto-wallet.')->group(function () {
        Route::get('/', fn() => comingSoonPage('กระเป๋าคริปโต'))->name('index');
    });

    // Investments
    Route::prefix('investments')->name('investments.')->group(function () {
        Route::get('/', fn() => comingSoonPage('การลงทุน ROI'))->name('index');
        Route::get('/plans', fn() => comingSoonPage('แผนการลงทุน'))->name('plans');
    });

    // Team
    Route::get('/referrals', fn() => comingSoonPage('ผู้แนะนำ'))->name('referrals');
    Route::get('/organization', fn() => comingSoonPage('ผังสายงาน'))->name('organization');

    // Retention
    Route::prefix('retention')->name('retention.')->group(function () {
        Route::get('/', fn() => comingSoonPage('สถานะพลังชีวิต'))->name('index');
    });

    // MLM Tools
    Route::prefix('mlm')->name('mlm.')->group(function () {
        Route::get('/income-simulator', fn() => comingSoonPage('จำลองรายได้'))->name('income-simulator');
    });

    // Themes
    Route::prefix('themes')->name('themes.')->group(function () {
        Route::get('/', fn() => comingSoonPage('ตั้งค่าธีม'))->name('index');
    });
});

// ============================================================================
// PUBLIC ROUTES - Placeholders
// ============================================================================

// Shop
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/', fn() => comingSoonPage('ช๊อปสินค้า'))->name('index');
});

// Hotels
Route::prefix('hotels')->name('hotels.')->group(function () {
    Route::get('/', fn() => comingSoonPage('จองโรงแรม'))->name('index');

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', fn() => comingSoonPage('การจองของฉัน'))->name('index');
    });
});

// Marketplace
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/', fn() => comingSoonPage('ตลาดบอท'))->name('index');
});

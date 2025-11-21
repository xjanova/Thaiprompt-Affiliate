<?php

use Illuminate\Support\Facades\Route;

/**
 * Software Sales System Routes
 *
 * ระบบการขายซอฟต์แวร์ที่ปรับแต่งได้
 *
 * Features:
 * - ระบบจัดการสินค้าซอฟต์แวร์ที่ปรับแต่งได้
 * - ระบบใบเสนอราคาแบบอัตโนมัติ
 * - ระบบคำนวณราคาพร้อมภาษี
 * - ระบบส่งใบเสนอราคาทางอีเมล
 * - ระบบผ่อนชำระ (Installment)
 * - ระบบแจ้งเตือนสำหรับ Admin
 */

/*
|--------------------------------------------------------------------------
| Customer Routes (ฝั่งลูกค้า)
|--------------------------------------------------------------------------
*/

// หน้าแสดงสินค้าซอฟต์แวร์
// ⚠️ E-commerce Critical: Software products ต้องถูก index โดย search engines
Route::match(['GET', 'HEAD'], '/software-products', [App\Http\Controllers\SoftwareProductController::class, 'index'])->name('software.products.index');
Route::match(['GET', 'HEAD'], '/software-products/{slug}', [App\Http\Controllers\SoftwareProductController::class, 'show'])->name('software.products.show');

// ระบบใบเสนอราคา (ต้อง login)
Route::middleware(['auth'])->group(function () {
    // สร้างใบเสนอราคา
    Route::post('/software-products/{product}/quotations', [App\Http\Controllers\QuotationController::class, 'store'])->name('quotations.store');

    // ดูใบเสนอราคาของตัวเอง
    Route::get('/my-quotations', [App\Http\Controllers\QuotationController::class, 'index'])->name('quotations.index');
    Route::get('/my-quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'show'])->name('quotations.show');

    // ยอมรับ/ปฏิเสธใบเสนอราคา
    Route::post('/my-quotations/{quotation}/accept', [App\Http\Controllers\QuotationController::class, 'accept'])->name('quotations.accept');
    Route::post('/my-quotations/{quotation}/reject', [App\Http\Controllers\QuotationController::class, 'reject'])->name('quotations.reject');

    // แปลงใบเสนอราคาเป็นคำสั่งซื้อ
    Route::post('/my-quotations/{quotation}/convert-to-order', [App\Http\Controllers\QuotationController::class, 'convertToOrder'])->name('quotations.convert');

    // ดูแผนผ่อนชำระของตัวเอง
    Route::get('/my-installments', [App\Http\Controllers\InstallmentController::class, 'index'])->name('installments.index');
    Route::get('/my-installments/{plan}', [App\Http\Controllers\InstallmentController::class, 'show'])->name('installments.show');

    // ชำระเงินงวด
    Route::post('/my-installments/{plan}/payments/{payment}/pay', [App\Http\Controllers\InstallmentController::class, 'pay'])->name('installments.pay');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (ฝั่ง Admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // จัดการหมวดหมู่สินค้า
    Route::resource('software-categories', App\Http\Controllers\Admin\SoftwareCategoryController::class);

    // จัดการสินค้าซอฟต์แวร์
    Route::resource('software-products', App\Http\Controllers\Admin\SoftwareProductManagementController::class);

    // จัดการออฟชั่นของสินค้า
    Route::resource('software-products.options', App\Http\Controllers\Admin\SoftwareProductOptionController::class);
    Route::resource('software-products.options.values', App\Http\Controllers\Admin\SoftwareProductOptionValueController::class);

    // จัดการใบเสนอราคา
    Route::resource('software-quotations', App\Http\Controllers\Admin\SoftwareQuotationManagementController::class);
    Route::post('software-quotations/{quotation}/send', [App\Http\Controllers\Admin\SoftwareQuotationManagementController::class, 'send'])->name('software-quotations.send');
    Route::post('software-quotations/{quotation}/approve', [App\Http\Controllers\Admin\SoftwareQuotationManagementController::class, 'approve'])->name('software-quotations.approve');
    Route::post('software-quotations/{quotation}/reject', [App\Http\Controllers\Admin\SoftwareQuotationManagementController::class, 'reject'])->name('software-quotations.reject');

    // จัดการแผนผ่อนชำระ
    Route::resource('installment-plans', App\Http\Controllers\Admin\InstallmentPlanController::class);
    Route::post('installment-plans/{plan}/payments/{payment}/mark-paid', [App\Http\Controllers\Admin\InstallmentPlanController::class, 'markPaymentAsPaid'])->name('installment-plans.payments.mark-paid');
    Route::post('installment-plans/{plan}/cancel', [App\Http\Controllers\Admin\InstallmentPlanController::class, 'cancel'])->name('installment-plans.cancel');

    // รายงานและสถิติ
    Route::get('software-sales/dashboard', [App\Http\Controllers\Admin\SoftwareSalesDashboardController::class, 'index'])->name('software-sales.dashboard');
    Route::get('software-sales/reports', [App\Http\Controllers\Admin\SoftwareSalesReportController::class, 'index'])->name('software-sales.reports');
});

/*
|--------------------------------------------------------------------------
| API Routes สำหรับ AJAX
|--------------------------------------------------------------------------
*/

Route::prefix('api')->middleware(['auth'])->group(function () {
    // คำนวณราคาแบบเรียลไทม์
    Route::post('/software-products/{product}/calculate-price', [App\Http\Controllers\Api\QuotationCalculatorController::class, 'calculate'])->name('api.calculate-price');

    // คำนวณแผนผ่อนชำระ
    Route::post('/calculate-installment', [App\Http\Controllers\Api\QuotationCalculatorController::class, 'calculateInstallment'])->name('api.calculate-installment');

    // ดึงออฟชั่นของสินค้า
    Route::get('/software-products/{product}/options', [App\Http\Controllers\Api\SoftwareProductController::class, 'getOptions'])->name('api.product-options');
});

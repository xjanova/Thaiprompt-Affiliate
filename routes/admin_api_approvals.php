<?php

use App\Http\Controllers\Api\Admin\Approvals\ApprovalsSummaryController;
use App\Http\Controllers\Api\Admin\Approvals\EkycApprovalsController;
use App\Http\Controllers\Api\Admin\Approvals\MlmCommissionsController;
use App\Http\Controllers\Api\Admin\Approvals\RiderApplicationsController;
use App\Http\Controllers\Api\Admin\Approvals\RiderJobsController;
use App\Http\Controllers\Api\Admin\Approvals\SellerApplicationsController;
use App\Http\Controllers\Api\Admin\Approvals\TicketsController;
use App\Http\Controllers\Api\Admin\Approvals\UserModerationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Mobile API — คิวอนุมัติ (Approvals)
|--------------------------------------------------------------------------
|
| ถูก require จากภายในกลุ่ม ['auth:sanctum', 'admin.api'] ของ routes/admin_api.php
| (prefix /api/admin ตั้งที่ bootstrap/app.php) — ทุก route ที่นี่ต้องมี admin token
|
| สัญญา JSON: docs/ADMIN_APP_API.md หัวข้อ "Approvals"
| ทุกการกระทำเรียก service/controller เดียวกับหลังบ้านเว็บ — ไม่มีกติกาธุรกิจใหม่ในไฟล์นี้
|
*/

Route::prefix('approvals')->name('api.admin.approvals.')->group(function () {

    // 🔔 ตัวเลขป้ายของทุกคิว (cache 20 วินาที)
    Route::get('/summary', [ApprovalsSummaryController::class, 'index'])->name('summary');

    // 🪪 AI eKYC ที่ AI ไม่มั่นใจ → แอดมินตรวจ
    Route::prefix('ekyc')->name('ekyc.')->group(function () {
        Route::get('/', [EkycApprovalsController::class, 'index'])->name('index');
        Route::get('/{kyc}', [EkycApprovalsController::class, 'show'])->whereNumber('kyc')->name('show');
        // รูปถอดรหัส — บันทึกการเปิดดูทุกครั้ง (PDPA) · throttle เดียวกับหน้าเว็บ
        Route::get('/{kyc}/image/{kind}', [EkycApprovalsController::class, 'image'])
            ->whereNumber('kyc')
            ->whereIn('kind', array_keys(EkycApprovalsController::IMAGE_KINDS))
            ->middleware('throttle:120,1,admin-kyc-image')
            ->name('image');
        Route::post('/{kyc}/approve', [EkycApprovalsController::class, 'approve'])->whereNumber('kyc')->name('approve');
        Route::post('/{kyc}/reject', [EkycApprovalsController::class, 'reject'])->whereNumber('kyc')->name('reject');
        Route::post('/{kyc}/request-retake', [EkycApprovalsController::class, 'requestRetake'])->whereNumber('kyc')->name('request-retake');
    });

    // 🏪 คำขอเปิดร้านค้า
    Route::prefix('seller-applications')->name('seller-applications.')->group(function () {
        Route::get('/', [SellerApplicationsController::class, 'index'])->name('index');
        Route::get('/{store}', [SellerApplicationsController::class, 'show'])->whereNumber('store')->name('show');
        Route::post('/{store}/approve', [SellerApplicationsController::class, 'approve'])->whereNumber('store')->name('approve');
        Route::post('/{store}/reject', [SellerApplicationsController::class, 'reject'])->whereNumber('store')->name('reject');
    });

    // 🛵 ใบสมัครไรเดอร์ + ตรวจเอกสารซ้ำ
    Route::prefix('riders')->name('riders.')->group(function () {
        Route::get('/', [RiderApplicationsController::class, 'index'])->name('index');
        Route::get('/{rider}', [RiderApplicationsController::class, 'show'])->whereNumber('rider')->name('show');
        Route::get('/{rider}/document/{type}', [RiderApplicationsController::class, 'document'])
            ->whereNumber('rider')
            ->whereIn('type', ['id_card', 'driver_license', 'vehicle_registration', 'profile'])
            ->name('document');
        Route::post('/{rider}/approve', [RiderApplicationsController::class, 'approve'])->whereNumber('rider')->name('approve');
        Route::post('/{rider}/reject', [RiderApplicationsController::class, 'reject'])->whereNumber('rider')->name('reject');
        Route::post('/{rider}/documents-reviewed', [RiderApplicationsController::class, 'documentsReviewed'])->whereNumber('rider')->name('documents-reviewed');
    });

    // 💸 งานไรเดอร์ที่รอตัดสิน (ร้องเรียน / เงินพัก / มอบหมายเอง) — เคลื่อนเงิน
    Route::prefix('rider-jobs')->name('rider-jobs.')->group(function () {
        Route::get('/', [RiderJobsController::class, 'index'])->name('index');
        Route::get('/{job}', [RiderJobsController::class, 'show'])->whereNumber('job')->name('show');
        Route::get('/{job}/handover-photo/{kind}', [RiderJobsController::class, 'handoverPhoto'])
            ->whereNumber('job')
            ->whereIn('kind', ['arrival', 'waited'])
            ->name('handover-photo');
        Route::post('/{job}/release', [RiderJobsController::class, 'release'])->whereNumber('job')->name('release');
        Route::post('/{job}/refund', [RiderJobsController::class, 'refund'])->whereNumber('job')->name('refund');
        Route::post('/{job}/reassign', [RiderJobsController::class, 'reassign'])->whereNumber('job')->name('reassign');
        Route::post('/{job}/redispatch', [RiderJobsController::class, 'redispatch'])->whereNumber('job')->name('redispatch');
    });

    // 🎫 ตั๋วซัพพอร์ต
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketsController::class, 'index'])->name('index');
        Route::get('/{ticket}', [TicketsController::class, 'show'])->whereNumber('ticket')->name('show');
        Route::post('/{ticket}/reply', [TicketsController::class, 'reply'])->whereNumber('ticket')->name('reply');
        Route::post('/{ticket}/status', [TicketsController::class, 'status'])->whereNumber('ticket')->name('status');
    });

    // 💰 คอมมิชชัน MLM — เคลื่อนเงิน
    Route::prefix('mlm-commissions')->name('mlm-commissions.')->group(function () {
        Route::get('/', [MlmCommissionsController::class, 'index'])->name('index');
        Route::post('/{commission}/approve', [MlmCommissionsController::class, 'approve'])->whereNumber('commission')->name('approve');
        Route::post('/{commission}/pay', [MlmCommissionsController::class, 'pay'])->whereNumber('commission')->name('pay');
    });
});

// 🔒 ผู้ใช้: ระงับ / ยกเลิกระงับ / รีเซ็ต PIN กระเป๋าเงิน (POST เท่านั้น — ไม่ชนกับ GET users/{user} ใน admin_api.php)
Route::prefix('users')->name('api.admin.users.')->group(function () {
    Route::post('/{user}/suspend', [UserModerationController::class, 'suspend'])->whereNumber('user')->name('suspend');
    Route::post('/{user}/unsuspend', [UserModerationController::class, 'unsuspend'])->whereNumber('user')->name('unsuspend');
    Route::post('/{user}/reset-wallet-pin', [UserModerationController::class, 'resetWalletPin'])->whereNumber('user')->name('reset-wallet-pin');
});

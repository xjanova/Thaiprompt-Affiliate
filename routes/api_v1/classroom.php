<?php

/*
 * 🎓 ห้องเรียน (2026-10-13) — อบรมผู้ขาย / ไรเดอร์ / ผู้ซื้อ (ไม่บังคับ) → ป้าย "ผ่านการอบรม"
 *
 * ถูก require จาก routes/api.php ภายในกลุ่ม prefix v1 + auth:sanctum
 * ชื่อ route ขึ้นต้นด้วย api.v1.classroom. · throttle ทุกเส้นมี prefix ของตัวเอง
 * heartbeat ส่งทุก 10 วิ (6 ครั้ง/นาที) → เผื่อ 2 บทเปิดพร้อมกัน + หยุด/เล่นต่อ = 30 ครั้ง/นาที
 */

use App\Http\Controllers\Api\V1\ClassroomApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('classroom')->name('api.v1.classroom.')->controller(ClassroomApiController::class)->group(function () {
    Route::get('/courses', 'courses')
        ->middleware('throttle:60,1,api-classroom-read')
        ->name('courses.index');

    Route::get('/courses/{id}', 'course')
        ->whereNumber('id')
        ->middleware('throttle:60,1,api-classroom-read')
        ->name('courses.show');

    Route::post('/lessons/{id}/progress', 'progress')
        ->whereNumber('id')
        ->middleware('throttle:30,1,api-classroom-progress')
        ->name('lessons.progress');

    Route::post('/courses/{id}/quiz/start', 'startQuiz')
        ->whereNumber('id')
        ->middleware('throttle:10,1,api-classroom-quiz-start')
        ->name('quiz.start');

    Route::post('/attempts/{id}/submit', 'submit')
        ->whereNumber('id')
        ->middleware('throttle:10,1,api-classroom-quiz-submit')
        ->name('attempts.submit');
});

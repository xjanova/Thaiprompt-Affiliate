<?php

/*
 * 🎓 ห้องเรียนบนเว็บ (2026-10-13) — /user/classroom
 *
 * ถูก require จากท้าย routes/user.php (อยู่ใต้ auth + role สมาชิก, prefix user, ชื่อ user.)
 * ชื่อ route: user.classroom.* · ใช้ service ชุดเดียวกับ API แอป (/api/v1/classroom/*)
 */

use App\Http\Controllers\User\ClassroomController;
use Illuminate\Support\Facades\Route;

Route::prefix('classroom')->name('classroom.')->controller(ClassroomController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/courses/{course}', 'course')->whereNumber('course')->name('course');
    Route::get('/lessons/{lesson}', 'lesson')->whereNumber('lesson')->name('lesson');
    Route::post('/lessons/{lesson}/progress', 'progress')
        ->whereNumber('lesson')
        ->middleware('throttle:30,1,api-classroom-progress')
        ->name('lesson.progress');
    Route::post('/courses/{course}/quiz/start', 'startQuiz')
        ->whereNumber('course')
        ->middleware('throttle:10,1,web-classroom-quiz-start')
        ->name('quiz.start');
    Route::get('/attempts/{attempt}', 'attempt')->whereNumber('attempt')->name('attempt');
    Route::post('/attempts/{attempt}/submit', 'submit')
        ->whereNumber('attempt')
        ->middleware('throttle:10,1,web-classroom-quiz-submit')
        ->name('attempt.submit');
    Route::get('/attempts/{attempt}/result', 'result')->whereNumber('attempt')->name('result');
});

<?php

/*
 * 🎓 หลังบ้านห้องเรียน (2026-10-13) — /admin/classroom
 *
 * ถูก require จากท้าย routes/admin.php (อยู่ใต้ auth + role:admin,super_admin, prefix admin, ชื่อ admin.)
 * ชื่อ route: admin.classroom.* · เมนูอยู่ที่ config/menus.php (id: classroom)
 */

use App\Http\Controllers\Admin\ClassroomAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('classroom')->name('classroom.')->controller(ClassroomAdminController::class)->group(function () {
    Route::get('/', 'index')->name('index');

    // คอร์ส + บทเรียน
    Route::get('/courses/{course}/edit', 'editCourse')->name('courses.edit');
    Route::put('/courses/{course}', 'updateCourse')->name('courses.update');
    Route::post('/courses/{course}/lessons', 'storeLesson')->name('lessons.store');
    Route::put('/lessons/{lesson}', 'updateLesson')->name('lessons.update');
    Route::post('/lessons/{lesson}/toggle', 'toggleLesson')->name('lessons.toggle');
    Route::post('/lessons/{lesson}/move', 'moveLesson')->name('lessons.move');
    Route::delete('/lessons/{lesson}', 'destroyLesson')->name('lessons.destroy');

    // คลังข้อสอบ
    Route::get('/courses/{course}/questions', 'questions')->name('questions.index');
    Route::get('/courses/{course}/questions/create', 'createQuestion')->name('questions.create');
    Route::post('/courses/{course}/questions', 'storeQuestion')->name('questions.store');
    Route::get('/questions/{question}/edit', 'editQuestion')->name('questions.edit');
    Route::put('/questions/{question}', 'updateQuestion')->name('questions.update');
    Route::post('/questions/{question}/toggle', 'toggleQuestion')->name('questions.toggle');
    Route::delete('/questions/{question}', 'destroyQuestion')->name('questions.destroy');

    // สถิติ
    Route::get('/courses/{course}/stats', 'stats')->name('stats');
});

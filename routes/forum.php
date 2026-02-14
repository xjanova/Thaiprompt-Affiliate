<?php

/**
 * Forum Routes
 *
 * เส้นทางสำหรับระบบฟอรั่ม Community
 * ประกอบด้วย:
 * - หน้าหลักฟอรั่ม
 * - หมวดหมู่
 * - กระทู้ (สร้าง, แก้ไข, ลบ, ไลค์)
 * - คอมเมนต์ (สร้าง, แก้ไข, ลบ, ไลค์)
 * - รายงาน
 */

use App\Http\Controllers\Forum\ForumCategoryController;
use App\Http\Controllers\Forum\ForumController;
use App\Http\Controllers\Forum\ForumPostController;
use App\Http\Controllers\Forum\ForumReportController;
use App\Http\Controllers\Forum\ForumThreadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Forum Routes (ไม่ต้อง login)
|--------------------------------------------------------------------------
*/

Route::prefix('forum')->name('forum.')->group(function () {

    // หน้าหลักฟอรั่ม
    Route::get('/', [ForumController::class, 'index'])->name('index');

    // ค้นหา
    Route::get('/search', [ForumController::class, 'search'])->name('search');

    // หมวดหมู่
    Route::get('/category/{slug}', [ForumCategoryController::class, 'show'])->name('category');

    // ดูกระทู้
    Route::get('/thread/{slug}', [ForumThreadController::class, 'show'])->name('thread.show');

});

/*
|--------------------------------------------------------------------------
| Authenticated Forum Routes (ต้อง login)
|--------------------------------------------------------------------------
*/

Route::prefix('forum')->name('forum.')->middleware(['auth', 'verified'])->group(function () {

    // สร้างกระทู้
    Route::get('/thread/create', [ForumThreadController::class, 'create'])->name('thread.create');
    Route::post('/thread', [ForumThreadController::class, 'store'])->name('thread.store');

    // แก้ไข/ลบกระทู้
    Route::get('/thread/{slug}/edit', [ForumThreadController::class, 'edit'])->name('thread.edit');
    Route::put('/thread/{slug}', [ForumThreadController::class, 'update'])->name('thread.update');
    Route::delete('/thread/{slug}', [ForumThreadController::class, 'destroy'])->name('thread.destroy');

    // ไลค์กระทู้
    Route::post('/thread/{id}/like', [ForumThreadController::class, 'toggleLike'])->name('thread.like');

    // คอมเมนต์
    Route::post('/thread/{threadId}/post', [ForumPostController::class, 'store'])->name('post.store');
    Route::put('/post/{id}', [ForumPostController::class, 'update'])->name('post.update');
    Route::delete('/post/{id}', [ForumPostController::class, 'destroy'])->name('post.destroy');

    // ไลค์คอมเมนต์
    Route::post('/post/{id}/like', [ForumPostController::class, 'toggleLike'])->name('post.like');

    // ตั้ง Best Answer
    Route::post('/post/{id}/best-answer', [ForumPostController::class, 'markBestAnswer'])->name('post.best-answer');

    // รายงาน
    Route::post('/thread/{threadId}/report', [ForumReportController::class, 'reportThread'])->name('thread.report');
    Route::post('/post/{postId}/report', [ForumReportController::class, 'reportPost'])->name('post.report');

});

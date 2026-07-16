<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

/**
 * User Guide Controller สำหรับ User Dashboard
 *
 * 🗑️ (2026-07-16) owner สั่งลบหน้าคู่มือ V3 ของฝั่ง user ทิ้ง (เนื้อหาเก่า
 *    ธีม arrow-x ไม่แปลงเป็น V4). คง route `user.user-guide.index` ไว้ให้
 *    resolve ได้ (navbar arrow-x legacy ยังอ้างถึง — ถ้าลบ route จะทำให้
 *    หน้าฟีเจอร์ที่ยังเป็น arrow-x 500) แต่ redirect ไปแดชบอร์ดแทน
 */
class UserGuideController extends Controller
{
    /**
     * คู่มือ V3 ถูกลบแล้ว → พาไปแดชบอร์ด
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        return redirect()->route('user.dashboard');
    }
}

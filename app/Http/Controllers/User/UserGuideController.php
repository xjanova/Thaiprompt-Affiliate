<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

/**
 * User Guide Controller สำหรับ User Dashboard
 *
 * แสดงหน้าคู่มือการใช้งานสำหรับผู้ใช้ทั่วไป
 *
 * @author TP-Affiliate Development Team
 */
class UserGuideController extends Controller
{
    /**
     * แสดงหน้าคู่มือการใช้งาน (V3)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('user.user-guide-v3');
    }
}

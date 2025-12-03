<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * User Guide Controller สำหรับ Seller Dashboard
 *
 * แสดงหน้าคู่มือการใช้งานสำหรับผู้ขาย
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
        return view('seller.user-guide-v3');
    }
}

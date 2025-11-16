<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserGuideController extends Controller
{
    /**
     * แสดงหน้าคู่มือการใช้งาน (V3)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.user-guide-v3');
    }
}

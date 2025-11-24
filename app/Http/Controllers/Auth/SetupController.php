<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

/**
 * ตัวควบคุมสำหรับการติดตั้งระบบครั้งแรก
 *
 * ใช้สำหรับสร้างบัญชี Super Admin ครั้งแรกเมื่อติดตั้งระบบ
 */
class SetupController extends Controller
{
    /**
     * แสดงหน้าติดตั้งระบบ
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        // ตรวจสอบว่ามี super admin อยู่แล้วหรือไม่
        if ($this->isSetupCompleted()) {
            return redirect()->route('home')->with('info', 'ระบบได้ติดตั้งเรียบร้อยแล้ว');
        }

        return view('auth.setup');
    }

    /**
     * บันทึกข้อมูล Super Admin และเสร็จสิ้นการติดตั้ง
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ตรวจสอบว่ามี super admin อยู่แล้วหรือไม่
        if ($this->isSetupCompleted()) {
            return redirect()->route('home')->with('error', 'ระบบได้ติดตั้งเรียบร้อยแล้ว');
        }

        // ตรวจสอบข้อมูลที่ส่งมา
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // สร้างบัญชี Super Admin
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'super_admin',
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ]);

            // สร้างไฟล์ flag เพื่อบอกว่าติดตั้งเสร็จแล้ว
            $this->createSetupCompletedFlag();

            // เข้าสู่ระบบอัตโนมัติ
            auth()->login($user);

            return redirect()->route('admin.dashboard')
                ->with('success', 'ติดตั้งระบบเรียบร้อยแล้ว! ยินดีต้อนรับสู่ TP-Affiliate');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการสร้างบัญชี: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * ตรวจสอบว่าติดตั้งระบบเรียบร้อยแล้วหรือไม่
     *
     * @return bool
     */
    protected function isSetupCompleted(): bool
    {
        // ตรวจสอบว่ามีไฟล์ flag หรือไม่
        if (File::exists(storage_path('app/.setup_completed'))) {
            return true;
        }

        // ตรวจสอบว่ามี super admin หรือไม่
        return User::where('is_super_admin', true)->exists() ||
               User::where('role', 'super_admin')->exists();
    }

    /**
     * สร้างไฟล์ flag เพื่อบอกว่าติดตั้งเสร็จแล้ว
     *
     * @return void
     */
    protected function createSetupCompletedFlag(): void
    {
        $storagePath = storage_path('app');

        // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        // สร้างไฟล์ flag
        File::put(storage_path('app/.setup_completed'), now()->toString());
    }
}

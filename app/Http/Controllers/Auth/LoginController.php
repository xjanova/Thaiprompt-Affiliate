<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * แสดงหน้า Login Form
     */
    public function showLoginForm()
    {
        // ถ้า user login แล้ว redirect ไปหน้าหลัก
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    /**
     * ประมวลผล Login
     */
    public function login(Request $request)
    {
        try {
            // Validate input
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ], [
                'email.required' => 'กรุณากรอกอีเมล',
                'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
                'password.required' => 'กรุณากรอกรหัสผ่าน',
                'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร',
            ]);

            // Log login attempt
            Log::info('Login attempt', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ลองทำการ login
            $remember = $request->filled('remember');

            if (Auth::attempt($credentials, $remember)) {
                // Login สำเร็จ
                $request->session()->regenerate();

                $user = Auth::user();

                Log::info('Login successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                // Redirect ตาม role หรือไปหน้าที่ต้องการ
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'เข้าสู่ระบบสำเร็จ',
                        'user' => $user,
                    ]);
                }

                return redirect()->intended('/');
            }

            // Login ไม่สำเร็จ
            Log::warning('Login failed - Invalid credentials', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                throw ValidationException::withMessages([
                    'email' => ['ข้อมูลการเข้าสู่ระบบไม่ถูกต้อง'],
                ]);
            }

            return back()->withErrors([
                'email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
            ])->onlyInput('email');

        } catch (ValidationException $e) {
            // Validation errors
            throw $e;

        } catch (\Exception $e) {
            // ข้อผิดพลาดทั่วไป
            Log::error('Login error', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            return back()->withErrors([
                'email' => 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง',
            ])->onlyInput('email');
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            Log::info('User logout', [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'ip' => $request->ip(),
            ]);

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ออกจากระบบสำเร็จ',
                ]);
            }

            return redirect('/');

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด',
                ], 500);
            }

            return redirect('/');
        }
    }
}

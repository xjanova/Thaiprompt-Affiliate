{{--
    หน้า Documentation: ระบบการสมัครสมาชิก (Registration Flow)

    แสดง Flow การทำงานของระบบการสมัครสมาชิกทั้งหมด
    รวมถึง LINE OAuth, MLM Member Creation, และ Middleware Protection

    @author Claude AI
    @version 1.0
--}}

@extends('layouts.admin')

@section('title', 'Documentation: ระบบการสมัครสมาชิก')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center mb-4">
                <a href="{{ route('admin.dashboard') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-user-plus text-purple-500 mr-3"></i>
                    Documentation: ระบบการสมัครสมาชิก
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                เอกสารอธิบาย Flow การทำงานของระบบการสมัครสมาชิกทั้งหมด รวมถึง LINE OAuth, MLM Member Creation, และ Middleware Protection
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-sm font-medium">
                    <i class="fas fa-code-branch mr-1"></i> Version 3.0
                </span>
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full text-sm font-medium">
                    <i class="fas fa-check-circle mr-1"></i> ใช้งานได้
                </span>
                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-sm font-medium">
                    <i class="fab fa-line mr-1"></i> LINE OAuth
                </span>
            </div>
        </div>

        {{-- Table of Contents --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-list-ol text-blue-500 mr-2"></i>
                สารบัญ
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="#overview" class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="w-8 h-8 bg-purple-500 text-white rounded-lg flex items-center justify-center mr-3">1</div>
                    <span class="text-gray-700 dark:text-gray-300">ภาพรวมระบบ</span>
                </a>
                <a href="#routes" class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="w-8 h-8 bg-blue-500 text-white rounded-lg flex items-center justify-center mr-3">2</div>
                    <span class="text-gray-700 dark:text-gray-300">Routes & URLs</span>
                </a>
                <a href="#standard-flow" class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="w-8 h-8 bg-green-500 text-white rounded-lg flex items-center justify-center mr-3">3</div>
                    <span class="text-gray-700 dark:text-gray-300">Standard Registration</span>
                </a>
                <a href="#line-flow" class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="w-8 h-8 bg-green-600 text-white rounded-lg flex items-center justify-center mr-3">4</div>
                    <span class="text-gray-700 dark:text-gray-300">LINE OAuth Flow</span>
                </a>
                <a href="#mlm-flow" class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="w-8 h-8 bg-orange-500 text-white rounded-lg flex items-center justify-center mr-3">5</div>
                    <span class="text-gray-700 dark:text-gray-300">MLM Member Creation</span>
                </a>
                <a href="#models" class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="w-8 h-8 bg-red-500 text-white rounded-lg flex items-center justify-center mr-3">6</div>
                    <span class="text-gray-700 dark:text-gray-300">Models & Database</span>
                </a>
                <a href="#middleware" class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="w-8 h-8 bg-indigo-500 text-white rounded-lg flex items-center justify-center mr-3">7</div>
                    <span class="text-gray-700 dark:text-gray-300">Middleware Protection</span>
                </a>
                <a href="#fixes" class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="w-8 h-8 bg-yellow-500 text-white rounded-lg flex items-center justify-center mr-3">8</div>
                    <span class="text-gray-700 dark:text-gray-300">การแก้ไขล่าสุด</span>
                </a>
            </div>
        </div>

        {{-- Section 1: Overview --}}
        <div id="overview" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="w-10 h-10 bg-purple-500 text-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-eye"></i>
                </div>
                1. ภาพรวมระบบการสมัครสมาชิก
            </h2>

            <div class="prose dark:prose-invert max-w-none">
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    ระบบการสมัครสมาชิกของ TP-Affiliate รองรับการสมัครหลายรูปแบบ โดยมีการเชื่อมต่อกับระบบ MLM (Multi-Level Marketing) อัตโนมัติ
                </p>
            </div>

            {{-- Flow Diagram --}}
            <div class="mt-6 p-6 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-xl">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-project-diagram mr-2"></i>
                    Architecture Overview
                </h3>

                <div class="flex flex-wrap justify-center items-center gap-4">
                    {{-- User --}}
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 bg-blue-500 text-white rounded-full flex items-center justify-center text-3xl shadow-lg">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">ผู้ใช้</span>
                    </div>

                    <div class="text-3xl text-gray-400">→</div>

                    {{-- Registration Form --}}
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 bg-purple-500 text-white rounded-xl flex items-center justify-center text-3xl shadow-lg">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <span class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">ฟอร์มสมัคร</span>
                    </div>

                    <div class="text-3xl text-gray-400">→</div>

                    {{-- Controller --}}
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 bg-green-500 text-white rounded-xl flex items-center justify-center text-3xl shadow-lg">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <span class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Controller</span>
                    </div>

                    <div class="text-3xl text-gray-400">→</div>

                    {{-- Database --}}
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 bg-orange-500 text-white rounded-xl flex items-center justify-center text-3xl shadow-lg">
                            <i class="fas fa-database"></i>
                        </div>
                        <span class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Database</span>
                    </div>

                    <div class="text-3xl text-gray-400">→</div>

                    {{-- Dashboard --}}
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 bg-indigo-500 text-white rounded-full flex items-center justify-center text-3xl shadow-lg">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <span class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Dashboard</span>
                    </div>
                </div>
            </div>

            {{-- Registration Types --}}
            <div class="mt-8 grid md:grid-cols-2 gap-6">
                <div class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl border-2 border-blue-200 dark:border-blue-700">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-500 text-white rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-envelope text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-800 dark:text-white">Standard Registration</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">สมัครด้วย Email + Password</p>
                        </div>
                    </div>
                    <ul class="space-y-2 text-gray-600 dark:text-gray-300 text-sm">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>กรอก ชื่อ, อีเมล, รหัสผ่าน</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>ใส่รหัสแนะนำ (optional)</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Cloudflare Turnstile CAPTCHA</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>สร้าง User + MlmMember อัตโนมัติ</li>
                    </ul>
                </div>

                <div class="p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-xl border-2 border-green-200 dark:border-green-700">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-500 text-white rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-800 dark:text-white">LINE OAuth Registration</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">สมัครผ่าน LINE Login</p>
                        </div>
                    </div>
                    <ul class="space-y-2 text-gray-600 dark:text-gray-300 text-sm">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>เข้าสู่ระบบด้วย LINE Account</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>ดึงข้อมูลจาก LINE Profile</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>กรอกข้อมูลเพิ่มเติม (Email, Password)</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>เชื่อมต่อ LINE OA อัตโนมัติ</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Section 2: Routes --}}
        <div id="routes" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="w-10 h-10 bg-blue-500 text-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-route"></i>
                </div>
                2. Routes & URLs
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Method</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">URL</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Route Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Controller</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">คำอธิบาย</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded font-mono text-xs">GET</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-blue-600 dark:text-blue-400">/register</td>
                            <td class="px-4 py-3 font-mono text-purple-600 dark:text-purple-400">register</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">RegisterController@showRegistrationForm</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">แสดงฟอร์มสมัครสมาชิก</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded font-mono text-xs">POST</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-blue-600 dark:text-blue-400">/register</td>
                            <td class="px-4 py-3 font-mono text-purple-600 dark:text-purple-400">register</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">RegisterController@register</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">ประมวลผลการสมัคร</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 bg-green-50/50 dark:bg-green-900/10">
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded font-mono text-xs">GET</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-blue-600 dark:text-blue-400">/auth/line</td>
                            <td class="px-4 py-3 font-mono text-purple-600 dark:text-purple-400">line.login</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">LineLoginController@redirect</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Redirect ไปหน้า LINE Login</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 bg-green-50/50 dark:bg-green-900/10">
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded font-mono text-xs">GET</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-blue-600 dark:text-blue-400">/auth/line/callback</td>
                            <td class="px-4 py-3 font-mono text-purple-600 dark:text-purple-400">line.callback</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">LineLoginController@callback</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">รับ callback จาก LINE</td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded font-mono text-xs">GET</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-blue-600 dark:text-blue-400">/login</td>
                            <td class="px-4 py-3 font-mono text-purple-600 dark:text-purple-400">login</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">LoginController@showLoginForm</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">แสดงฟอร์มเข้าสู่ระบบ</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-700">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-yellow-500 text-xl mr-3 mt-1"></i>
                    <div>
                        <h4 class="font-semibold text-gray-800 dark:text-white">Middleware ที่ใช้</h4>
                        <ul class="mt-2 text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">guest</code> - เฉพาะผู้ที่ยังไม่ได้ login</li>
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">turnstile:register</code> - Cloudflare CAPTCHA verification</li>
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">throttle.login</code> - Rate limiting</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Standard Registration Flow --}}
        <div id="standard-flow" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="w-10 h-10 bg-green-500 text-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                3. Standard Registration Flow (Email + Password)
            </h2>

            {{-- Step by Step --}}
            <div class="relative">
                {{-- Vertical Line --}}
                <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-500 via-green-500 to-purple-500 hidden md:block"></div>

                {{-- Step 1 --}}
                <div class="relative pl-0 md:pl-16 pb-8">
                    <div class="absolute left-0 w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-lg hidden md:flex">1</div>
                    <div class="bg-blue-50 dark:bg-blue-900/30 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                            <i class="fas fa-globe text-blue-500 mr-2"></i>
                            ผู้ใช้เข้าหน้า /register
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            ผู้ใช้เข้าถึงหน้าสมัครสมาชิกผ่าน URL <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">/register</code>
                            หรือคลิกลิงก์ "สมัครสมาชิก" จากหน้า Login
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">URL รูปแบบที่รองรับ:</p>
                            <ul class="text-sm font-mono space-y-1">
                                <li class="text-blue-600 dark:text-blue-400">/register</li>
                                <li class="text-blue-600 dark:text-blue-400">/register?ref=ABC123 <span class="text-gray-500">(มีรหัสแนะนำ)</span></li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="relative pl-0 md:pl-16 pb-8">
                    <div class="absolute left-0 w-12 h-12 bg-purple-500 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-lg hidden md:flex">2</div>
                    <div class="bg-purple-50 dark:bg-purple-900/30 rounded-xl p-6 border-2 border-purple-200 dark:border-purple-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                            <i class="fas fa-file-alt text-purple-500 mr-2"></i>
                            แสดงฟอร์มสมัครสมาชิก
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">RegisterController@showRegistrationForm</code>
                            โหลด View และส่งข้อมูลไปแสดง
                        </p>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">ข้อมูลที่ส่งไป View:</h4>
                                <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
                                    <li><i class="fas fa-code text-purple-500 mr-1"></i> $referralCode - รหัสแนะนำ</li>
                                    <li><i class="fas fa-user text-purple-500 mr-1"></i> $defaultSponsorName - ชื่อผู้แนะนำเริ่มต้น</li>
                                    <li><i class="fab fa-line text-green-500 mr-1"></i> $lineProfile - ข้อมูล LINE (ถ้ามี)</li>
                                    <li><i class="fas fa-gift text-yellow-500 mr-1"></i> $signupRewards - รางวัลการสมัคร</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">ฟิลด์ในฟอร์ม:</h4>
                                <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
                                    <li><i class="fas fa-user text-blue-500 mr-1"></i> name - ชื่อ-นามสกุล</li>
                                    <li><i class="fas fa-envelope text-blue-500 mr-1"></i> email - อีเมล</li>
                                    <li><i class="fas fa-lock text-blue-500 mr-1"></i> password - รหัสผ่าน</li>
                                    <li><i class="fas fa-lock text-blue-500 mr-1"></i> password_confirmation</li>
                                    <li><i class="fas fa-gift text-green-500 mr-1"></i> referral_code (optional)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="relative pl-0 md:pl-16 pb-8">
                    <div class="absolute left-0 w-12 h-12 bg-yellow-500 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-lg hidden md:flex">3</div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-xl p-6 border-2 border-yellow-200 dark:border-yellow-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                            <i class="fas fa-shield-alt text-yellow-500 mr-2"></i>
                            Validation & CAPTCHA
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            ผู้ใช้กรอกข้อมูล → กด Submit → ระบบตรวจสอบ CAPTCHA และ Validate ข้อมูล
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Validation Rules:</h4>
                            <pre class="text-xs bg-gray-900 text-green-400 p-3 rounded-lg overflow-x-auto">
$rules = [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    'password' => ['required', 'confirmed', Password::defaults()],
    'referral_code' => ['nullable', 'string', 'exists:mlm_members,member_code'],
];</pre>
                        </div>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="relative pl-0 md:pl-16 pb-8">
                    <div class="absolute left-0 w-12 h-12 bg-green-500 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-lg hidden md:flex">4</div>
                    <div class="bg-green-50 dark:bg-green-900/30 rounded-xl p-6 border-2 border-green-200 dark:border-green-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                            <i class="fas fa-user-plus text-green-500 mr-2"></i>
                            สร้าง User Account
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            ระบบสร้าง record ในตาราง <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">users</code>
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <pre class="text-xs bg-gray-900 text-green-400 p-3 rounded-lg overflow-x-auto">
$user = User::create([
    'name' => $validated['name'],
    'email' => $validated['email'],
    'password' => Hash::make($validated['password']),
    'role' => 'user',
    'menu_theme_preference' => 'arrow-x',
    'arrow_x_mode' => 'classic',
    // ... theme settings
]);

// ⚠️ CRITICAL: ตั้งค่า role หลัง create
// เพราะ role อยู่ใน $guarded
$user->role = 'user';
$user->save();</pre>
                        </div>
                        <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700">
                            <p class="text-sm text-red-700 dark:text-red-300">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>สำคัญ:</strong> ต้องตั้งค่า role หลัง create() เพราะ role อยู่ใน $guarded
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Step 5 --}}
                <div class="relative pl-0 md:pl-16 pb-8">
                    <div class="absolute left-0 w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-lg hidden md:flex">5</div>
                    <div class="bg-orange-50 dark:bg-orange-900/30 rounded-xl p-6 border-2 border-orange-200 dark:border-orange-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                            <i class="fas fa-sitemap text-orange-500 mr-2"></i>
                            สร้าง MLM Member
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            ระบบสร้าง record ในตาราง <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">mlm_members</code>
                            และจัดวางใน Tree Structure
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">ข้อมูลที่สร้าง:</p>
                            <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
                                <li><i class="fas fa-id-badge text-orange-500 mr-1"></i> member_code - รหัสสมาชิกอัตโนมัติ</li>
                                <li><i class="fas fa-link text-orange-500 mr-1"></i> original_sponsor_id - ผู้แนะนำตรง</li>
                                <li><i class="fas fa-project-diagram text-orange-500 mr-1"></i> unilevel_sponsor_id - parent ใน Unilevel</li>
                                <li><i class="fas fa-code-branch text-orange-500 mr-1"></i> binary_sponsor_id - parent ใน Binary</li>
                                <li><i class="fas fa-crown text-orange-500 mr-1"></i> mlm_plan_id - แผนคอมมิชชัน</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Step 6 --}}
                <div class="relative pl-0 md:pl-16">
                    <div class="absolute left-0 w-12 h-12 bg-indigo-500 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-lg hidden md:flex">6</div>
                    <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-xl p-6 border-2 border-indigo-200 dark:border-indigo-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                            <i class="fas fa-sign-in-alt text-indigo-500 mr-2"></i>
                            Login & Redirect
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            ระบบ Login ให้ผู้ใช้อัตโนมัติและ Redirect ไปหน้า User Dashboard
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <pre class="text-xs bg-gray-900 text-green-400 p-3 rounded-lg overflow-x-auto">
// Login user อัตโนมัติ
Auth::login($user);

// Redirect ไป User Dashboard
return redirect()->route('user.home')
    ->with('success', 'ลงทะเบียนสำเร็จ! ยินดีต้อนรับสู่ระบบ MLM');</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 4: LINE OAuth Flow --}}
        <div id="line-flow" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="w-10 h-10 bg-green-600 text-white rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                </div>
                4. LINE OAuth Registration Flow
            </h2>

            {{-- LINE Flow Diagram --}}
            <div class="mb-8 p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl">
                <div class="flex flex-wrap justify-center items-center gap-3 text-sm">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-green-500 text-white rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-user text-2xl"></i>
                        </div>
                        <span class="mt-2 text-gray-700 dark:text-gray-300 font-medium">User</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 text-xl"></i>
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-green-600 text-white rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755z"/>
                            </svg>
                        </div>
                        <span class="mt-2 text-gray-700 dark:text-gray-300 font-medium">LINE Login</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 text-xl"></i>
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-blue-500 text-white rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-exchange-alt text-2xl"></i>
                        </div>
                        <span class="mt-2 text-gray-700 dark:text-gray-300 font-medium">Callback</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 text-xl"></i>
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-purple-500 text-white rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-file-alt text-2xl"></i>
                        </div>
                        <span class="mt-2 text-gray-700 dark:text-gray-300 font-medium">Register Form</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 text-xl"></i>
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-indigo-500 text-white rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-check text-2xl"></i>
                        </div>
                        <span class="mt-2 text-gray-700 dark:text-gray-300 font-medium">Complete</span>
                    </div>
                </div>
            </div>

            {{-- Two scenarios --}}
            <div class="grid md:grid-cols-2 gap-6">
                {{-- Existing User --}}
                <div class="p-6 bg-blue-50 dark:bg-blue-900/30 rounded-xl border-2 border-blue-200 dark:border-blue-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                        <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user-check"></i>
                        </div>
                        กรณีที่ 1: มี Account อยู่แล้ว
                    </h3>
                    <ol class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3">1</span>
                            <span>User กด "เข้าสู่ระบบด้วย LINE"</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3">2</span>
                            <span>Redirect ไป LINE Login Page</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3">3</span>
                            <span>User ยืนยันตัวตนที่ LINE</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3">4</span>
                            <span>LINE Callback → พบ User ในระบบ</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-3">✓</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">Login สำเร็จ → ไป Dashboard</span>
                        </li>
                    </ol>
                </div>

                {{-- New User --}}
                <div class="p-6 bg-green-50 dark:bg-green-900/30 rounded-xl border-2 border-green-200 dark:border-green-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                        <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        กรณีที่ 2: ยังไม่มี Account
                    </h3>
                    <ol class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-3">1</span>
                            <span>User กด "สมัครด้วย LINE"</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-3">2</span>
                            <span>Redirect ไป LINE Login Page</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-3">3</span>
                            <span>User ยืนยันตัวตนที่ LINE</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-3">4</span>
                            <span>LINE Callback → ไม่พบ User</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-3">5</span>
                            <span>เก็บ LINE Profile ใน Session</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-3">6</span>
                            <span>Redirect ไปหน้า Register</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-3">7</span>
                            <span>กรอกข้อมูลเพิ่มเติม (Email, Password)</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-indigo-500 text-white rounded-full flex items-center justify-center text-xs mr-3">✓</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">สมัครสำเร็จ + เชื่อมต่อ LINE</span>
                        </li>
                    </ol>
                </div>
            </div>

            {{-- LINE Data Stored --}}
            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <h4 class="font-semibold text-gray-800 dark:text-white mb-3">
                    <i class="fas fa-database text-green-500 mr-2"></i>
                    ข้อมูล LINE ที่เก็บในระบบ
                </h4>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 mb-2">ใน Session (ชั่วคราว):</p>
                        <ul class="space-y-1 text-gray-600 dark:text-gray-300">
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_user_id</code></li>
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_display_name</code></li>
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_picture_url</code></li>
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_access_token</code></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 mb-2">ในตาราง users (ถาวร):</p>
                        <ul class="space-y-1 text-gray-600 dark:text-gray-300">
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_user_id</code></li>
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_display_name</code></li>
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_picture_url</code></li>
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_linked_at</code></li>
                            <li><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">line_verified</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 5: MLM Member Creation --}}
        <div id="mlm-flow" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="w-10 h-10 bg-orange-500 text-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-sitemap"></i>
                </div>
                5. MLM Member Creation Process
            </h2>

            {{-- Tree Types --}}
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                {{-- Unilevel Tree --}}
                <div class="p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl border-2 border-blue-200 dark:border-blue-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-layer-group text-blue-500 mr-2"></i>
                        Unilevel Tree
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        โครงสร้างแบบ Unlimited Width - แนะนำได้ไม่จำกัดจำนวนคนใน Level เดียวกัน
                    </p>

                    {{-- Tree Diagram --}}
                    <div class="flex justify-center mb-4">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-lg">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">Sponsor</span>
                            <div class="flex justify-center mt-4 space-x-4">
                                <div class="text-center">
                                    <div class="w-2 h-8 bg-blue-300 mx-auto"></div>
                                    <div class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center shadow">
                                        <i class="fas fa-user text-xs"></i>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="w-2 h-8 bg-blue-300 mx-auto"></div>
                                    <div class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center shadow">
                                        <i class="fas fa-user text-xs"></i>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="w-2 h-8 bg-blue-300 mx-auto"></div>
                                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center shadow animate-pulse">
                                        <i class="fas fa-user-plus text-xs"></i>
                                    </div>
                                    <span class="text-xs text-green-600 dark:text-green-400 font-bold">NEW</span>
                                </div>
                                <div class="text-center">
                                    <div class="w-2 h-8 bg-blue-300 mx-auto"></div>
                                    <div class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center shadow">
                                        <i class="fas fa-user text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-xs">
                        <p class="text-gray-500 dark:text-gray-400 mb-2">ข้อมูลที่บันทึก:</p>
                        <ul class="space-y-1 text-gray-600 dark:text-gray-300">
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">unilevel_sponsor_id</code> - Parent ใน tree</li>
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">unilevel_level</code> - ระดับ (1, 2, 3...)</li>
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">unilevel_path</code> - เส้นทาง (/1/5/12)</li>
                        </ul>
                    </div>
                </div>

                {{-- Binary Tree --}}
                <div class="p-6 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/30 dark:to-red-900/30 rounded-xl border-2 border-orange-200 dark:border-orange-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-code-branch text-orange-500 mr-2"></i>
                        Binary Tree
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        โครงสร้างแบบ 2 ขา - วางได้เฉพาะซ้ายหรือขวา (spillover อัตโนมัติ)
                    </p>

                    {{-- Tree Diagram --}}
                    <div class="flex justify-center mb-4">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-lg">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">Sponsor</span>
                            <div class="flex justify-center mt-4 space-x-8">
                                <div class="text-center">
                                    <div class="w-2 h-8 bg-orange-300 mx-auto transform -rotate-12"></div>
                                    <div class="w-10 h-10 bg-orange-400 text-white rounded-full flex items-center justify-center shadow">
                                        <i class="fas fa-user text-xs"></i>
                                    </div>
                                    <span class="text-xs text-gray-500">Left</span>
                                </div>
                                <div class="text-center">
                                    <div class="w-2 h-8 bg-orange-300 mx-auto transform rotate-12"></div>
                                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center shadow animate-pulse">
                                        <i class="fas fa-user-plus text-xs"></i>
                                    </div>
                                    <span class="text-xs text-green-600 dark:text-green-400 font-bold">NEW</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-xs">
                        <p class="text-gray-500 dark:text-gray-400 mb-2">ข้อมูลที่บันทึก:</p>
                        <ul class="space-y-1 text-gray-600 dark:text-gray-300">
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">binary_parent_id</code> - Parent ใน tree</li>
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">binary_position</code> - left / right</li>
                            <li><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">binary_path</code> - เส้นทาง (/left/right)</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Placement Strategy --}}
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <h4 class="font-semibold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-chess text-purple-500 mr-2"></i>
                    Binary Placement Strategies
                </h4>
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <h5 class="font-bold text-gray-700 dark:text-gray-300 mb-2">fill_level</h5>
                        <p class="text-gray-500 dark:text-gray-400">เรียงเต็มชั้นก่อนไปชั้นถัดไป (BFS)</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <h5 class="font-bold text-gray-700 dark:text-gray-300 mb-2">balanced</h5>
                        <p class="text-gray-500 dark:text-gray-400">วางในขาที่มีสมาชิกน้อยกว่า</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <h5 class="font-bold text-gray-700 dark:text-gray-300 mb-2">weak_leg</h5>
                        <p class="text-gray-500 dark:text-gray-400">วางในขาที่มี PV น้อยกว่า</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 6: Models & Database --}}
        <div id="models" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="w-10 h-10 bg-red-500 text-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-database"></i>
                </div>
                6. Models & Database Tables
            </h2>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- User Model --}}
                <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-700">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-3 flex items-center">
                        <i class="fas fa-user text-blue-500 mr-2"></i>
                        User
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">app/Models/User.php</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Table: users</p>
                    <div class="text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">id</span>
                            <span class="text-blue-600 dark:text-blue-400">bigint</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">name</span>
                            <span class="text-blue-600 dark:text-blue-400">varchar</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">email</span>
                            <span class="text-blue-600 dark:text-blue-400">varchar</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">password</span>
                            <span class="text-blue-600 dark:text-blue-400">varchar</span>
                        </div>
                        <div class="flex justify-between bg-yellow-100 dark:bg-yellow-900/30 px-1 rounded">
                            <span class="text-gray-600 dark:text-gray-400">role</span>
                            <span class="text-yellow-600 dark:text-yellow-400">varchar (guarded)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">line_user_id</span>
                            <span class="text-green-600 dark:text-green-400">varchar</span>
                        </div>
                    </div>
                </div>

                {{-- MlmMember Model --}}
                <div class="p-4 bg-orange-50 dark:bg-orange-900/30 rounded-xl border border-orange-200 dark:border-orange-700">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-3 flex items-center">
                        <i class="fas fa-sitemap text-orange-500 mr-2"></i>
                        MlmMember
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">app/Models/MlmMember.php</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Table: mlm_members</p>
                    <div class="text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">id</span>
                            <span class="text-blue-600 dark:text-blue-400">bigint</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">user_id</span>
                            <span class="text-blue-600 dark:text-blue-400">FK → users</span>
                        </div>
                        <div class="flex justify-between bg-red-100 dark:bg-red-900/30 px-1 rounded">
                            <span class="text-gray-600 dark:text-gray-400">mlm_plan_id</span>
                            <span class="text-red-600 dark:text-red-400">FK (NOT NULL)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">member_code</span>
                            <span class="text-blue-600 dark:text-blue-400">varchar</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">original_sponsor_id</span>
                            <span class="text-blue-600 dark:text-blue-400">FK</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">unilevel_sponsor_id</span>
                            <span class="text-blue-600 dark:text-blue-400">FK</span>
                        </div>
                    </div>
                </div>

                {{-- MlmPlan Model --}}
                <div class="p-4 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-200 dark:border-purple-700">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-3 flex items-center">
                        <i class="fas fa-crown text-purple-500 mr-2"></i>
                        MlmPlan
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">app/Models/MlmPlan.php</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Table: mlm_plans</p>
                    <div class="text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">id</span>
                            <span class="text-blue-600 dark:text-blue-400">bigint</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">name</span>
                            <span class="text-blue-600 dark:text-blue-400">varchar</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">slug</span>
                            <span class="text-blue-600 dark:text-blue-400">varchar</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">type</span>
                            <span class="text-blue-600 dark:text-blue-400">enum</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">is_default</span>
                            <span class="text-blue-600 dark:text-blue-400">boolean</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">is_active</span>
                            <span class="text-blue-600 dark:text-blue-400">boolean</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ER Diagram --}}
            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <h4 class="font-semibold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-project-diagram text-indigo-500 mr-2"></i>
                    Entity Relationships
                </h4>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <div class="px-4 py-2 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg font-mono">
                        User
                    </div>
                    <div class="text-gray-400">
                        1 ←→ 1
                    </div>
                    <div class="px-4 py-2 bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded-lg font-mono">
                        MlmMember
                    </div>
                    <div class="text-gray-400">
                        N ←→ 1
                    </div>
                    <div class="px-4 py-2 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-lg font-mono">
                        MlmPlan
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 7: Middleware Protection --}}
        <div id="middleware" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="w-10 h-10 bg-indigo-500 text-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-shield-alt"></i>
                </div>
                7. Middleware Protection
            </h2>

            <div class="grid md:grid-cols-2 gap-6">
                {{-- Auth Flow --}}
                <div class="p-6 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-200 dark:border-indigo-700">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4">
                        <i class="fas fa-lock text-indigo-500 mr-2"></i>
                        Authentication Flow
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                            <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center mr-3">1</div>
                            <span class="text-gray-600 dark:text-gray-400">Request เข้ามา</span>
                        </div>
                        <div class="flex items-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                            <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center mr-3">2</div>
                            <span class="text-gray-600 dark:text-gray-400">
                                <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">auth</code> middleware ตรวจสอบ login
                            </span>
                        </div>
                        <div class="flex items-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                            <div class="w-8 h-8 bg-purple-500 text-white rounded-full flex items-center justify-center mr-3">3</div>
                            <span class="text-gray-600 dark:text-gray-400">
                                <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">role:user</code> ตรวจสอบ role
                            </span>
                        </div>
                        <div class="flex items-center p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center mr-3">✓</div>
                            <span class="text-green-700 dark:text-green-300 font-medium">เข้าถึง Dashboard ได้</span>
                        </div>
                    </div>
                </div>

                {{-- CheckRole Middleware --}}
                <div class="p-6 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-200 dark:border-purple-700">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4">
                        <i class="fas fa-user-shield text-purple-500 mr-2"></i>
                        CheckRole Middleware
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        ไฟล์: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">app/Http/Middleware/CheckRole.php</code>
                    </p>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <pre class="text-xs bg-gray-900 text-green-400 p-3 rounded-lg overflow-x-auto">
// ตรวจสอบ role ของ user
if ($user->role === $role) {
    return $next($request);
}

// ถ้า role ไม่ตรง → redirect
return redirect()->route('login')
    ->with('error', 'คุณไม่มีสิทธิ์เข้าถึง');</pre>
                    </div>
                    <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                        <p class="text-xs text-yellow-700 dark:text-yellow-300">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>สำคัญ:</strong> ถ้า user.role เป็น null จะ fail middleware นี้
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 8: Recent Fixes --}}
        <div id="fixes" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="w-10 h-10 bg-yellow-500 text-white rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-wrench"></i>
                </div>
                8. การแก้ไขล่าสุด (Recent Fixes)
            </h2>

            <div class="space-y-6">
                {{-- Fix 1 --}}
                <div class="p-6 bg-red-50 dark:bg-red-900/20 rounded-xl border-l-4 border-red-500">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-red-500 text-white rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-bug"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                                Bug #1: Role ไม่ถูกตั้งค่า
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-3">
                                <strong>ปัญหา:</strong> หลังสมัครสมาชิก user ไม่สามารถเข้า Dashboard ได้ เพราะ role เป็น null
                            </p>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-3">
                                <p class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2">สาเหตุ:</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">role</code> อยู่ใน
                                    <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">$guarded</code> ของ User model
                                    ทำให้ mass assignment ไม่ทำงาน → role = null ใน memory
                                </p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <p class="text-sm font-semibold text-green-600 dark:text-green-400 mb-2">วิธีแก้:</p>
                                <pre class="text-xs bg-gray-900 text-green-400 p-3 rounded-lg overflow-x-auto">
$user = User::create($userData);

// ⚠️ CRITICAL: ตั้งค่า role หลัง create
$user->role = 'user';
$user->save();</pre>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Fix 2 --}}
                <div class="p-6 bg-orange-50 dark:bg-orange-900/20 rounded-xl border-l-4 border-orange-500">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-orange-500 text-white rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                                Bug #2: MlmPlan ไม่มีข้อมูล
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-3">
                                <strong>ปัญหา:</strong> การสมัคร error เพราะ mlm_plan_id เป็น NOT NULL แต่ไม่มี MlmPlan ในระบบ
                            </p>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-3">
                                <p class="text-sm font-semibold text-orange-600 dark:text-orange-400 mb-2">สาเหตุ:</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    MlmPlanSeeder เดิมใช้ <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">MlmPlan::query()->delete()</code>
                                    ลบ plans ทั้งหมด ทำให้ไม่มี plan สำหรับ registration
                                </p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <p class="text-sm font-semibold text-green-600 dark:text-green-400 mb-2">วิธีแก้:</p>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>1. แก้ไข MlmPlanSeeder ให้สร้าง Default Plan แทนการลบ</li>
                                    <li>2. เพิ่ม fallback ใน RegisterController ให้สร้าง Plan อัตโนมัติ</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Files Summary --}}
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-2xl shadow-lg p-6 text-white">
            <h2 class="text-xl font-bold mb-6 flex items-center">
                <i class="fas fa-folder-open text-yellow-400 mr-3"></i>
                ไฟล์ที่เกี่ยวข้องทั้งหมด
            </h2>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div>
                    <h3 class="text-yellow-400 font-semibold mb-2">Controllers</h3>
                    <ul class="space-y-1 text-gray-300">
                        <li><i class="fas fa-file-code text-blue-400 mr-1"></i> RegisterController.php</li>
                        <li><i class="fas fa-file-code text-blue-400 mr-1"></i> LoginController.php</li>
                        <li><i class="fas fa-file-code text-green-400 mr-1"></i> LineLoginController.php</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-yellow-400 font-semibold mb-2">Models</h3>
                    <ul class="space-y-1 text-gray-300">
                        <li><i class="fas fa-cube text-purple-400 mr-1"></i> User.php</li>
                        <li><i class="fas fa-cube text-purple-400 mr-1"></i> MlmMember.php</li>
                        <li><i class="fas fa-cube text-purple-400 mr-1"></i> MlmPlan.php</li>
                        <li><i class="fas fa-cube text-green-400 mr-1"></i> LineOaSetting.php</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-yellow-400 font-semibold mb-2">Views</h3>
                    <ul class="space-y-1 text-gray-300">
                        <li><i class="fas fa-file-alt text-orange-400 mr-1"></i> auth/register.blade.php</li>
                        <li><i class="fas fa-file-alt text-orange-400 mr-1"></i> auth/login.blade.php</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-yellow-400 font-semibold mb-2">Routes</h3>
                    <ul class="space-y-1 text-gray-300">
                        <li><i class="fas fa-route text-cyan-400 mr-1"></i> routes/web.php</li>
                        <li><i class="fas fa-route text-cyan-400 mr-1"></i> routes/user.php</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-yellow-400 font-semibold mb-2">Middleware</h3>
                    <ul class="space-y-1 text-gray-300">
                        <li><i class="fas fa-shield-alt text-red-400 mr-1"></i> CheckRole.php</li>
                        <li><i class="fas fa-shield-alt text-red-400 mr-1"></i> VerifyCloudfareTurnstile.php</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-yellow-400 font-semibold mb-2">Seeders</h3>
                    <ul class="space-y-1 text-gray-300">
                        <li><i class="fas fa-seedling text-green-400 mr-1"></i> MlmPlanSeeder.php</li>
                        <li><i class="fas fa-seedling text-green-400 mr-1"></i> DatabaseSeeder.php</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

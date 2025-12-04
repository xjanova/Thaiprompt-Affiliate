{{--
/**
 * เอกสารวิธีสมัครสมาชิก - สำหรับผู้ใช้ทั่วไป
 *
 * หน้าเอกสารที่อธิบายขั้นตอนการสมัครสมาชิกอย่างละเอียด
 * เขียนด้วยภาษาที่เข้าใจง่าย เหมาะสำหรับทุกคน
 *
 * @version 1.0.0
 * @author Thaiprompt Team
 * @created 2025-12-04
 */
--}}

@extends('layouts.landing')

@php
    $siteSettings = \App\Models\SiteSetting::getSetting();
    $appName = $siteSettings->site_name ?? 'TP-Affiliate Pro';
    $logo = $siteSettings->logo;
@endphp

@section('title', 'วิธีสมัครสมาชิก - ' . $appName)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-green-600 to-emerald-600 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-full text-white text-sm font-medium mb-6">
                <i class="fas fa-book-open"></i>
                <span>คู่มือการใช้งาน</span>
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                📝 วิธีสมัครสมาชิก
            </h1>
            <p class="text-xl text-green-100 max-w-2xl mx-auto">
                อธิบายทุกขั้นตอนอย่างละเอียด อ่านง่าย ทำตามได้เลย!
            </p>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">

        {{-- Quick Navigation --}}
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 mb-12">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-list text-green-400"></i>
                สารบัญ - เลือกอ่านหัวข้อที่ต้องการ
            </h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <a href="#web-registration" class="flex items-center gap-3 p-4 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/30 rounded-xl transition-colors">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-globe text-white"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-white">สมัครผ่านเว็บไซต์</p>
                        <p class="text-sm text-slate-400">4 ขั้นตอน • ใช้เวลา 2 นาที</p>
                    </div>
                </a>
                <a href="#line-registration" class="flex items-center gap-3 p-4 bg-green-500/10 hover:bg-green-500/20 border border-green-500/30 rounded-xl transition-colors">
                    <div class="w-10 h-10 bg-[#06C755] rounded-lg flex items-center justify-center">
                        <i class="fab fa-line text-white"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-white">สมัครผ่าน LINE</p>
                        <p class="text-sm text-slate-400">3 ขั้นตอน • เร็วที่สุด</p>
                    </div>
                </a>
            </div>
        </div>

        {{-- Important Note --}}
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-6 mb-12">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-lightbulb text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-amber-300 mb-2">💡 สิ่งที่ต้องเตรียมก่อนสมัคร</h3>
                    <ul class="text-amber-200/90 space-y-2">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span><strong>อีเมล</strong> ที่ใช้งานได้จริง (เช่น Gmail, Hotmail, Yahoo)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span><strong>ชื่อ-นามสกุล</strong> จริงของคุณ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span><strong>รหัสแนะนำ</strong> (ถ้ามีคนแนะนำมา)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ============================================== --}}
        {{-- SECTION 1: สมัครผ่านเว็บไซต์ --}}
        {{-- ============================================== --}}
        <section id="web-registration" class="mb-16 scroll-mt-8">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-globe text-2xl text-white"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-white">วิธีที่ 1: สมัครผ่านเว็บไซต์</h2>
                    <p class="text-slate-400">เหมาะสำหรับทุกคน • ทำได้จากคอมพิวเตอร์หรือมือถือ</p>
                </div>
            </div>

            {{-- Step 1 --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 lg:p-8 mb-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center font-bold text-white text-xl flex-shrink-0">
                        1
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">เปิดหน้าสมัครสมาชิก</h3>
                        <p class="text-slate-400">ไปที่หน้าลงทะเบียนของเว็บไซต์</p>
                    </div>
                </div>

                <div class="bg-slate-800/50 rounded-xl p-6 mb-6">
                    <h4 class="font-semibold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-mouse-pointer text-blue-400"></i>
                        วิธีทำ:
                    </h4>
                    <ol class="space-y-3 text-slate-300">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-blue-500/30 text-blue-300 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                            <span>เปิดเว็บเบราว์เซอร์ (Chrome, Safari, หรือแอพอื่นที่ใช้เปิดเว็บ)</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-blue-500/30 text-blue-300 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                            <span>พิมพ์ที่อยู่เว็บไซต์ หรือ กดปุ่ม <strong class="text-green-400">"สมัครสมาชิก"</strong> ที่หน้าแรก</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-blue-500/30 text-blue-300 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                            <span>หรือถ้าได้รับลิงก์จากเพื่อน/ผู้แนะนำ ให้กดลิงก์นั้นเลย</span>
                        </li>
                    </ol>
                </div>

                <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4">
                    <p class="text-green-300 flex items-start gap-2">
                        <i class="fas fa-check-circle mt-1"></i>
                        <span><strong>เคล็ดลับ:</strong> ถ้าได้รับลิงก์จากผู้แนะนำ ให้ใช้ลิงก์นั้นสมัคร เพราะระบบจะกรอกรหัสแนะนำให้อัตโนมัติ</span>
                    </p>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 lg:p-8 mb-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center font-bold text-white text-xl flex-shrink-0">
                        2
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">กรอกข้อมูลส่วนตัว</h3>
                        <p class="text-slate-400">กรอกข้อมูลของคุณในแบบฟอร์ม</p>
                    </div>
                </div>

                <div class="bg-slate-800/50 rounded-xl p-6 mb-6">
                    <h4 class="font-semibold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-edit text-purple-400"></i>
                        ข้อมูลที่ต้องกรอก:
                    </h4>

                    <div class="space-y-6">
                        {{-- ชื่อ-นามสกุล --}}
                        <div class="border-l-4 border-blue-500 pl-4">
                            <h5 class="font-bold text-white mb-2">
                                <i class="fas fa-user text-blue-400 mr-2"></i>
                                ชื่อ-นามสกุล
                            </h5>
                            <p class="text-slate-400 mb-2">พิมพ์ชื่อจริงและนามสกุลของคุณ</p>
                            <div class="bg-slate-700 rounded-lg px-4 py-3 font-mono text-green-400">
                                ตัวอย่าง: สมชาย ใจดี
                            </div>
                        </div>

                        {{-- อีเมล --}}
                        <div class="border-l-4 border-green-500 pl-4">
                            <h5 class="font-bold text-white mb-2">
                                <i class="fas fa-envelope text-green-400 mr-2"></i>
                                อีเมล
                            </h5>
                            <p class="text-slate-400 mb-2">พิมพ์อีเมลที่คุณใช้งานจริง (สำคัญมาก!)</p>
                            <div class="bg-slate-700 rounded-lg px-4 py-3 font-mono text-green-400">
                                ตัวอย่าง: somchai@gmail.com
                            </div>
                            <p class="text-amber-400 text-sm mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                ต้องเป็นอีเมลที่เข้าถึงได้ เพราะใช้สำหรับรีเซ็ตรหัสผ่าน
                            </p>
                        </div>

                        {{-- รหัสผ่าน --}}
                        <div class="border-l-4 border-purple-500 pl-4">
                            <h5 class="font-bold text-white mb-2">
                                <i class="fas fa-lock text-purple-400 mr-2"></i>
                                รหัสผ่าน
                            </h5>
                            <p class="text-slate-400 mb-2">ตั้งรหัสผ่านสำหรับเข้าสู่ระบบ</p>
                            <div class="bg-slate-700 rounded-lg px-4 py-3">
                                <ul class="text-slate-300 space-y-1 text-sm">
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>ต้องมีอย่างน้อย 8 ตัวอักษร</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>ควรมีตัวเลขผสม เช่น abc12345</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>จดไว้ในที่ปลอดภัย อย่าลืม!</li>
                                </ul>
                            </div>
                        </div>

                        {{-- ยืนยันรหัสผ่าน --}}
                        <div class="border-l-4 border-pink-500 pl-4">
                            <h5 class="font-bold text-white mb-2">
                                <i class="fas fa-lock text-pink-400 mr-2"></i>
                                ยืนยันรหัสผ่าน
                            </h5>
                            <p class="text-slate-400 mb-2">พิมพ์รหัสผ่านอีกครั้งให้ตรงกับช่องแรก</p>
                            <p class="text-slate-500 text-sm">
                                <i class="fas fa-info-circle mr-1"></i>
                                เพื่อยืนยันว่าคุณพิมพ์รหัสผ่านถูกต้อง
                            </p>
                        </div>

                        {{-- รหัสแนะนำ --}}
                        <div class="border-l-4 border-amber-500 pl-4">
                            <h5 class="font-bold text-white mb-2">
                                <i class="fas fa-gift text-amber-400 mr-2"></i>
                                รหัสแนะนำ (ถ้ามี)
                            </h5>
                            <p class="text-slate-400 mb-2">ถ้ามีคนแนะนำมา ให้กรอกรหัสที่ได้รับ</p>
                            <div class="bg-slate-700 rounded-lg px-4 py-3 font-mono text-amber-400">
                                ตัวอย่าง: REF123456
                            </div>
                            <p class="text-slate-500 text-sm mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                ไม่บังคับ - ถ้าไม่มีก็ปล่อยว่างได้
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 lg:p-8 mb-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center font-bold text-white text-xl flex-shrink-0">
                        3
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">กดปุ่มสมัครสมาชิก</h3>
                        <p class="text-slate-400">ตรวจสอบข้อมูลแล้วกดปุ่มสมัคร</p>
                    </div>
                </div>

                <div class="bg-slate-800/50 rounded-xl p-6 mb-6">
                    <h4 class="font-semibold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-hand-pointer text-green-400"></i>
                        วิธีทำ:
                    </h4>
                    <ol class="space-y-3 text-slate-300">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-green-500/30 text-green-300 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                            <span>ตรวจสอบข้อมูลที่กรอกว่าถูกต้องหรือไม่</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-green-500/30 text-green-300 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                            <span>มองหาปุ่มสีม่วง/น้ำเงินที่เขียนว่า <strong class="text-green-400">"สมัครสมาชิกเลย!"</strong></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-green-500/30 text-green-300 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                            <span>กดที่ปุ่มนั้น 1 ครั้ง แล้วรอสักครู่</span>
                        </li>
                    </ol>
                </div>

                <div class="text-center">
                    <div class="inline-block px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold text-lg rounded-xl">
                        <i class="fas fa-rocket mr-2"></i>
                        สมัครสมาชิกเลย!
                    </div>
                    <p class="text-slate-500 text-sm mt-3">
                        <i class="fas fa-arrow-up animate-bounce"></i>
                        ปุ่มจะมีหน้าตาประมาณนี้
                    </p>
                </div>
            </div>

            {{-- Step 4 --}}
            <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 border border-green-500/30 rounded-2xl p-6 lg:p-8">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center font-bold text-white text-xl flex-shrink-0">
                        ✓
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">🎉 สมัครสำเร็จแล้ว!</h3>
                        <p class="text-green-300">ยินดีต้อนรับสู่ {{ $appName }}</p>
                    </div>
                </div>

                <div class="bg-white/5 rounded-xl p-6">
                    <h4 class="font-semibold text-white mb-4">หลังจากสมัครสำเร็จ:</h4>
                    <ul class="space-y-3 text-slate-300">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>ระบบจะพาคุณเข้าสู่หน้า <strong>Dashboard</strong> โดยอัตโนมัติ</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>คุณสามารถเริ่มใช้งานระบบได้ทันที</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>ครั้งต่อไปให้ใช้ <strong>อีเมล</strong> และ <strong>รหัสผ่าน</strong> ที่ตั้งไว้เพื่อเข้าสู่ระบบ</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- Divider --}}
        <div class="flex items-center gap-4 my-16">
            <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
            <span class="text-slate-500 text-sm">หรือ</span>
            <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
        </div>

        {{-- ============================================== --}}
        {{-- SECTION 2: สมัครผ่าน LINE --}}
        {{-- ============================================== --}}
        <section id="line-registration" class="mb-16 scroll-mt-8">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-14 h-14 bg-gradient-to-br from-[#06C755] to-[#00B900] rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fab fa-line text-2xl text-white"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-white">วิธีที่ 2: สมัครผ่าน LINE</h2>
                    <p class="text-slate-400">วิธีที่เร็วที่สุด • ใช้บัญชี LINE ที่มีอยู่แล้ว</p>
                </div>
            </div>

            {{-- LINE Benefits --}}
            <div class="bg-[#06C755]/10 border border-[#06C755]/30 rounded-2xl p-6 mb-8">
                <h3 class="text-lg font-bold text-[#06C755] mb-4 flex items-center gap-2">
                    <i class="fas fa-star"></i>
                    ข้อดีของการสมัครผ่าน LINE
                </h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-bolt text-yellow-400 mt-1"></i>
                        <span class="text-slate-300"><strong class="text-white">เร็วมาก</strong> - ไม่ต้องกรอกข้อมูลเยอะ</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-bell text-yellow-400 mt-1"></i>
                        <span class="text-slate-300"><strong class="text-white">รับแจ้งเตือน</strong> - ผ่านทาง LINE</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-shield-alt text-yellow-400 mt-1"></i>
                        <span class="text-slate-300"><strong class="text-white">ปลอดภัย</strong> - ใช้ระบบของ LINE</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-key text-yellow-400 mt-1"></i>
                        <span class="text-slate-300"><strong class="text-white">ไม่ต้องจำรหัสผ่าน</strong> - ล็อกอินด้วย LINE</span>
                    </div>
                </div>
            </div>

            {{-- Step 1 --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 lg:p-8 mb-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#06C755] rounded-full flex items-center justify-center font-bold text-white text-xl flex-shrink-0">
                        1
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">กดปุ่ม "สมัครด้วย LINE"</h3>
                        <p class="text-slate-400">หาปุ่มสีเขียวที่หน้าสมัครสมาชิก</p>
                    </div>
                </div>

                <div class="bg-slate-800/50 rounded-xl p-6 mb-6">
                    <h4 class="font-semibold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-mouse-pointer text-[#06C755]"></i>
                        วิธีทำ:
                    </h4>
                    <ol class="space-y-3 text-slate-300">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-[#06C755]/30 text-[#06C755] rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                            <span>เปิดหน้าสมัครสมาชิก (เหมือนวิธีที่ 1)</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-[#06C755]/30 text-[#06C755] rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                            <span>เลื่อนลงมาหาปุ่มสีเขียวที่เขียนว่า <strong class="text-[#06C755]">"สมัครด้วย LINE"</strong></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-[#06C755]/30 text-[#06C755] rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                            <span>กดที่ปุ่มนั้น</span>
                        </li>
                    </ol>
                </div>

                <div class="text-center">
                    <div class="inline-flex items-center gap-2 px-8 py-4 bg-[#06C755] text-white font-bold text-lg rounded-xl">
                        <i class="fab fa-line text-2xl"></i>
                        สมัครด้วย LINE
                    </div>
                    <p class="text-slate-500 text-sm mt-3">
                        <i class="fas fa-arrow-up animate-bounce"></i>
                        ปุ่มจะมีหน้าตาประมาณนี้
                    </p>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 lg:p-8 mb-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#06C755] rounded-full flex items-center justify-center font-bold text-white text-xl flex-shrink-0">
                        2
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">ยืนยันตัวตนกับ LINE</h3>
                        <p class="text-slate-400">อนุญาตให้เว็บไซต์เข้าถึงข้อมูล LINE ของคุณ</p>
                    </div>
                </div>

                <div class="bg-slate-800/50 rounded-xl p-6 mb-6">
                    <h4 class="font-semibold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-user-check text-[#06C755]"></i>
                        สิ่งที่จะเกิดขึ้น:
                    </h4>
                    <ol class="space-y-4 text-slate-300">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-[#06C755]/30 text-[#06C755] rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                            <div>
                                <span>ระบบจะเปิดหน้าต่าง LINE ขึ้นมา</span>
                                <p class="text-slate-500 text-sm mt-1">บนมือถืออาจเปิดแอพ LINE / บนคอมพิวเตอร์จะเปิดหน้าเว็บ LINE</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-[#06C755]/30 text-[#06C755] rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                            <div>
                                <span>ถ้ายังไม่ได้ล็อกอิน LINE ให้ล็อกอินก่อน</span>
                                <p class="text-slate-500 text-sm mt-1">ใช้อีเมลหรือเบอร์โทรที่ผูกกับ LINE</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 bg-[#06C755]/30 text-[#06C755] rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                            <div>
                                <span>กดปุ่ม <strong class="text-[#06C755]">"อนุญาต"</strong> หรือ <strong class="text-[#06C755]">"Allow"</strong></span>
                                <p class="text-slate-500 text-sm mt-1">เพื่อให้เว็บไซต์รู้ว่าคุณคือใคร</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                    <p class="text-blue-300 flex items-start gap-2">
                        <i class="fas fa-shield-alt mt-1"></i>
                        <span><strong>ปลอดภัย:</strong> เราจะเข้าถึงแค่ชื่อและรูปโปรไฟล์ของคุณเท่านั้น ไม่สามารถอ่านแชทหรือส่งข้อความแทนคุณได้</span>
                    </p>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="bg-gradient-to-br from-[#06C755]/20 to-green-500/20 border border-[#06C755]/30 rounded-2xl p-6 lg:p-8">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#06C755] rounded-full flex items-center justify-center font-bold text-white text-xl flex-shrink-0">
                        ✓
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">🎉 สมัครสำเร็จแล้ว!</h3>
                        <p class="text-green-300">ยินดีต้อนรับสู่ {{ $appName }}</p>
                    </div>
                </div>

                <div class="bg-white/5 rounded-xl p-6">
                    <h4 class="font-semibold text-white mb-4">หลังจากสมัครสำเร็จ:</h4>
                    <ul class="space-y-3 text-slate-300">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-[#06C755] mt-1"></i>
                            <span>ระบบจะพาคุณเข้าสู่หน้า <strong>Dashboard</strong> โดยอัตโนมัติ</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-[#06C755] mt-1"></i>
                            <span>คุณจะได้รับการแจ้งเตือนสำคัญผ่านทาง LINE</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-[#06C755] mt-1"></i>
                            <span>ครั้งต่อไปให้กดปุ่ม <strong>"เข้าสู่ระบบด้วย LINE"</strong> ที่หน้า Login</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- FAQ Section --}}
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-white mb-8 flex items-center gap-3">
                <i class="fas fa-question-circle text-amber-400"></i>
                คำถามที่พบบ่อย
            </h2>

            <div class="space-y-4">
                {{-- FAQ 1 --}}
                <div class="bg-white/5 border border-white/10 rounded-xl p-6">
                    <h3 class="font-bold text-white mb-3">
                        <i class="fas fa-q text-amber-400 mr-2"></i>
                        สมัครแล้วต้องเสียเงินไหม?
                    </h3>
                    <p class="text-slate-300">
                        <i class="fas fa-a text-green-400 mr-2"></i>
                        <strong class="text-green-400">ไม่เสียเงิน!</strong> การสมัครสมาชิกฟรี 100% ไม่มีค่าใช้จ่ายใดๆ ทั้งสิ้น
                    </p>
                </div>

                {{-- FAQ 2 --}}
                <div class="bg-white/5 border border-white/10 rounded-xl p-6">
                    <h3 class="font-bold text-white mb-3">
                        <i class="fas fa-q text-amber-400 mr-2"></i>
                        ลืมรหัสผ่านทำอย่างไร?
                    </h3>
                    <p class="text-slate-300">
                        <i class="fas fa-a text-green-400 mr-2"></i>
                        ไปที่หน้า Login แล้วกดลิงก์ <strong>"ลืมรหัสผ่าน?"</strong> ระบบจะส่งลิงก์ตั้งรหัสผ่านใหม่ไปที่อีเมลของคุณ
                    </p>
                </div>

                {{-- FAQ 3 --}}
                <div class="bg-white/5 border border-white/10 rounded-xl p-6">
                    <h3 class="font-bold text-white mb-3">
                        <i class="fas fa-q text-amber-400 mr-2"></i>
                        สมัครผ่านเว็บกับ LINE ต่างกันอย่างไร?
                    </h3>
                    <p class="text-slate-300">
                        <i class="fas fa-a text-green-400 mr-2"></i>
                        ผลลัพธ์เหมือนกัน แต่สมัครผ่าน LINE จะเร็วกว่าและได้รับแจ้งเตือนผ่าน LINE ด้วย ถ้าใช้ LINE อยู่แล้วแนะนำให้สมัครผ่าน LINE
                    </p>
                </div>

                {{-- FAQ 4 --}}
                <div class="bg-white/5 border border-white/10 rounded-xl p-6">
                    <h3 class="font-bold text-white mb-3">
                        <i class="fas fa-q text-amber-400 mr-2"></i>
                        ไม่มีรหัสแนะนำจะสมัครได้ไหม?
                    </h3>
                    <p class="text-slate-300">
                        <i class="fas fa-a text-green-400 mr-2"></i>
                        <strong class="text-green-400">สมัครได้!</strong> รหัสแนะนำไม่บังคับ แค่ช่วยให้ผู้แนะนำได้รับค่าคอมมิชชั่นเท่านั้น
                    </p>
                </div>

                {{-- FAQ 5 --}}
                <div class="bg-white/5 border border-white/10 rounded-xl p-6">
                    <h3 class="font-bold text-white mb-3">
                        <i class="fas fa-q text-amber-400 mr-2"></i>
                        สมัครไม่ได้ ขึ้น Error ทำอย่างไร?
                    </h3>
                    <p class="text-slate-300">
                        <i class="fas fa-a text-green-400 mr-2"></i>
                        ลองตรวจสอบว่า: (1) อีเมลถูกต้องและยังไม่เคยใช้สมัคร (2) รหัสผ่านมีอย่างน้อย 8 ตัวอักษร (3) รหัสผ่านทั้ง 2 ช่องตรงกัน ถ้ายังไม่ได้ ติดต่อทีมซัพพอร์ต
                    </p>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="text-center">
            <div class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 rounded-2xl p-8 lg:p-12">
                <h2 class="text-3xl font-bold text-white mb-4">พร้อมเริ่มต้นแล้ว?</h2>
                <p class="text-slate-300 mb-8 max-w-xl mx-auto">
                    สมัครสมาชิกฟรีวันนี้ เริ่มต้นสร้างรายได้กับ {{ $appName }} ได้ทันที!
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-green-500/30 transition-all">
                        <i class="fas fa-rocket"></i>
                        สมัครสมาชิกเลย!
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-lg rounded-xl transition-all">
                        <i class="fas fa-home"></i>
                        กลับหน้าแรก
                    </a>
                </div>
            </div>
        </section>

    </div>

    {{-- Help Button --}}
    <div class="fixed bottom-6 right-6">
        <a href="{{ route('contact') }}" class="flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-full shadow-lg transition-all">
            <i class="fas fa-headset"></i>
            <span>ต้องการความช่วยเหลือ?</span>
        </a>
    </div>

</div>
@endsection

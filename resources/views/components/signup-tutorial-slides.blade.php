{{--
/**
 * สไลด์สอนการสมัครสมาชิก - Signup Tutorial Slides
 *
 * แสดงขั้นตอนการสมัครสมาชิกทั้ง 2 แบบ:
 * 1. สมัครผ่านเว็บ (Web Registration)
 * 2. สมัครผ่าน LINE (LINE Registration)
 *
 * V3 Technologies:
 * - Tailwind CSS (utility-first)
 * - Alpine.js (reactive)
 * - Smooth animations
 *
 * @version 1.0.0
 * @author Thaiprompt Team
 * @created 2025-12-04
 */
--}}

@php
    $appName = \App\Models\SiteSetting::getSetting()->site_name ?? 'TP-Affiliate Pro';
@endphp

{{-- ===== SLIDES: สมัครผ่านเว็บ (signup-web) ===== --}}

{{-- Slide 1: หน้าปก - สมัครผ่านเว็บ --}}
<div class="slide" data-topic="signup-web">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-blue-900 via-purple-900 to-indigo-900">
        <div class="max-w-5xl mx-auto text-center">
            {{-- Icon --}}
            <div class="mb-8 animate-float">
                <div class="w-32 h-32 mx-auto bg-gradient-to-br from-blue-500 to-purple-600 rounded-3xl flex items-center justify-center shadow-2xl">
                    <i class="fas fa-user-plus text-6xl text-white"></i>
                </div>
            </div>

            {{-- Title --}}
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6">
                <span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                    วิธีสมัครสมาชิกผ่านเว็บ
                </span>
            </h1>

            <p class="text-2xl text-slate-300 mb-8 max-w-3xl mx-auto">
                ขั้นตอนง่ายๆ เพียง 4 ขั้นตอน สมัครฟรี! ไม่มีค่าใช้จ่าย
            </p>

            {{-- Quick Stats --}}
            <div class="flex flex-wrap justify-center gap-6 mb-10">
                <div class="px-6 py-3 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                    <i class="fas fa-clock text-green-400 mr-2"></i>
                    <span class="text-white font-semibold">ใช้เวลาไม่ถึง 2 นาที</span>
                </div>
                <div class="px-6 py-3 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                    <i class="fas fa-check-circle text-blue-400 mr-2"></i>
                    <span class="text-white font-semibold">4 ขั้นตอนง่ายๆ</span>
                </div>
                <div class="px-6 py-3 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                    <i class="fas fa-gift text-amber-400 mr-2"></i>
                    <span class="text-white font-semibold">สมัครฟรี!</span>
                </div>
            </div>

            {{-- CTA --}}
            <div class="flex justify-center gap-4">
                <span class="inline-flex items-center gap-2 text-slate-400">
                    <i class="fas fa-arrow-right animate-pulse"></i>
                    กดลูกศรขวาเพื่อดูขั้นตอน
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Slide 2: ขั้นตอนที่ 1 - เข้าหน้าสมัครสมาชิก --}}
<div class="slide" data-topic="signup-web">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                {{-- Left: Content --}}
                <div>
                    {{-- Step Badge --}}
                    <div class="inline-flex items-center gap-3 px-5 py-2.5 bg-blue-500/20 border border-blue-500/30 rounded-full mb-6">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center font-bold text-white text-lg">1</div>
                        <span class="text-blue-400 font-semibold text-lg">ขั้นตอนที่ 1</span>
                    </div>

                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        เข้าหน้าสมัครสมาชิก
                    </h2>

                    <div class="space-y-4 text-lg text-slate-300">
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>คลิกปุ่ม <strong class="text-white">"เริ่มต้นใช้งานฟรี"</strong> หรือ <strong class="text-white">"ลงทะเบียน"</strong> ที่หน้าแรก</span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>หรือเข้าผ่าน URL โดยตรง: <code class="px-2 py-1 bg-slate-700 rounded text-blue-300">/register</code></span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>ถ้ามีรหัสแนะนำ ให้เข้าผ่าน Link ที่ผู้แนะนำส่งให้</span>
                        </p>
                    </div>

                    {{-- Tip Box --}}
                    <div class="mt-8 p-5 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-lightbulb text-amber-400 text-xl mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-amber-300 mb-1">เคล็ดลับ</p>
                                <p class="text-amber-200/80 text-sm">ถ้าได้รับ Link จากผู้แนะนำ ให้คลิก Link นั้นโดยตรง ระบบจะกรอกรหัสแนะนำให้อัตโนมัติ</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Visual --}}
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-purple-500/20 rounded-3xl blur-3xl"></div>
                    <div class="relative bg-slate-800/50 backdrop-blur-sm border border-white/10 rounded-2xl p-8 shadow-2xl">
                        {{-- Mock Browser --}}
                        <div class="bg-slate-700 rounded-t-lg p-3 flex items-center gap-2">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            </div>
                            <div class="flex-1 ml-4">
                                <div class="bg-slate-600 rounded px-3 py-1 text-xs text-slate-300">
                                    {{ config('app.url') }}/register
                                </div>
                            </div>
                        </div>

                        {{-- Mock Content --}}
                        <div class="bg-gradient-to-br from-slate-900 to-blue-950 p-6 rounded-b-lg">
                            <div class="text-center mb-6">
                                <div class="w-16 h-16 mx-auto bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl mb-4 flex items-center justify-center">
                                    <span class="text-white font-bold text-xl">TP</span>
                                </div>
                                <h3 class="text-xl font-bold text-white">สมัครสมาชิก</h3>
                            </div>

                            {{-- Mock Form --}}
                            <div class="space-y-3">
                                <div class="h-10 bg-white/5 border border-white/10 rounded-lg"></div>
                                <div class="h-10 bg-white/5 border border-white/10 rounded-lg"></div>
                                <div class="h-10 bg-white/5 border border-white/10 rounded-lg"></div>
                                <div class="h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-semibold">สมัครสมาชิกเลย!</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Arrow Indicator --}}
                    <div class="absolute -left-8 top-1/2 -translate-y-1/2">
                        <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center animate-pulse shadow-lg shadow-blue-500/50">
                            <i class="fas fa-mouse-pointer text-white text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 3: ขั้นตอนที่ 2 - กรอกข้อมูลส่วนตัว --}}
<div class="slide" data-topic="signup-web">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                {{-- Left: Visual --}}
                <div class="order-2 lg:order-1 relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-3xl blur-3xl"></div>
                    <div class="relative bg-slate-800/50 backdrop-blur-sm border border-white/10 rounded-2xl p-8 shadow-2xl">
                        {{-- Form Fields --}}
                        <div class="space-y-5">
                            {{-- Name Field --}}
                            <div class="relative">
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <i class="fas fa-user mr-2 text-blue-400"></i>ชื่อ-นามสกุล
                                </label>
                                <div class="bg-white/5 border-2 border-blue-500 rounded-xl px-4 py-3 text-white">
                                    สมชาย ใจดี
                                </div>
                                <div class="absolute -right-3 top-1/2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                            </div>

                            {{-- Email Field --}}
                            <div class="relative">
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <i class="fas fa-envelope mr-2 text-blue-400"></i>อีเมล
                                </label>
                                <div class="bg-white/5 border-2 border-blue-500 rounded-xl px-4 py-3 text-white">
                                    somchai@email.com
                                </div>
                                <div class="absolute -right-3 top-1/2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                            </div>

                            {{-- Password Fields --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="fas fa-lock mr-2 text-purple-400"></i>รหัสผ่าน
                                    </label>
                                    <div class="bg-white/5 border border-white/20 rounded-xl px-4 py-3 text-white">
                                        ••••••••
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="fas fa-lock mr-2 text-purple-400"></i>ยืนยัน
                                    </label>
                                    <div class="bg-white/5 border border-white/20 rounded-xl px-4 py-3 text-white">
                                        ••••••••
                                    </div>
                                </div>
                            </div>

                            {{-- Referral Code --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <i class="fas fa-gift mr-2 text-amber-400"></i>รหัสแนะนำ (ถ้ามี)
                                </label>
                                <div class="bg-white/5 border border-amber-500/50 rounded-xl px-4 py-3 text-amber-300">
                                    REF123456
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Content --}}
                <div class="order-1 lg:order-2">
                    {{-- Step Badge --}}
                    <div class="inline-flex items-center gap-3 px-5 py-2.5 bg-purple-500/20 border border-purple-500/30 rounded-full mb-6">
                        <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center font-bold text-white text-lg">2</div>
                        <span class="text-purple-400 font-semibold text-lg">ขั้นตอนที่ 2</span>
                    </div>

                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        กรอกข้อมูลส่วนตัว
                    </h2>

                    <div class="space-y-4 text-lg text-slate-300">
                        <p class="flex items-start gap-3">
                            <i class="fas fa-user text-blue-400 mt-1"></i>
                            <span><strong class="text-white">ชื่อ-นามสกุล:</strong> กรอกชื่อจริงเพื่อใช้ในการยืนยันตัวตน</span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-envelope text-blue-400 mt-1"></i>
                            <span><strong class="text-white">อีเมล:</strong> ใช้สำหรับเข้าสู่ระบบและรับการแจ้งเตือน</span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-lock text-purple-400 mt-1"></i>
                            <span><strong class="text-white">รหัสผ่าน:</strong> ตั้งรหัสผ่านที่ปลอดภัย อย่างน้อย 8 ตัวอักษร</span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-gift text-amber-400 mt-1"></i>
                            <span><strong class="text-white">รหัสแนะนำ:</strong> ถ้ามีผู้แนะนำ กรอกรหัสที่ได้รับมา</span>
                        </p>
                    </div>

                    {{-- Warning Box --}}
                    <div class="mt-8 p-5 bg-red-500/10 border border-red-500/30 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-red-400 text-xl mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-red-300 mb-1">ข้อควรระวัง</p>
                                <p class="text-red-200/80 text-sm">กรุณากรอกอีเมลที่ใช้งานได้จริง เพราะจะใช้สำหรับรับรหัส OTP และรีเซ็ตรหัสผ่าน</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 4: ขั้นตอนที่ 3 - กดปุ่มสมัคร --}}
<div class="slide" data-topic="signup-web">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-slate-900 via-green-950 to-slate-900">
        <div class="max-w-5xl mx-auto text-center">
            {{-- Step Badge --}}
            <div class="inline-flex items-center gap-3 px-5 py-2.5 bg-green-500/20 border border-green-500/30 rounded-full mb-8">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center font-bold text-white text-lg">3</div>
                <span class="text-green-400 font-semibold text-lg">ขั้นตอนที่ 3</span>
            </div>

            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                กดปุ่ม "สมัครสมาชิกเลย!"
            </h2>

            <p class="text-xl text-slate-300 mb-12 max-w-2xl mx-auto">
                เมื่อกรอกข้อมูลครบถ้วนแล้ว กดปุ่มสมัครสมาชิกเพื่อสร้างบัญชีของคุณ
            </p>

            {{-- Big Button Visual --}}
            <div class="relative inline-block mb-12">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur-xl opacity-50 animate-pulse"></div>
                <button class="relative px-16 py-6 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 text-white text-2xl font-bold rounded-2xl shadow-2xl transform hover:scale-105 transition-all">
                    <i class="fas fa-rocket mr-3"></i>
                    สมัครสมาชิกเลย!
                </button>

                {{-- Click Indicator --}}
                <div class="absolute -bottom-8 left-1/2 -translate-x-1/2">
                    <div class="flex items-center gap-2 text-green-400">
                        <i class="fas fa-hand-pointer text-2xl animate-bounce"></i>
                        <span class="font-semibold">คลิกที่นี่!</span>
                    </div>
                </div>
            </div>

            {{-- Process Steps --}}
            <div class="flex flex-wrap justify-center gap-4 mt-16">
                <div class="px-6 py-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin text-blue-400"></i>
                        </div>
                        <span class="text-slate-300">ตรวจสอบข้อมูล</span>
                    </div>
                </div>
                <div class="text-slate-500 flex items-center">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="px-6 py-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-plus text-purple-400"></i>
                        </div>
                        <span class="text-slate-300">สร้างบัญชี</span>
                    </div>
                </div>
                <div class="text-slate-500 flex items-center">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="px-6 py-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <span class="text-slate-300">เข้าสู่ระบบอัตโนมัติ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 5: ขั้นตอนที่ 4 - สมัครสำเร็จ --}}
<div class="slide" data-topic="signup-web">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-green-900 via-emerald-900 to-teal-900">
        <div class="max-w-5xl mx-auto text-center">
            {{-- Success Icon --}}
            <div class="mb-8">
                <div class="w-32 h-32 mx-auto bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center shadow-2xl shadow-green-500/30 animate-bounce">
                    <i class="fas fa-check text-6xl text-white"></i>
                </div>
            </div>

            {{-- Step Badge --}}
            <div class="inline-flex items-center gap-3 px-5 py-2.5 bg-green-500/20 border border-green-500/30 rounded-full mb-6">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center font-bold text-white text-lg">4</div>
                <span class="text-green-400 font-semibold text-lg">ขั้นตอนที่ 4</span>
            </div>

            <h2 class="text-4xl md:text-6xl font-bold text-white mb-6">
                🎉 สมัครสมาชิกสำเร็จ!
            </h2>

            <p class="text-xl text-slate-300 mb-10 max-w-2xl mx-auto">
                ยินดีต้อนรับสู่ {{ $appName }}! ระบบจะพาคุณเข้าสู่หน้า Dashboard อัตโนมัติ
            </p>

            {{-- What's Next --}}
            <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div class="p-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                    <div class="w-14 h-14 mx-auto bg-blue-500/20 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-user-circle text-2xl text-blue-400"></i>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">อัพเดทโปรไฟล์</h4>
                    <p class="text-slate-400 text-sm">เพิ่มรูปโปรไฟล์และข้อมูลส่วนตัว</p>
                </div>

                <div class="p-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                    <div class="w-14 h-14 mx-auto bg-purple-500/20 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-share-alt text-2xl text-purple-400"></i>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">แชร์ลิงก์แนะนำ</h4>
                    <p class="text-slate-400 text-sm">เริ่มต้นสร้างทีมและรับคอมมิชชั่น</p>
                </div>

                <div class="p-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                    <div class="w-14 h-14 mx-auto bg-amber-500/20 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-graduation-cap text-2xl text-amber-400"></i>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">เรียนรู้ระบบ</h4>
                    <p class="text-slate-400 text-sm">ศึกษาคู่มือและวิธีใช้งานต่างๆ</p>
                </div>
            </div>

            {{-- CTA --}}
            <div class="mt-10">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-green-500/30 transition-all">
                    <i class="fas fa-rocket"></i>
                    สมัครสมาชิกเลย!
                </a>
            </div>
        </div>
    </div>
</div>


{{-- ===== SLIDES: สมัครผ่าน LINE (signup-line) ===== --}}

{{-- Slide 1: หน้าปก - สมัครผ่าน LINE --}}
<div class="slide" data-topic="signup-line">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-[#06C755] via-[#00B900] to-[#00D564]">
        <div class="max-w-5xl mx-auto text-center">
            {{-- LINE Logo --}}
            <div class="mb-8 animate-float">
                <div class="w-32 h-32 mx-auto bg-white rounded-3xl flex items-center justify-center shadow-2xl">
                    <svg class="w-20 h-20" viewBox="0 0 24 24" fill="#06C755">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                </div>
            </div>

            {{-- Title --}}
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6">
                วิธีสมัครสมาชิกผ่าน LINE
            </h1>

            <p class="text-2xl text-white/90 mb-8 max-w-3xl mx-auto">
                สะดวก รวดเร็ว ปลอดภัย สมัครด้วยบัญชี LINE ของคุณ
            </p>

            {{-- Quick Stats --}}
            <div class="flex flex-wrap justify-center gap-6 mb-10">
                <div class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-xl border border-white/30">
                    <i class="fas fa-bolt text-yellow-300 mr-2"></i>
                    <span class="text-white font-semibold">รวดเร็วที่สุด</span>
                </div>
                <div class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-xl border border-white/30">
                    <i class="fas fa-check-circle text-white mr-2"></i>
                    <span class="text-white font-semibold">3 ขั้นตอนง่ายๆ</span>
                </div>
                <div class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-xl border border-white/30">
                    <i class="fas fa-bell text-white mr-2"></i>
                    <span class="text-white font-semibold">รับแจ้งเตือนทาง LINE</span>
                </div>
            </div>

            {{-- CTA --}}
            <div class="flex justify-center gap-4">
                <span class="inline-flex items-center gap-2 text-white/80">
                    <i class="fas fa-arrow-right animate-pulse"></i>
                    กดลูกศรขวาเพื่อดูขั้นตอน
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Slide 2: ขั้นตอนที่ 1 - กดปุ่มสมัครด้วย LINE --}}
<div class="slide" data-topic="signup-line">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-slate-900 via-green-950 to-slate-900">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                {{-- Left: Content --}}
                <div>
                    {{-- Step Badge --}}
                    <div class="inline-flex items-center gap-3 px-5 py-2.5 bg-green-500/20 border border-green-500/30 rounded-full mb-6">
                        <div class="w-10 h-10 bg-[#06C755] rounded-full flex items-center justify-center font-bold text-white text-lg">1</div>
                        <span class="text-green-400 font-semibold text-lg">ขั้นตอนที่ 1</span>
                    </div>

                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        กดปุ่ม "สมัครด้วย LINE"
                    </h2>

                    <div class="space-y-4 text-lg text-slate-300">
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>ไปที่หน้าสมัครสมาชิก <code class="px-2 py-1 bg-slate-700 rounded text-blue-300">/register</code></span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>มองหาปุ่มสีเขียว <strong class="text-[#06C755]">"สมัครด้วย LINE"</strong></span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>คลิกปุ่มเพื่อเริ่มต้นกระบวนการ</span>
                        </p>
                    </div>

                    {{-- Info Box --}}
                    <div class="mt-8 p-5 bg-green-500/10 border border-green-500/30 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-green-400 text-xl mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-green-300 mb-1">ข้อดีของการสมัครผ่าน LINE</p>
                                <ul class="text-green-200/80 text-sm space-y-1">
                                    <li>• ไม่ต้องจำรหัสผ่าน</li>
                                    <li>• รับการแจ้งเตือนผ่าน LINE โดยตรง</li>
                                    <li>• สะดวกและปลอดภัย</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Visual --}}
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-3xl blur-3xl"></div>
                    <div class="relative bg-slate-800/50 backdrop-blur-sm border border-white/10 rounded-2xl p-8 shadow-2xl">
                        {{-- Divider Line --}}
                        <div class="flex items-center gap-4 mb-6">
                            <div class="flex-1 h-px bg-white/20"></div>
                            <span class="text-slate-400 text-sm">หรือ</span>
                            <div class="flex-1 h-px bg-white/20"></div>
                        </div>

                        {{-- LINE Button --}}
                        <div class="relative">
                            <button class="w-full flex items-center justify-center gap-3 px-6 py-4 bg-[#06C755] hover:bg-[#05B34D] text-white font-bold text-lg rounded-xl shadow-lg transition-all transform hover:scale-105">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                </svg>
                                สมัครด้วย LINE
                            </button>

                            {{-- Click Indicator --}}
                            <div class="absolute -right-4 top-1/2 -translate-y-1/2">
                                <div class="w-12 h-12 bg-yellow-400 rounded-full flex items-center justify-center animate-bounce shadow-lg">
                                    <i class="fas fa-hand-pointer text-slate-900 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <p class="text-center text-slate-400 text-sm mt-4">
                            <i class="fas fa-shield-alt text-green-400 mr-1"></i>
                            ปลอดภัยด้วย LINE OAuth 2.0
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 3: ขั้นตอนที่ 2 - ยืนยันตัวตนกับ LINE --}}
<div class="slide" data-topic="signup-line">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-slate-900 via-[#065f46] to-slate-900">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                {{-- Left: Visual --}}
                <div class="order-2 lg:order-1 relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-500/20 to-teal-500/20 rounded-3xl blur-3xl"></div>
                    <div class="relative bg-slate-800/50 backdrop-blur-sm border border-white/10 rounded-2xl p-8 shadow-2xl">
                        {{-- Mock LINE Login Screen --}}
                        <div class="bg-white rounded-xl p-6 text-center">
                            {{-- LINE Logo --}}
                            <div class="w-20 h-20 mx-auto bg-[#06C755] rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-12 h-12" viewBox="0 0 24 24" fill="white">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                </svg>
                            </div>

                            <h3 class="text-xl font-bold text-gray-800 mb-2">Login with LINE</h3>
                            <p class="text-gray-600 text-sm mb-6">{{ $appName }} ต้องการเข้าถึงข้อมูลของคุณ</p>

                            {{-- Permissions --}}
                            <div class="text-left bg-gray-100 rounded-lg p-4 mb-6">
                                <p class="text-sm text-gray-600 mb-2">สิทธิ์ที่ขอ:</p>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 text-sm">
                                        <i class="fas fa-check text-green-500"></i>
                                        <span class="text-gray-700">ชื่อโปรไฟล์</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm">
                                        <i class="fas fa-check text-green-500"></i>
                                        <span class="text-gray-700">รูปโปรไฟล์</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm">
                                        <i class="fas fa-check text-green-500"></i>
                                        <span class="text-gray-700">อีเมล (ถ้ามี)</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Buttons --}}
                            <div class="space-y-3">
                                <button class="w-full py-3 bg-[#06C755] text-white font-bold rounded-lg">
                                    อนุญาต
                                </button>
                                <button class="w-full py-3 bg-gray-200 text-gray-700 font-medium rounded-lg">
                                    ยกเลิก
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Content --}}
                <div class="order-1 lg:order-2">
                    {{-- Step Badge --}}
                    <div class="inline-flex items-center gap-3 px-5 py-2.5 bg-green-500/20 border border-green-500/30 rounded-full mb-6">
                        <div class="w-10 h-10 bg-[#06C755] rounded-full flex items-center justify-center font-bold text-white text-lg">2</div>
                        <span class="text-green-400 font-semibold text-lg">ขั้นตอนที่ 2</span>
                    </div>

                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        ยืนยันตัวตนกับ LINE
                    </h2>

                    <div class="space-y-4 text-lg text-slate-300">
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>ระบบจะเปิดหน้าต่าง LINE Login</span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>ล็อกอินด้วยบัญชี LINE ของคุณ (ถ้ายังไม่ได้ล็อกอิน)</span>
                        </p>
                        <p class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <span>กดปุ่ม <strong class="text-green-400">"อนุญาต"</strong> เพื่อให้ระบบเข้าถึงข้อมูล</span>
                        </p>
                    </div>

                    {{-- Security Note --}}
                    <div class="mt-8 p-5 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-shield-alt text-blue-400 text-xl mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-blue-300 mb-1">ความปลอดภัย</p>
                                <p class="text-blue-200/80 text-sm">เราจะเข้าถึงเฉพาะข้อมูลพื้นฐาน (ชื่อ, รูปโปรไฟล์, อีเมล) เท่านั้น ไม่สามารถอ่านแชทหรือโพสต์แทนคุณได้</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 4: ขั้นตอนที่ 3 - สมัครสำเร็จ --}}
<div class="slide" data-topic="signup-line">
    <div class="h-full flex items-center justify-center p-8 bg-gradient-to-br from-[#06C755] via-[#00B900] to-[#00D564]">
        <div class="max-w-5xl mx-auto text-center">
            {{-- Success Icon --}}
            <div class="mb-8">
                <div class="w-32 h-32 mx-auto bg-white rounded-full flex items-center justify-center shadow-2xl animate-bounce">
                    <i class="fas fa-check text-6xl text-[#06C755]"></i>
                </div>
            </div>

            {{-- Step Badge --}}
            <div class="inline-flex items-center gap-3 px-5 py-2.5 bg-white/20 border border-white/30 rounded-full mb-6">
                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center font-bold text-[#06C755] text-lg">3</div>
                <span class="text-white font-semibold text-lg">ขั้นตอนที่ 3</span>
            </div>

            <h2 class="text-4xl md:text-6xl font-bold text-white mb-6">
                🎉 สมัครสมาชิกสำเร็จ!
            </h2>

            <p class="text-xl text-white/90 mb-10 max-w-2xl mx-auto">
                ยินดีต้อนรับสู่ {{ $appName }}! บัญชีของคุณเชื่อมต่อกับ LINE เรียบร้อยแล้ว
            </p>

            {{-- Benefits --}}
            <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto mb-10">
                <div class="p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                    <div class="w-14 h-14 mx-auto bg-white/20 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-bell text-2xl text-white"></i>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">รับแจ้งเตือน</h4>
                    <p class="text-white/80 text-sm">รับการแจ้งเตือนสำคัญผ่าน LINE</p>
                </div>

                <div class="p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                    <div class="w-14 h-14 mx-auto bg-white/20 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-sign-in-alt text-2xl text-white"></i>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">เข้าสู่ระบบง่าย</h4>
                    <p class="text-white/80 text-sm">ล็อกอินด้วย LINE ได้ตลอด</p>
                </div>

                <div class="p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                    <div class="w-14 h-14 mx-auto bg-white/20 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-2xl text-white"></i>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">ติดต่อสะดวก</h4>
                    <p class="text-white/80 text-sm">แชทกับทีมซัพพอร์ตผ่าน LINE</p>
                </div>
            </div>

            {{-- CTA --}}
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-[#06C755] font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                    สมัครด้วย LINE เลย!
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-bold text-lg rounded-xl border border-white/30 hover:bg-white/20 transition-all">
                    <i class="fas fa-globe"></i>
                    หรือสมัครผ่านเว็บ
                </a>
            </div>
        </div>
    </div>
</div>

@extends('layouts.admin-v3')

@section('title', 'จัดการแผนคอมมิชชัน MLM')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 dark:from-violet-800 dark:via-purple-800 dark:to-fuchsia-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-3">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-layer-group text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">จัดการแผนคอมมิชชัน MLM</h1>
                    <p class="text-violet-100 text-lg mt-1">ระบบใช้แผนคอมมิชชัน Global บังคับทั้งระบบ</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Info Card --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-2xl shadow-xl p-8 border-2 border-blue-200 dark:border-blue-700">
        {{-- Decorative Elements --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-200 dark:bg-blue-800 rounded-full blur-3xl opacity-20 -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-200 dark:bg-indigo-800 rounded-full blur-3xl opacity-20 -ml-32 -mb-32"></div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row items-start gap-6">
                {{-- Icon --}}
                <div class="flex-shrink-0">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-2xl transform hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-info-circle text-white text-4xl"></i>
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-globe text-blue-600 dark:text-blue-400"></i>
                        ระบบใช้แผนคอมมิชชัน Global
                    </h2>

                    <div class="space-y-3 mb-6">
                        <div class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md">
                            <div class="flex-shrink-0 w-6 h-6 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mt-0.5">
                                <i class="fas fa-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-700 dark:text-gray-300">
                                    <strong class="text-gray-900 dark:text-white">แผนคอมมิชชันถูกกำหนดโดยตรงในโค้ด</strong> และ <span class="font-semibold text-blue-600 dark:text-blue-400">MlmGlobalSettings</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md">
                            <div class="flex-shrink-0 w-6 h-6 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mt-0.5">
                                <i class="fas fa-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-700 dark:text-gray-300">
                                    <strong class="text-gray-900 dark:text-white">ไม่ต้องจัดการแผนคอมมิชชันผ่าน UI</strong> - ระบบทำงานอัตโนมัติ
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md">
                            <div class="flex-shrink-0 w-6 h-6 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mt-0.5">
                                <i class="fas fa-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-700 dark:text-gray-300">
                                    <strong class="text-gray-900 dark:text-white">สมาชิกทุกคนใช้แผนคอมมิชชันเดียวกัน</strong> - เพื่อความเป็นธรรมและความเท่าเทียม
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Note Section --}}
                    <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 border-l-4 border-amber-500 dark:border-amber-400 rounded-xl p-5 mb-6">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-lightbulb text-amber-600 dark:text-amber-400 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    หมายเหตุสำคัญ
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                                    หากต้องการจัดการ <strong>แพคเกจสมาชิก</strong> (เช่น Bronze, Silver, Gold, Platinum)
                                    หรือปรับแต่งระดับ Rank และสิทธิประโยชน์ กรุณาไปที่เมนู
                                    <span class="font-semibold text-amber-700 dark:text-amber-300">MLM Settings</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- CTA Button --}}
                    <a href="{{ route('admin.mlm.settings.index') }}"
                       class="inline-flex items-center gap-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-500 dark:hover:from-blue-600 dark:hover:to-indigo-600 text-white px-8 py-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105 font-semibold">
                        <i class="fas fa-cog text-2xl"></i>
                        <div class="text-left">
                            <div class="text-sm opacity-90">ตั้งค่า</div>
                            <div class="text-lg">MLM Global Settings</div>
                        </div>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Access Cards --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-bolt text-yellow-500"></i>
            การดำเนินการด่วน
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Global Settings Card --}}
            <a href="{{ route('admin.mlm.settings.index') }}"
               class="group glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-2xl transition-all duration-300 border-l-4 border-blue-500 dark:border-blue-400 transform hover:scale-105">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-cog text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-1">Global Settings</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ตั้งค่าระบบ MLM</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 group-hover:translate-x-2 transition-all"></i>
                </div>
            </a>

            {{-- Members Card --}}
            <a href="{{ route('admin.mlm.members.index') }}"
               class="group glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-2xl transition-all duration-300 border-l-4 border-emerald-500 dark:border-emerald-400 transform hover:scale-105">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-1">สมาชิก MLM</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">จัดการสมาชิก</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 group-hover:translate-x-2 transition-all"></i>
                </div>
            </a>

            {{-- Reports Card --}}
            <a href="{{ route('admin.mlm.reports.dashboard') }}"
               class="group glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-2xl transition-all duration-300 border-l-4 border-purple-500 dark:border-purple-400 transform hover:scale-105">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-1">รายงาน</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ดูสถิติและรายงาน</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 group-hover:translate-x-2 transition-all"></i>
                </div>
            </a>
        </div>
    </div>

    {{-- Additional Info Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- System Architecture --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <div class="bg-gradient-to-r from-cyan-500 to-blue-600 p-2 rounded-xl">
                    <i class="fas fa-sitemap text-white"></i>
                </div>
                สถาปัตยกรรมระบบ
            </h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <i class="fas fa-code-branch text-cyan-600 dark:text-cyan-400 mt-1"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>Binary System:</strong> โครงสร้าง 2 ขา (Left/Right)
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-layer-group text-cyan-600 dark:text-cyan-400 mt-1"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>Unilevel:</strong> รองรับสูงสุด 10 ระดับ
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-user-plus text-cyan-600 dark:text-cyan-400 mt-1"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>Direct Referral:</strong> โบนัสผู้แนะนำโดยตรง
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-handshake text-cyan-600 dark:text-cyan-400 mt-1"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>Matching Bonus:</strong> โบนัสการจับคู่
                    </p>
                </div>
            </div>
        </div>

        {{-- Features --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <div class="bg-gradient-to-r from-pink-500 to-rose-600 p-2 rounded-xl">
                    <i class="fas fa-star text-white"></i>
                </div>
                คุณสมบัติหลัก
            </h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-pink-600 dark:text-pink-400 mt-1"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        คำนวณคอมมิชชันแบบ Real-time
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-pink-600 dark:text-pink-400 mt-1"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        รองรับ Auto Placement 4 แบบ
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-pink-600 dark:text-pink-400 mt-1"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        ระบบ PV (Point Value) ที่ยืดหยุ่น
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-pink-600 dark:text-pink-400 mt-1"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        การจัดการ Rank และสิทธิประโยชน์
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Theme Customizer CSS Variables Integration */
.glass-fusion {
    background: rgba(255, 255, 255, var(--glass-opacity, 0.15)) !important;
    backdrop-filter: blur(var(--glass-blur, 12px)) saturate(var(--backdrop-saturate, 100%)) !important;
    border-radius: var(--card-roundness, 16px) !important;
}

.dark .glass-fusion {
    background: rgba(31, 41, 55, var(--glass-opacity, 0.15)) !important;
}

/* Card roundness */
.rounded-2xl {
    border-radius: var(--card-roundness, 16px) !important;
}

.rounded-xl {
    border-radius: calc(var(--button-roundness, 12px) * 1.33) !important;
}

.rounded-lg {
    border-radius: var(--button-roundness, 12px) !important;
}

/* Hover scale effect */
.hover\:scale-105:hover {
    transform: scale(var(--hover-scale, 1.05)) !important;
}

/* Animation speed */
.transition-all,
.transition {
    transition-duration: var(--animation-speed, 500ms) !important;
}

/* Shadow intensity */
.shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.5)) !important;
}

.shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.2)),
                0 10px 10px -5px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.08)) !important;
}

.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.2)),
                0 4px 6px -2px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.1)) !important;
}
</style>
@endsection

@extends('layouts.admin-v3')

@section('title', 'ตัวอย่างการจัดวางสมาชิก MLM')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-pink-600 via-rose-600 to-fuchsia-600 dark:from-pink-800 dark:via-rose-800 dark:to-fuchsia-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-sitemap text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">ตัวอย่างการจัดวางสมาชิก MLM</h1>
                        <p class="text-rose-100 text-lg mt-1">ดูแบบอนิเมชั่นแสดงวิธีการจัดวางสมาชิกใหม่แบบต่างๆ</p>
                    </div>
                </div>

                {{-- Quick Action --}}
                <a href="{{ route('admin.mlm.settings.index') }}"
                   class="glass-fusion hover:bg-white/20 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg">
                    <i class="fas fa-cog"></i>
                    ตั้งค่า MLM
                </a>
            </div>
        </div>
    </div>

    {{-- Info Alert --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-info-circle text-white text-2xl"></i>
                </div>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">เกี่ยวกับการจัดวางอัตโนมัติ (Auto Placement)</h2>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-6">
                    การจัดวางอัตโนมัติช่วยให้ระบบกำหนดตำแหน่งของสมาชิกใหม่โดยอัตโนมัติตามกลยุทธ์ที่เลือก
                    ช่วยสร้างความสมดุลและเพิ่มโอกาสในการสร้างคอมมิชชั่น
                </p>

                {{-- Strategy Cards Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {{-- Left First --}}
                    <div class="group bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-xl p-4 border-2 border-purple-200 dark:border-purple-700 hover:border-purple-400 dark:hover:border-purple-500 transition-all transform hover:scale-105">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                                <i class="fas fa-arrow-left text-white"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Left First</h3>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            เหมาะสำหรับผู้ที่ต้องการสร้างขาซ้ายให้แข็งแรงก่อน
                        </p>
                    </div>

                    {{-- Right First --}}
                    <div class="group bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl p-4 border-2 border-blue-200 dark:border-blue-700 hover:border-blue-400 dark:hover:border-blue-500 transition-all transform hover:scale-105">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg">
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Right First</h3>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            เหมาะสำหรับผู้ที่ต้องการสร้างขาขวาให้แข็งแรงก่อน
                        </p>
                    </div>

                    {{-- Fill Level --}}
                    <div class="group bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-xl p-4 border-2 border-green-200 dark:border-green-700 hover:border-green-400 dark:hover:border-green-500 transition-all transform hover:scale-105">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center shadow-lg">
                                <i class="fas fa-layer-group text-white"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Fill Level</h3>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            เติมเต็มทีละชั้น สร้างโครงสร้างที่เป็นระเบียบ
                        </p>
                    </div>

                    {{-- Weak Leg --}}
                    <div class="group bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 rounded-xl p-4 border-2 border-orange-200 dark:border-orange-700 hover:border-orange-400 dark:hover:border-orange-500 transition-all transform hover:scale-105">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center shadow-lg">
                                <i class="fas fa-balance-scale text-white"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Weak Leg</h3>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            วางสมาชิกในขาที่อ่อนกว่าเพื่อสร้างความสมดุล
                        </p>
                    </div>

                    {{-- Balanced --}}
                    <div class="group bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-900/30 dark:to-teal-800/30 rounded-xl p-4 border-2 border-teal-200 dark:border-teal-700 hover:border-teal-400 dark:hover:border-teal-500 transition-all transform hover:scale-105">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg flex items-center justify-center shadow-lg">
                                <i class="fas fa-equals text-white"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Balanced</h3>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            สลับซ้าย-ขวาเพื่อความสมดุลสูงสุด
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Placement Animation Examples --}}
    <div class="space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-play-circle text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">แอนิเมชั่นแสดงการจัดวาง</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">ดูตัวอย่างการจัดวางแต่ละแบบ</p>
            </div>
        </div>

        {{-- Left First --}}
        <x-mlm.placement-animation type="left_first" />

        {{-- Right First --}}
        <x-mlm.placement-animation type="right_first" />

        {{-- Fill Level --}}
        <x-mlm.placement-animation type="fill_level" />

        {{-- Weak Leg --}}
        <x-mlm.placement-animation type="weak_leg" />

        {{-- Balanced --}}
        <x-mlm.placement-animation type="balanced" />
    </div>

    {{-- Settings Note --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg animate-pulse">
                    <i class="fas fa-cog text-white text-2xl"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">การตั้งค่ากลยุทธ์การจัดวาง</h3>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                    คุณสามารถกำหนดกลยุทธ์การจัดวางเริ่มต้นได้ที่หน้าตั้งค่าระบบ MLM ในส่วน "Placement Strategies"
                    เลือกกลยุทธ์ที่เหมาะสมกับโครงสร้างธุรกิจของคุณ
                </p>
                <a href="{{ route('admin.mlm.settings.index') }}"
                   class="inline-flex items-center gap-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 dark:from-purple-500 dark:to-pink-500 dark:hover:from-purple-600 dark:hover:to-pink-600 text-white px-8 py-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105 font-semibold">
                    <i class="fas fa-cog text-2xl"></i>
                    <div class="text-left">
                        <div class="text-sm opacity-90">ไปที่</div>
                        <div class="text-lg">ตั้งค่าระบบ MLM</div>
                    </div>
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Benefits Section --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Automatic Placement --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-pink-500 dark:border-pink-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-magic text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">อัตโนมัติ 100%</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ระบบจะจัดวางสมาชิกใหม่โดยอัตโนมัติตามกลยุทธ์ที่คุณเลือก ไม่ต้องกังวลเรื่องการจัดวางด้วยตนเอง
            </p>
        </div>

        {{-- Balanced Growth --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-rose-500 dark:border-rose-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-rose-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">เติบโตอย่างสมดุล</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                กลยุทธ์ที่เหมาะสมช่วยสร้างโครงสร้างที่สมดุล เพิ่มโอกาสในการได้รับคอมมิชชั่น
            </p>
        </div>

        {{-- Maximize Earnings --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-fuchsia-500 dark:border-fuchsia-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-coins text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">เพิ่มรายได้สูงสุด</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                โครงสร้างที่ดีช่วยเพิ่มโอกาสในการจับคู่และสร้างคอมมิชชั่นได้มากขึ้น
            </p>
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

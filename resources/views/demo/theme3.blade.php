@extends('demo.layout')

@section('title', 'Theme 3: Neumorphism')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Theme Header --}}
    <div class="text-center mb-12 animate-fade-in">
        <h1 class="text-5xl md:text-6xl font-bold mb-4 text-gray-800 dark:text-gray-100">
            Neumorphism Theme
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400">
            เน้น Soft Shadow มิติ 3D สไตล์ Minimal & Elegant
        </p>
    </div>

    {{-- Stats Cards (Neumorphic) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="neu-light dark:neu-dark rounded-2xl p-6 transition-all duration-300 cursor-pointer animate-scale-in">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-2xl"></i>
                </div>
                <span class="px-3 py-1 bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400 rounded-full text-xs font-bold">
                    +12.5%
                </span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">12,584</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">ผู้ใช้งานทั้งหมด</p>
        </div>

        <div class="neu-light dark:neu-dark rounded-2xl p-6 transition-all duration-300 cursor-pointer animate-scale-in" style="animation-delay: 100ms;">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-green-600 dark:text-green-400 text-2xl"></i>
                </div>
                <span class="px-3 py-1 bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400 rounded-full text-xs font-bold">
                    +8.3%
                </span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">฿145,678</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">ยอดขายวันนี้</p>
        </div>

        <div class="neu-light dark:neu-dark rounded-2xl p-6 transition-all duration-300 cursor-pointer animate-scale-in" style="animation-delay: 200ms;">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                    <i class="fas fa-file-invoice text-purple-600 dark:text-purple-400 text-2xl"></i>
                </div>
                <span class="px-3 py-1 bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400 rounded-full text-xs font-bold">
                    +15.2%
                </span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">389</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">คำสั่งซื้อใหม่</p>
        </div>

        <div class="neu-light dark:neu-dark rounded-2xl p-6 transition-all duration-300 cursor-pointer animate-scale-in" style="animation-delay: 300ms;">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-orange-600 dark:text-orange-400 text-2xl"></i>
                </div>
                <span class="px-3 py-1 bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400 rounded-full text-xs font-bold">
                    +22.7%
                </span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">฿2.5M</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">รายได้รวม</p>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Chart Card (Neumorphic) --}}
        <div class="lg:col-span-2 animate-slide-up">
            <div class="neu-light dark:neu-dark rounded-2xl overflow-hidden">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-300 dark:border-gray-600">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">สถิติการขาย</h2>
                    </div>
                </div>

                {{-- Chart --}}
                <div class="p-6">
                    <div class="h-64 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-xl">
                        <div class="text-center">
                            <i class="fas fa-chart-area text-6xl text-gray-400 dark:text-gray-500 mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400">Neumorphic Chart</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity Card (Neumorphic) --}}
        <div class="animate-slide-up" style="animation-delay: 200ms;">
            <div class="neu-light dark:neu-dark rounded-2xl overflow-hidden h-full">
                <div class="px-6 py-4 border-b border-gray-300 dark:border-gray-600">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-purple-600 dark:text-purple-400 text-xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">กิจกรรมล่าสุด</h2>
                    </div>
                </div>

                <div class="p-6 space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer hover:shadow-inner transition-shadow">
                        <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shopping-cart text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">คำสั่งซื้อใหม่ #12345</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">5 นาทีที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer hover:shadow-inner transition-shadow">
                        <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user-plus text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">สมาชิกใหม่ลงทะเบียน</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">15 นาทีที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer hover:shadow-inner transition-shadow">
                        <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">สินค้าใกล้หมด</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">1 ชั่วโมงที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer hover:shadow-inner transition-shadow">
                        <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-star text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">รีวิวใหม่ 5 ดาว</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">2 ชั่วโมงที่แล้ว</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Feature Cards (Neumorphic) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="neu-light dark:neu-dark rounded-2xl p-8 transition-all duration-300 animate-scale-in">
            <div class="w-16 h-16 neu-light dark:neu-dark rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-cube text-blue-600 dark:text-blue-400 text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">มิติ 3D</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                ใช้ soft shadow สร้างความรู้สึก 3 มิติ นูนและเว้าที่สมจริง
            </p>
            <button class="w-full px-6 py-3 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all">
                เรียนรู้เพิ่มเติม
            </button>
        </div>

        <div class="neu-light dark:neu-dark rounded-2xl p-8 transition-all duration-300 animate-scale-in" style="animation-delay: 100ms;">
            <div class="w-16 h-16 neu-light dark:neu-dark rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-palette text-purple-600 dark:text-purple-400 text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Soft & Minimal</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                ดีไซน์เรียบง่าย เน้นรายละเอียดที่ shadow และความลึก
            </p>
            <button class="w-full px-6 py-3 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all">
                ดูตัวอย่าง
            </button>
        </div>

        <div class="neu-light dark:neu-dark rounded-2xl p-8 transition-all duration-300 animate-scale-in" style="animation-delay: 200ms;">
            <div class="w-16 h-16 neu-light dark:neu-dark rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-hand-pointer text-green-600 dark:text-green-400 text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Interactive</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Hover effects ที่นุ่มนวล สร้างประสบการณ์การใช้งานที่ดี
            </p>
            <button class="w-full px-6 py-3 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all">
                ทดลองใช้
            </button>
        </div>
    </div>

    {{-- Interactive Controls --}}
    <div class="max-w-2xl mx-auto space-y-6 animate-fade-in">
        <div class="neu-light dark:neu-dark rounded-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">ควบคุมระบบ</h2>

            {{-- Toggle Switches --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">เปิดใช้งานระบบ</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">เปิด/ปิด ระบบทั้งหมด</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-16 h-8 neu-light dark:neu-dark rounded-full peer-checked:bg-blue-600 transition-all relative">
                            <div class="absolute top-1 left-1 bg-white w-6 h-6 rounded-full shadow-md peer-checked:translate-x-8 transition-transform"></div>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">การแจ้งเตือน</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">รับการแจ้งเตือนทางอีเมล</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-16 h-8 neu-light dark:neu-dark rounded-full peer-checked:bg-green-600 transition-all relative">
                            <div class="absolute top-1 left-1 bg-white w-6 h-6 rounded-full shadow-md peer-checked:translate-x-8 transition-transform"></div>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">โหมดบำรุงรักษา</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ปิดระบบชั่วคราว</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-16 h-8 neu-light dark:neu-dark rounded-full peer-checked:bg-orange-600 transition-all relative">
                            <div class="absolute top-1 left-1 bg-white w-6 h-6 rounded-full shadow-md peer-checked:translate-x-8 transition-transform"></div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="grid grid-cols-2 gap-4 mt-6">
                <button class="px-6 py-3 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all">
                    <i class="fas fa-save mr-2"></i>
                    บันทึก
                </button>
                <button class="px-6 py-3 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all">
                    <i class="fas fa-undo mr-2"></i>
                    รีเซ็ต
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
console.log('Theme 3: Neumorphism loaded');
</script>
@endpush

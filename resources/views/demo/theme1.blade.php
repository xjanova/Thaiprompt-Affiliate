@extends('demo.layout')

@section('title', 'Theme 1: Premium Gradient')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Theme Header --}}
    <div class="text-center mb-12 animate-fade-in">
        <h1 class="text-5xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">
            Premium Gradient Theme
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400">
            เน้นสีสันสดใส Gradient ที่สวยงาม มีพลัง Premium Feel
        </p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" x-data="{
        stats: [
            {
                id: 1,
                label: 'ผู้ใช้งานทั้งหมด',
                value: '12,584',
                change: '+12.5%',
                icon: 'fa-users',
                gradient: 'from-blue-500 to-cyan-600',
                iconBg: 'bg-gradient-to-br from-blue-500 to-cyan-600'
            },
            {
                id: 2,
                label: 'ยอดขายวันนี้',
                value: '฿145,678',
                change: '+8.3%',
                icon: 'fa-shopping-cart',
                gradient: 'from-green-500 to-emerald-600',
                iconBg: 'bg-gradient-to-br from-green-500 to-emerald-600'
            },
            {
                id: 3,
                label: 'คำสั่งซื้อใหม่',
                value: '389',
                change: '+15.2%',
                icon: 'fa-file-invoice',
                gradient: 'from-purple-500 to-pink-600',
                iconBg: 'bg-gradient-to-br from-purple-500 to-pink-600'
            },
            {
                id: 4,
                label: 'รายได้รวม',
                value: '฿2.5M',
                change: '+22.7%',
                icon: 'fa-dollar-sign',
                gradient: 'from-orange-500 to-red-600',
                iconBg: 'bg-gradient-to-br from-orange-500 to-red-600'
            }
        ]
    }">
        <template x-for="stat in stats" :key="stat.id">
            <div class="group perspective-1000 animate-scale-in" :style="`animation-delay: ${stat.id * 100}ms`">
                <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity bg-gradient-to-br"
                         :class="stat.gradient"></div>

                    {{-- Card Content --}}
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div :class="stat.iconBg"
                                 class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform">
                                <i :class="'fas ' + stat.icon" class="text-white text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full text-xs font-bold"
                                  x-text="stat.change"></span>
                        </div>

                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1"
                            x-text="stat.value"></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400"
                           x-text="stat.label"></p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Chart Card --}}
        <div class="lg:col-span-2 animate-slide-up">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                        <h2 class="text-xl font-bold text-white">สถิติการขาย</h2>
                    </div>
                </div>

                {{-- Chart Placeholder --}}
                <div class="p-6">
                    <div class="h-64 flex items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-600 rounded-xl">
                        <div class="text-center">
                            <i class="fas fa-chart-bar text-6xl text-blue-500 dark:text-blue-400 mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400">Chart.js จะแสดงที่นี่</p>
                        </div>
                    </div>

                    {{-- Legend --}}
                    <div class="flex items-center justify-center gap-6 mt-6">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">ยอดขาย</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">รายได้</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-gradient-to-r from-orange-500 to-red-500 rounded-full"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">ค่าใช้จ่าย</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity Card --}}
        <div class="animate-slide-up" style="animation-delay: 200ms;">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-history text-2xl text-white"></i>
                        <h2 class="text-xl font-bold text-white">กิจกรรมล่าสุด</h2>
                    </div>
                </div>

                {{-- Activities --}}
                <div class="p-6 space-y-4" x-data="{
                    activities: [
                        { id: 1, title: 'มีคำสั่งซื้อใหม่ #12345', time: '5 นาทีที่แล้ว', icon: 'fa-shopping-cart', color: 'green' },
                        { id: 2, title: 'ผู้ใช้ใหม่ลงทะเบียน', time: '15 นาทีที่แล้ว', icon: 'fa-user-plus', color: 'blue' },
                        { id: 3, title: 'สินค้าใกล้หมด: Product X', time: '1 ชั่วโมงที่แล้ว', icon: 'fa-exclamation-triangle', color: 'yellow' },
                        { id: 4, title: 'รีวิวใหม่จากลูกค้า', time: '2 ชั่วโมงที่แล้ว', icon: 'fa-star', color: 'purple' },
                        { id: 5, title: 'ชำระเงินสำเร็จ #12340', time: '3 ชั่วโมงที่แล้ว', icon: 'fa-check-circle', color: 'green' }
                    ]
                }">
                    <template x-for="activity in activities" :key="activity.id">
                        <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
                                 :class="{
                                     'bg-green-500': activity.color === 'green',
                                     'bg-blue-500': activity.color === 'blue',
                                     'bg-yellow-500': activity.color === 'yellow',
                                     'bg-purple-500': activity.color === 'purple'
                                 }">
                                <i :class="'fas ' + activity.icon" class="text-white"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                   x-text="activity.title"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"
                                   x-text="activity.time"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Feature Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Card 1 --}}
        <div class="group perspective-1000 animate-scale-in">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                {{-- Glow --}}
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>

                {{-- Card --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-rocket text-white text-3xl"></i>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        Performance สูง
                    </h3>

                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        เร็ว เบา ทันสมัย ด้วย Alpine.js และ Tailwind CSS
                    </p>

                    <button class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-xl hover:shadow-lg transform hover:scale-105 transition-all w-full">
                        <i class="fas fa-arrow-right mr-2"></i>
                        เรียนรู้เพิ่มเติม
                    </button>
                </div>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="group perspective-1000 animate-scale-in" style="animation-delay: 100ms;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-paint-brush text-white text-3xl"></i>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        UI สวยงาม
                    </h3>

                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Modern design พร้อม 3D effects และ smooth animations
                    </p>

                    <button class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:shadow-lg transform hover:scale-105 transition-all w-full">
                        <i class="fas fa-arrow-right mr-2"></i>
                        ดูตัวอย่าง
                    </button>
                </div>
            </div>
        </div>

        {{-- Card 3 --}}
        <div class="group perspective-1000 animate-scale-in" style="animation-delay: 200ms;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-mobile-alt text-white text-3xl"></i>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        Responsive
                    </h3>

                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ทำงานได้ลื่นไหลบนทุกอุปกรณ์ Mobile-first design
                    </p>

                    <button class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-lg transform hover:scale-105 transition-all w-full">
                        <i class="fas fa-arrow-right mr-2"></i>
                        ทดสอบเลย
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- CTA Section --}}
    <div class="relative overflow-hidden rounded-3xl animate-fade-in">
        {{-- Background Gradient --}}
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500 via-purple-600 to-pink-500"></div>

        {{-- Pattern Overlay --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        {{-- Content --}}
        <div class="relative z-10 text-center py-20 px-6">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                พร้อมเริ่มต้นแล้วหรือยัง?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                เริ่มใช้งาน Theme นี้ได้เลยวันนี้ พร้อมเครื่องมือครบครันสำหรับสร้าง UI ที่สวยงาม
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button class="group px-8 py-4 bg-white text-purple-600 rounded-xl font-bold hover:shadow-2xl transform hover:scale-105 transition-all">
                    <span class="flex items-center justify-center gap-2">
                        <i class="fas fa-download"></i>
                        <span>ดาวน์โหลด Theme</span>
                    </span>
                </button>
                <button class="px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white text-white rounded-xl font-bold hover:bg-white/20 transition-all">
                    <span class="flex items-center justify-center gap-2">
                        <i class="fas fa-book"></i>
                        <span>อ่านเอกสาร</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
console.log('Theme 1: Premium Gradient loaded');
</script>
@endpush

<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Neumorphism Theme</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+Thai:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full bg-gray-200 dark:bg-gray-800 font-sans" x-data="{
    sidebarOpen: true,
    profileOpen: false,
    darkMode: localStorage.getItem('darkMode') === 'true' || false
}" x-init="$watch('darkMode', val => {
    localStorage.setItem('darkMode', val);
    document.documentElement.classList.toggle('dark', val);
}); document.documentElement.classList.toggle('dark', darkMode);">

    <div class="flex h-full">

        {{-- Sidebar (Neumorphic) --}}
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'"
               class="neu-light dark:neu-dark transition-all duration-300 flex flex-col">

            {{-- Logo --}}
            <div class="h-16 flex items-center justify-between px-4 border-b border-gray-300 dark:border-gray-600">
                <div class="flex items-center gap-3" x-show="sidebarOpen">
                    <div class="w-10 h-10 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                        <i class="fas fa-cube text-gray-700 dark:text-gray-300"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-900 dark:text-white">TP-Affiliate</h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Theme 3</p>
                    </div>
                </div>
                <button @click="sidebarOpen = !sidebarOpen"
                        class="p-2 neu-light dark:neu-dark rounded-lg transition-all">
                    <i class="fas fa-bars text-gray-600 dark:text-gray-400"></i>
                </button>
            </div>

            {{-- Menu --}}
            <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl bg-blue-500 text-white shadow-lg transition-all">
                    <i class="fas fa-home w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">แดชบอร์ด</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl neu-light dark:neu-dark text-gray-700 dark:text-gray-300 transition-all hover:shadow-inner">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">ผู้ใช้งาน</span>
                    <span x-show="sidebarOpen" class="ml-auto px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full">12</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl neu-light dark:neu-dark text-gray-700 dark:text-gray-300 transition-all hover:shadow-inner">
                    <i class="fas fa-shopping-cart w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">คำสั่งซื้อ</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl neu-light dark:neu-dark text-gray-700 dark:text-gray-300 transition-all hover:shadow-inner">
                    <i class="fas fa-box w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">สินค้า</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl neu-light dark:neu-dark text-gray-700 dark:text-gray-300 transition-all hover:shadow-inner">
                    <i class="fas fa-chart-line w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">รายงาน</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl neu-light dark:neu-dark text-gray-700 dark:text-gray-300 transition-all hover:shadow-inner">
                    <i class="fas fa-wallet w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">กระเป๋าเงิน</span>
                </a>

                <div x-show="sidebarOpen" class="border-t border-gray-300 dark:border-gray-600 my-4"></div>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl neu-light dark:neu-dark text-gray-700 dark:text-gray-300 transition-all hover:shadow-inner">
                    <i class="fas fa-cog w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">ตั้งค่า</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl neu-light dark:neu-dark text-gray-700 dark:text-gray-300 transition-all hover:shadow-inner">
                    <i class="fas fa-question-circle w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">ช่วยเหลือ</span>
                </a>
            </nav>

            {{-- User Profile --}}
            <div class="p-4 border-t border-gray-300 dark:border-gray-600">
                <div class="flex items-center gap-3 p-2 rounded-xl neu-light dark:neu-dark transition-all cursor-pointer hover:shadow-inner">
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=64748b&color=fff"
                         class="w-10 h-10 rounded-xl" alt="Profile">
                    <div x-show="sidebarOpen" class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white text-sm truncate">Admin User</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">admin@tp.com</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Top Navbar (Neumorphic) --}}
            <header class="h-16 neu-light dark:neu-dark border-b border-gray-300 dark:border-gray-600 flex items-center justify-between px-6">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">แดชบอร์ด</h2>
                    <span class="px-3 py-1 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 text-xs font-bold rounded-full">
                        Neumorphism
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    {{-- Search --}}
                    <div class="relative hidden md:block">
                        <input type="text" placeholder="ค้นหา..."
                               class="w-64 px-4 py-2 pl-10 neu-light dark:neu-dark rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:shadow-inner focus:outline-none transition-all">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>

                    {{-- Notifications --}}
                    <button class="relative p-2 neu-light dark:neu-dark rounded-lg transition-all hover:shadow-inner">
                        <i class="fas fa-bell text-gray-600 dark:text-gray-400"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    {{-- Dark Mode Toggle --}}
                    <button @click="darkMode = !darkMode"
                            class="p-2 neu-light dark:neu-dark rounded-lg transition-all hover:shadow-inner">
                        <i class="fas fa-moon text-gray-600 dark:text-gray-400" x-show="!darkMode"></i>
                        <i class="fas fa-sun text-gray-600 dark:text-gray-400" x-show="darkMode" style="display: none;"></i>
                    </button>

                    {{-- Profile Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 p-2 neu-light dark:neu-dark rounded-lg transition-all hover:shadow-inner">
                            <img src="https://ui-avatars.com/api/?name=Admin+User&background=64748b&color=fff"
                                 class="w-8 h-8 rounded-lg" alt="Profile">
                            <i class="fas fa-chevron-down text-gray-600 dark:text-gray-400 text-sm"></i>
                        </button>

                        <div x-show="open"
                             @click.outside="open = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 neu-light dark:neu-dark rounded-xl shadow-lg py-2 z-50"
                             style="display: none;">
                            <a href="#" class="flex items-center gap-3 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mx-2">
                                <i class="fas fa-user w-4"></i>
                                <span>โปรไฟล์</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mx-2">
                                <i class="fas fa-cog w-4"></i>
                                <span>ตั้งค่า</span>
                            </a>
                            <div class="border-t border-gray-300 dark:border-gray-600 my-2"></div>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mx-2">
                                <i class="fas fa-sign-out-alt w-4"></i>
                                <span>ออกจากระบบ</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-6">

                {{-- Stats Cards (Neumorphic) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="neu-light dark:neu-dark rounded-2xl p-6 transition-all duration-300 cursor-pointer hover:shadow-inner">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400 rounded-lg text-xs font-bold">
                                +12.5%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">12,584</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ผู้ใช้งานทั้งหมด</p>
                    </div>

                    <div class="neu-light dark:neu-dark rounded-2xl p-6 transition-all duration-300 cursor-pointer hover:shadow-inner">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-green-600 dark:text-green-400 text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400 rounded-lg text-xs font-bold">
                                +8.3%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">฿145,678</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ยอดขายวันนี้</p>
                    </div>

                    <div class="neu-light dark:neu-dark rounded-2xl p-6 transition-all duration-300 cursor-pointer hover:shadow-inner">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-purple-600 dark:text-purple-400 text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400 rounded-lg text-xs font-bold">
                                +15.2%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">389</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">คำสั่งซื้อใหม่</p>
                    </div>

                    <div class="neu-light dark:neu-dark rounded-2xl p-6 transition-all duration-300 cursor-pointer hover:shadow-inner">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 neu-light dark:neu-dark rounded-xl flex items-center justify-center">
                                <i class="fas fa-chart-line text-orange-600 dark:text-orange-400 text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400 rounded-lg text-xs font-bold">
                                +22.7%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">฿2.5M</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">รายได้รวม</p>
                    </div>
                </div>

                {{-- Charts & Activity Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                    {{-- Chart (Neumorphic) --}}
                    <div class="lg:col-span-2 neu-light dark:neu-dark rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-300 dark:border-gray-600">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-area text-blue-600 dark:text-blue-400 text-xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">สถิติการขาย</h3>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="h-64 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-xl">
                                <div class="text-center">
                                    <i class="fas fa-chart-bar text-6xl text-gray-400 dark:text-gray-500 mb-4"></i>
                                    <p class="text-gray-600 dark:text-gray-400">Neumorphic Chart</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Activity List (Neumorphic) --}}
                    <div class="neu-light dark:neu-dark rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-300 dark:border-gray-600">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clock text-purple-600 dark:text-purple-400 text-xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">กิจกรรมล่าสุด</h3>
                            </div>
                        </div>
                        <div class="p-4 space-y-3">
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

                            <div class="flex items-start gap-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer hover:shadow-inner transition-shadow">
                                <div class="w-10 h-10 neu-light dark:neu-dark rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-ban text-red-600 dark:text-red-400"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">คำสั่งซื้อถูกยกเลิก</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">3 ชั่วโมงที่แล้ว</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Data Table (Neumorphic) --}}
                <div class="neu-light dark:neu-dark rounded-2xl overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-300 dark:border-gray-600 flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-table"></i>
                            รายการคำสั่งซื้อล่าสุด
                        </h3>
                        <div class="flex items-center gap-2">
                            <button class="px-4 py-2 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-lg hover:shadow-inner transition-all">
                                <i class="fas fa-plus mr-2"></i>
                                เพิ่มใหม่
                            </button>
                            <button class="px-4 py-2 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-lg hover:shadow-inner transition-all">
                                <i class="fas fa-download mr-2"></i>
                                Export
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        <input type="checkbox" class="rounded border-gray-300">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        รหัสคำสั่งซื้อ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        ลูกค้า
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        สินค้า
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        ยอดรวม
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        สถานะ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        จัดการ
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-300 dark:divide-gray-600">
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-gray-300">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">#ORD-12345</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">วันนี้ 14:30</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=สมชาย+ใจดี&background=3b82f6&color=fff"
                                                 class="w-10 h-10 rounded-lg" alt="Customer">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">สมชาย ใจดี</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">somchai@email.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white">iPhone 15 Pro Max</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">x1</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">฿45,900</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-500/10 dark:bg-green-400/10 text-green-600 dark:text-green-400">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            สำเร็จ
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            <button class="p-2 neu-light dark:neu-dark text-blue-600 dark:text-blue-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="p-2 neu-light dark:neu-dark text-green-600 dark:text-green-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="p-2 neu-light dark:neu-dark text-red-600 dark:text-red-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-gray-300">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">#ORD-12344</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">วันนี้ 13:15</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=สมหญิง+รักดี&background=8b5cf6&color=fff"
                                                 class="w-10 h-10 rounded-lg" alt="Customer">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">สมหญิง รักดี</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">somying@email.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white">MacBook Pro M3</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">x1</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">฿89,900</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-500/10 dark:bg-yellow-400/10 text-yellow-600 dark:text-yellow-400">
                                            <i class="fas fa-clock mr-1"></i>
                                            รอดำเนินการ
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            <button class="p-2 neu-light dark:neu-dark text-blue-600 dark:text-blue-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="p-2 neu-light dark:neu-dark text-green-600 dark:text-green-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="p-2 neu-light dark:neu-dark text-red-600 dark:text-red-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-gray-300">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">#ORD-12343</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">เมื่อวาน 16:45</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=ประยุทธ์+มีสุข&background=ec4899&color=fff"
                                                 class="w-10 h-10 rounded-lg" alt="Customer">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">ประยุทธ์ มีสุข</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">prayut@email.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white">iPad Air</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">x2</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">฿39,800</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-500/10 dark:bg-red-400/10 text-red-600 dark:text-red-400">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            ยกเลิก
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            <button class="p-2 neu-light dark:neu-dark text-blue-600 dark:text-blue-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="p-2 neu-light dark:neu-dark text-green-600 dark:text-green-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="p-2 neu-light dark:neu-dark text-red-600 dark:text-red-400 rounded-lg transition-all hover:shadow-inner">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="px-6 py-4 border-t border-gray-300 dark:border-gray-600 flex items-center justify-between">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            แสดง <span class="font-medium">1</span> ถึง <span class="font-medium">3</span> จาก <span class="font-medium">48</span> รายการ
                        </div>
                        <div class="flex items-center gap-2">
                            <button class="px-3 py-2 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-lg hover:shadow-inner transition-all disabled:opacity-50" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="px-3 py-2 bg-blue-500 text-white rounded-lg shadow-lg">1</button>
                            <button class="px-3 py-2 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-lg hover:shadow-inner transition-all">2</button>
                            <button class="px-3 py-2 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-lg hover:shadow-inner transition-all">3</button>
                            <button class="px-3 py-2 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-lg hover:shadow-inner transition-all">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Button Showcase (Neumorphic) --}}
                <div class="neu-light dark:neu-dark rounded-2xl p-6 mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-hand-pointer"></i>
                        ตัวอย่างปุ่มและไอคอน
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Neumorphic Buttons</h4>
                            <div class="flex flex-wrap gap-3">
                                <button class="px-6 py-3 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:shadow-inner transition-all">
                                    <i class="fas fa-save mr-2"></i>
                                    บันทึก
                                </button>
                                <button class="px-6 py-3 neu-light dark:neu-dark text-green-600 dark:text-green-400 rounded-xl font-semibold hover:shadow-inner transition-all">
                                    <i class="fas fa-check mr-2"></i>
                                    ยืนยัน
                                </button>
                                <button class="px-6 py-3 neu-light dark:neu-dark text-red-600 dark:text-red-400 rounded-xl font-semibold hover:shadow-inner transition-all">
                                    <i class="fas fa-trash mr-2"></i>
                                    ลบ
                                </button>
                                <button class="px-6 py-3 neu-light dark:neu-dark text-yellow-600 dark:text-yellow-400 rounded-xl font-semibold hover:shadow-inner transition-all">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    คำเตือน
                                </button>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Colored Neumorphic Buttons</h4>
                            <div class="flex flex-wrap gap-3">
                                <button class="px-6 py-3 bg-blue-500 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                                    <i class="fas fa-plus mr-2"></i>
                                    เพิ่ม
                                </button>
                                <button class="px-6 py-3 bg-green-500 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                                    <i class="fas fa-download mr-2"></i>
                                    ดาวน์โหลด
                                </button>
                                <button class="px-6 py-3 bg-purple-500 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                                    <i class="fas fa-share mr-2"></i>
                                    แชร์
                                </button>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Icon Neumorphic Buttons</h4>
                            <div class="flex flex-wrap gap-3">
                                <button class="w-12 h-12 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-xl hover:shadow-inner transition-all">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="w-12 h-12 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-xl hover:shadow-inner transition-all">
                                    <i class="fas fa-star"></i>
                                </button>
                                <button class="w-12 h-12 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-xl hover:shadow-inner transition-all">
                                    <i class="fas fa-bell"></i>
                                </button>
                                <button class="w-12 h-12 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-xl hover:shadow-inner transition-all">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <button class="w-12 h-12 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-xl hover:shadow-inner transition-all">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Size Variations</h4>
                            <div class="flex flex-wrap items-center gap-3">
                                <button class="px-3 py-1 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 text-xs rounded-lg">
                                    Small
                                </button>
                                <button class="px-4 py-2 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 text-sm rounded-lg">
                                    Medium
                                </button>
                                <button class="px-6 py-3 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 rounded-xl">
                                    Large
                                </button>
                                <button class="px-8 py-4 neu-light dark:neu-dark text-gray-700 dark:text-gray-300 text-lg rounded-xl">
                                    Extra Large
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <style>
        /* Neumorphic Styles */
        .neu-light {
            background: #e0e0e0;
            box-shadow: 8px 8px 16px #bebebe, -8px -8px 16px #ffffff;
        }
        .dark .neu-dark {
            background: #374151;
            box-shadow: 8px 8px 16px #1f2937, -8px -8px 16px #4b5563;
        }
        .neu-light:hover, .dark .neu-dark:hover {
            box-shadow: inset 4px 4px 8px #bebebe, inset -4px -4px 8px #ffffff;
        }
        .dark .neu-dark:hover {
            box-shadow: inset 4px 4px 8px #1f2937, inset -4px -4px 8px #4b5563;
        }
    </style>

</body>
</html>

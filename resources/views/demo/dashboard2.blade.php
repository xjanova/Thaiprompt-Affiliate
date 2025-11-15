<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Glassmorphism Theme</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+Thai:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full font-sans overflow-hidden" x-data="{
    sidebarOpen: true,
    profileOpen: false,
    darkMode: false
}" x-init="document.documentElement.classList.toggle('dark', darkMode);">

    {{-- Background Gradient --}}
    <div class="fixed inset-0 bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 -z-10"></div>

    {{-- Animated Background Circles --}}
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-pink-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 w-96 h-96 bg-yellow-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <div class="flex h-full">

        {{-- Sidebar (Glass) --}}
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'"
               class="glass transition-all duration-300 flex flex-col border-r border-white/20">

            {{-- Logo --}}
            <div class="h-16 flex items-center justify-between px-4 border-b border-white/20">
                <div class="flex items-center gap-3" x-show="sidebarOpen">
                    <div class="w-10 h-10 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <i class="fas fa-gem text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-white drop-shadow-lg">TP-Affiliate</h1>
                        <p class="text-xs text-white/80">Theme 2</p>
                    </div>
                </div>
                <button @click="sidebarOpen = !sidebarOpen"
                        class="p-2 rounded-lg hover:bg-white/20 transition-colors">
                    <i class="fas fa-bars text-white"></i>
                </button>
            </div>

            {{-- Menu --}}
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl bg-white/30 backdrop-blur-sm border border-white/30 text-white transition-all">
                    <i class="fas fa-home w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">แดชบอร์ด</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/90 hover:bg-white/20 transition-all">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">ผู้ใช้งาน</span>
                    <span x-show="sidebarOpen" class="ml-auto px-2 py-0.5 bg-red-400/30 backdrop-blur-sm text-white text-xs font-bold rounded-full">12</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/90 hover:bg-white/20 transition-all">
                    <i class="fas fa-shopping-cart w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">คำสั่งซื้อ</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/90 hover:bg-white/20 transition-all">
                    <i class="fas fa-box w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">สินค้า</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/90 hover:bg-white/20 transition-all">
                    <i class="fas fa-chart-line w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">รายงาน</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/90 hover:bg-white/20 transition-all">
                    <i class="fas fa-wallet w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">กระเป๋าเงิน</span>
                </a>

                <div x-show="sidebarOpen" class="border-t border-white/20 my-4"></div>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/90 hover:bg-white/20 transition-all">
                    <i class="fas fa-cog w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">ตั้งค่า</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/90 hover:bg-white/20 transition-all">
                    <i class="fas fa-question-circle w-5 text-center"></i>
                    <span x-show="sidebarOpen" class="font-medium">ช่วยเหลือ</span>
                </a>
            </nav>

            {{-- User Profile --}}
            <div class="p-4 border-t border-white/20">
                <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-white/20 transition-all cursor-pointer">
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=06b6d4&color=fff"
                         class="w-10 h-10 rounded-xl" alt="Profile">
                    <div x-show="sidebarOpen" class="flex-1 min-w-0">
                        <p class="font-medium text-white text-sm truncate">Admin User</p>
                        <p class="text-xs text-white/80 truncate">admin@tp.com</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Top Navbar (Glass) --}}
            <header class="h-16 glass border-b border-white/20 flex items-center justify-between px-6">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-bold text-white drop-shadow-lg">แดชบอร์ด</h2>
                    <span class="px-3 py-1 bg-white/30 backdrop-blur-sm border border-white/30 text-white text-xs font-bold rounded-full">
                        Glassmorphism
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    {{-- Search --}}
                    <div class="relative hidden md:block">
                        <input type="text" placeholder="ค้นหา..."
                               class="w-64 px-4 py-2 pl-10 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-white/50 focus:bg-white/20 focus:border-white/40 focus:outline-none transition-all">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-white/70"></i>
                    </div>

                    {{-- Notifications --}}
                    <button class="relative p-2 rounded-lg hover:bg-white/20 transition-colors">
                        <i class="fas fa-bell text-white"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    {{-- Profile Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 p-2 rounded-lg hover:bg-white/20 transition-colors">
                            <img src="https://ui-avatars.com/api/?name=Admin+User&background=06b6d4&color=fff"
                                 class="w-8 h-8 rounded-lg" alt="Profile">
                            <i class="fas fa-chevron-down text-white text-sm"></i>
                        </button>

                        <div x-show="open"
                             @click.outside="open = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 glass border border-white/20 rounded-xl shadow-lg py-2 z-50"
                             style="display: none;">
                            <a href="#" class="flex items-center gap-3 px-4 py-2 text-white hover:bg-white/20">
                                <i class="fas fa-user w-4"></i>
                                <span>โปรไฟล์</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 text-white hover:bg-white/20">
                                <i class="fas fa-cog w-4"></i>
                                <span>ตั้งค่า</span>
                            </a>
                            <div class="border-t border-white/20 my-2"></div>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 text-red-300 hover:bg-white/20">
                                <i class="fas fa-sign-out-alt w-4"></i>
                                <span>ออกจากระบบ</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-6">

                {{-- Stats Cards (Glass) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="glass rounded-2xl p-6 hover:bg-white/20 transition-all duration-300 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-green-400/30 backdrop-blur-sm text-white rounded-lg text-xs font-bold">
                                +12.5%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1">12,584</h3>
                        <p class="text-sm text-white/80">ผู้ใช้งานทั้งหมด</p>
                    </div>

                    <div class="glass rounded-2xl p-6 hover:bg-white/20 transition-all duration-300 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-white text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-green-400/30 backdrop-blur-sm text-white rounded-lg text-xs font-bold">
                                +8.3%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1">฿145,678</h3>
                        <p class="text-sm text-white/80">ยอดขายวันนี้</p>
                    </div>

                    <div class="glass rounded-2xl p-6 hover:bg-white/20 transition-all duration-300 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-white text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-green-400/30 backdrop-blur-sm text-white rounded-lg text-xs font-bold">
                                +15.2%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1">389</h3>
                        <p class="text-sm text-white/80">คำสั่งซื้อใหม่</p>
                    </div>

                    <div class="glass rounded-2xl p-6 hover:bg-white/20 transition-all duration-300 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/30 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas fa-chart-line text-white text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-green-400/30 backdrop-blur-sm text-white rounded-lg text-xs font-bold">
                                +22.7%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1">฿2.5M</h3>
                        <p class="text-sm text-white/80">รายได้รวม</p>
                    </div>
                </div>

                {{-- Charts & Activity Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                    {{-- Chart (Glass) --}}
                    <div class="lg:col-span-2 glass rounded-2xl overflow-hidden border border-white/20">
                        <div class="px-6 py-4 border-b border-white/20">
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <i class="fas fa-chart-area"></i>
                                สถิติการขาย
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="h-64 flex items-center justify-center bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                                <div class="text-center">
                                    <i class="fas fa-chart-bar text-6xl text-white/70 mb-4"></i>
                                    <p class="text-white/90">Glassmorphism Chart</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Activity List (Glass) --}}
                    <div class="glass rounded-2xl overflow-hidden border border-white/20">
                        <div class="px-6 py-4 border-b border-white/20">
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <i class="fas fa-clock"></i>
                                กิจกรรมล่าสุด
                            </h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                                <div class="w-10 h-10 bg-green-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-shopping-cart text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">คำสั่งซื้อใหม่ #12345</p>
                                    <p class="text-xs text-white/70">5 นาทีที่แล้ว</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                                <div class="w-10 h-10 bg-blue-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user-plus text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">สมาชิกใหม่ลงทะเบียน</p>
                                    <p class="text-xs text-white/70">15 นาทีที่แล้ว</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                                <div class="w-10 h-10 bg-yellow-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">สินค้าใกล้หมด</p>
                                    <p class="text-xs text-white/70">1 ชั่วโมงที่แล้ว</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                                <div class="w-10 h-10 bg-purple-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-star text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">รีวิวใหม่ 5 ดาว</p>
                                    <p class="text-xs text-white/70">2 ชั่วโมงที่แล้ว</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/10 hover:bg-white/20 transition-all cursor-pointer">
                                <div class="w-10 h-10 bg-red-400/30 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-ban text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">คำสั่งซื้อถูกยกเลิก</p>
                                    <p class="text-xs text-white/70">3 ชั่วโมงที่แล้ว</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Data Table (Glass) --}}
                <div class="glass rounded-2xl overflow-hidden border border-white/20 mb-6">
                    <div class="px-6 py-4 border-b border-white/20 flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-table"></i>
                            รายการคำสั่งซื้อล่าสุด
                        </h3>
                        <div class="flex items-center gap-2">
                            <button class="px-4 py-2 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-lg hover:bg-white/40 transition-all">
                                <i class="fas fa-plus mr-2"></i>
                                เพิ่มใหม่
                            </button>
                            <button class="px-4 py-2 bg-white/20 backdrop-blur-sm border border-white/20 text-white rounded-lg hover:bg-white/30 transition-all">
                                <i class="fas fa-download mr-2"></i>
                                Export
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-white/10 backdrop-blur-sm">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider">
                                        <input type="checkbox" class="rounded border-white/30">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider">
                                        รหัสคำสั่งซื้อ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider">
                                        ลูกค้า
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider">
                                        สินค้า
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider">
                                        ยอดรวม
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider">
                                        สถานะ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider">
                                        จัดการ
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr class="hover:bg-white/10 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-white/30">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white">#ORD-12345</div>
                                        <div class="text-xs text-white/70">วันนี้ 14:30</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=สมชาย+ใจดี&background=06b6d4&color=fff"
                                                 class="w-10 h-10 rounded-lg" alt="Customer">
                                            <div>
                                                <div class="text-sm font-medium text-white">สมชาย ใจดี</div>
                                                <div class="text-xs text-white/70">somchai@email.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-white">iPhone 15 Pro Max</div>
                                        <div class="text-xs text-white/70">x1</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-white">฿45,900</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-400/30 backdrop-blur-sm text-white border border-green-400/30">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            สำเร็จ
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="hover:bg-white/10 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-white/30">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white">#ORD-12344</div>
                                        <div class="text-xs text-white/70">วันนี้ 13:15</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=สมหญิง+รักดี&background=8b5cf6&color=fff"
                                                 class="w-10 h-10 rounded-lg" alt="Customer">
                                            <div>
                                                <div class="text-sm font-medium text-white">สมหญิง รักดี</div>
                                                <div class="text-xs text-white/70">somying@email.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-white">MacBook Pro M3</div>
                                        <div class="text-xs text-white/70">x1</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-white">฿89,900</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-400/30 backdrop-blur-sm text-white border border-yellow-400/30">
                                            <i class="fas fa-clock mr-1"></i>
                                            รอดำเนินการ
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="hover:bg-white/10 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-white/30">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white">#ORD-12343</div>
                                        <div class="text-xs text-white/70">เมื่อวาน 16:45</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=ประยุทธ์+มีสุข&background=ec4899&color=fff"
                                                 class="w-10 h-10 rounded-lg" alt="Customer">
                                            <div>
                                                <div class="text-sm font-medium text-white">ประยุทธ์ มีสุข</div>
                                                <div class="text-xs text-white/70">prayut@email.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-white">iPad Air</div>
                                        <div class="text-xs text-white/70">x2</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-white">฿39,800</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-400/30 backdrop-blur-sm text-white border border-red-400/30">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            ยกเลิก
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="p-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="px-6 py-4 border-t border-white/20 flex items-center justify-between">
                        <div class="text-sm text-white/90">
                            แสดง <span class="font-medium">1</span> ถึง <span class="font-medium">3</span> จาก <span class="font-medium">48</span> รายการ
                        </div>
                        <div class="flex items-center gap-2">
                            <button class="px-3 py-2 bg-white/20 backdrop-blur-sm border border-white/20 text-white rounded-lg hover:bg-white/30 transition-colors disabled:opacity-50" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="px-3 py-2 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-lg">1</button>
                            <button class="px-3 py-2 bg-white/20 backdrop-blur-sm border border-white/20 text-white rounded-lg hover:bg-white/30 transition-colors">2</button>
                            <button class="px-3 py-2 bg-white/20 backdrop-blur-sm border border-white/20 text-white rounded-lg hover:bg-white/30 transition-colors">3</button>
                            <button class="px-3 py-2 bg-white/20 backdrop-blur-sm border border-white/20 text-white rounded-lg hover:bg-white/30 transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Button Showcase (Glass) --}}
                <div class="glass rounded-2xl border border-white/20 p-6 mb-6">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-hand-pointer"></i>
                        ตัวอย่างปุ่มและไอคอน
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-semibold text-white/90 mb-3">Glass Buttons</h4>
                            <div class="flex flex-wrap gap-3">
                                <button class="px-6 py-3 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl font-semibold hover:bg-white/40 transition-all">
                                    <i class="fas fa-save mr-2"></i>
                                    บันทึก
                                </button>
                                <button class="px-6 py-3 bg-green-400/30 backdrop-blur-sm border border-green-400/30 text-white rounded-xl font-semibold hover:bg-green-400/40 transition-all">
                                    <i class="fas fa-check mr-2"></i>
                                    ยืนยัน
                                </button>
                                <button class="px-6 py-3 bg-red-400/30 backdrop-blur-sm border border-red-400/30 text-white rounded-xl font-semibold hover:bg-red-400/40 transition-all">
                                    <i class="fas fa-trash mr-2"></i>
                                    ลบ
                                </button>
                                <button class="px-6 py-3 bg-yellow-400/30 backdrop-blur-sm border border-yellow-400/30 text-white rounded-xl font-semibold hover:bg-yellow-400/40 transition-all">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    คำเตือน
                                </button>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-white/90 mb-3">Outline Glass Buttons</h4>
                            <div class="flex flex-wrap gap-3">
                                <button class="px-6 py-3 border-2 border-white/50 text-white rounded-xl font-semibold hover:bg-white/20 transition-all">
                                    <i class="fas fa-plus mr-2"></i>
                                    เพิ่ม
                                </button>
                                <button class="px-6 py-3 border-2 border-white/50 text-white rounded-xl font-semibold hover:bg-white/20 transition-all">
                                    <i class="fas fa-download mr-2"></i>
                                    ดาวน์โหลด
                                </button>
                                <button class="px-6 py-3 border-2 border-white/50 text-white rounded-xl font-semibold hover:bg-white/20 transition-all">
                                    <i class="fas fa-share mr-2"></i>
                                    แชร์
                                </button>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-white/90 mb-3">Icon Glass Buttons</h4>
                            <div class="flex flex-wrap gap-3">
                                <button class="w-12 h-12 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl hover:bg-white/40 transform hover:scale-110 transition-all">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="w-12 h-12 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl hover:bg-white/40 transform hover:scale-110 transition-all">
                                    <i class="fas fa-star"></i>
                                </button>
                                <button class="w-12 h-12 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl hover:bg-white/40 transform hover:scale-110 transition-all">
                                    <i class="fas fa-bell"></i>
                                </button>
                                <button class="w-12 h-12 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl hover:bg-white/40 transform hover:scale-110 transition-all">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <button class="w-12 h-12 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl hover:bg-white/40 transform hover:scale-110 transition-all">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-white/90 mb-3">Size Variations</h4>
                            <div class="flex flex-wrap items-center gap-3">
                                <button class="px-3 py-1 bg-white/30 backdrop-blur-sm border border-white/30 text-white text-xs rounded-lg">
                                    Small
                                </button>
                                <button class="px-4 py-2 bg-white/30 backdrop-blur-sm border border-white/30 text-white text-sm rounded-lg">
                                    Medium
                                </button>
                                <button class="px-6 py-3 bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl">
                                    Large
                                </button>
                                <button class="px-8 py-4 bg-white/30 backdrop-blur-sm border border-white/30 text-white text-lg rounded-xl">
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
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>

</body>
</html>

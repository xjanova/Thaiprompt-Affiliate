@extends('layouts.admin-v3')

@section('title', 'จัดการบัญชี Marketplace API')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-500 via-red-500 to-pink-600 dark:from-orange-700 dark:via-red-700 dark:to-pink-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
        </div>

        {{-- Floating Platform Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="absolute text-white/10 text-6xl bottom-10 right-40" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
                <i class="fab fa-tiktok"></i>
            </div>
            <div class="absolute text-white/10 text-7xl top-20 left-1/4" style="animation: float 6s ease-in-out infinite; animation-delay: 0.5s">
                <i class="fas fa-store"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title Section --}}
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-store text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">Marketplace Affiliate</h1>
                            <p class="text-orange-100 text-lg mt-1">จัดการบัญชี API สำหรับ Lazada, Shopee, TikTok Shop</p>
                        </div>
                    </div>
                </div>

                {{-- Add Account Button --}}
                <div>
                    <a href="{{ route('admin.marketplace.accounts.create') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-plus group-hover:rotate-90 transition-transform duration-300"></i>
                        เพิ่มบัญชีใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-orange-600 dark:text-orange-400"></i>
            สถิติภาพรวม
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            {{-- Total Accounts --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-key text-9xl"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">บัญชีทั้งหมด</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-link text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['total_accounts'] ?? 0) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-database text-blue-200"></i>
                        <span class="opacity-90">Total Accounts</span>
                    </div>
                </div>
            </div>

            {{-- Active Accounts --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-check-circle text-9xl"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">บัญชี Active</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-plug text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['active_accounts'] ?? 0) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-signal text-green-200"></i>
                        <span class="opacity-90">Connected</span>
                    </div>
                </div>
            </div>

            {{-- Total Products --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-boxes text-9xl"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">สินค้าทั้งหมด</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-cube text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['total_products'] ?? 0) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-sync text-purple-200"></i>
                        <span class="opacity-90">Synced Products</span>
                    </div>
                </div>
            </div>

            {{-- Total Orders --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 dark:from-orange-600 dark:to-red-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-shopping-cart text-9xl"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">ออเดอร์ทั้งหมด</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-receipt text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['total_orders'] ?? 0) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-chart-line text-orange-200"></i>
                        <span class="opacity-90">Total Orders</span>
                    </div>
                </div>
            </div>

            {{-- Total Commission --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 dark:from-emerald-600 dark:to-teal-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-coins text-9xl"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">คอมมิชชั่นรวม</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold mb-2">฿{{ number_format($stats['total_commission'] ?? 0, 2) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-trophy text-emerald-200"></i>
                        <span class="opacity-90">Total Earnings</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-orange-600 dark:text-orange-400"></i>
            ตัวกรองข้อมูล
        </h3>

        <form method="GET" action="{{ route('admin.marketplace.accounts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-1 text-blue-600 dark:text-blue-400"></i>
                    ค้นหา
                </label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหาชื่อบัญชี, ร้านค้า, Shop ID..."
                           class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all placeholder:text-gray-400">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            {{-- Platform Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-store mr-1 text-orange-600 dark:text-orange-400"></i>
                    แพลตฟอร์ม
                </label>
                <select name="platform" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all">
                    <option value="">ทั้งหมด</option>
                    @foreach($platforms ?? [] as $platform)
                        <option value="{{ $platform->id }}" {{ request('platform') == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                    <option value="lazada" {{ request('platform') == 'lazada' ? 'selected' : '' }}>Lazada</option>
                    <option value="shopee" {{ request('platform') == 'shopee' ? 'selected' : '' }}>Shopee</option>
                    <option value="tiktok" {{ request('platform') == 'tiktok' ? 'selected' : '' }}>TikTok Shop</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-toggle-on mr-1 text-green-600 dark:text-green-400"></i>
                    สถานะ
                </label>
                <select name="status" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>ระงับ</option>
                </select>
            </div>

            {{-- Filter Buttons --}}
            <div class="md:col-span-4 flex justify-end gap-3">
                <a href="{{ route('admin.marketplace.accounts.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-xl transition-all flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    รีเซ็ต
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Accounts Table --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-list text-orange-600 dark:text-orange-400"></i>
                    รายการบัญชี Marketplace
                </h3>
                <span class="px-4 py-2 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-xl text-sm font-semibold">
                    {{ number_format($accounts->total()) }} บัญชี
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-store mr-1 text-orange-500"></i> บัญชี
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-tag mr-1 text-blue-500"></i> Platform
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-toggle-on mr-1 text-green-500"></i> สถานะ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-boxes mr-1 text-purple-500"></i> สินค้า
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-shopping-cart mr-1 text-pink-500"></i> ออเดอร์
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-coins mr-1 text-yellow-500"></i> คอมมิชชั่น
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-clock mr-1 text-gray-500"></i> Sync ล่าสุด
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1 text-indigo-500"></i> จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-gradient-to-r hover:from-orange-50/50 hover:to-transparent dark:hover:from-gray-700/50 transition-all duration-200">
                            {{-- Account Info --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    @php
                                        $platformColors = [
                                            'lazada' => 'from-blue-500 to-indigo-600',
                                            'shopee' => 'from-orange-500 to-red-600',
                                            'tiktok' => 'from-gray-800 to-black',
                                        ];
                                        $platformSlug = $account->platform->slug ?? ($account->platform ?? 'default');
                                        $gradient = $platformColors[$platformSlug] ?? 'from-gray-500 to-gray-600';
                                    @endphp
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $gradient }} flex items-center justify-center text-white font-bold shadow-lg transform group-hover:scale-110 transition-transform">
                                        {{ strtoupper(substr($account->account_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $account->account_name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-id-badge mr-1"></i>
                                            {{ $account->shop_name ?? $account->shop_id ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- Platform --}}
                            <td class="px-6 py-4">
                                @php
                                    $platformConfig = [
                                        'lazada' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-300', 'icon' => 'fas fa-shopping-bag', 'name' => 'Lazada'],
                                        'shopee' => ['bg' => 'bg-orange-100 dark:bg-orange-900/30', 'text' => 'text-orange-700 dark:text-orange-300', 'icon' => 'fas fa-store', 'name' => 'Shopee'],
                                        'tiktok' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-700 dark:text-gray-300', 'icon' => 'fab fa-tiktok', 'name' => 'TikTok Shop'],
                                    ];
                                    $pSlug = $account->platform->slug ?? ($account->platform ?? 'default');
                                    $pConfig = $platformConfig[$pSlug] ?? ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-700 dark:text-gray-300', 'icon' => 'fas fa-store', 'name' => $account->platform->name ?? 'Unknown'];
                                @endphp
                                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold {{ $pConfig['bg'] }} {{ $pConfig['text'] }}">
                                    <i class="{{ $pConfig['icon'] }}"></i>
                                    {{ $pConfig['name'] }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusConfig = [
                                        'active' => ['gradient' => 'from-green-400 to-emerald-500', 'icon' => 'fa-check-circle', 'label' => 'ใช้งาน'],
                                        'inactive' => ['gradient' => 'from-gray-400 to-gray-500', 'icon' => 'fa-pause-circle', 'label' => 'ไม่ใช้งาน'],
                                        'pending' => ['gradient' => 'from-yellow-400 to-orange-500', 'icon' => 'fa-clock', 'label' => 'รอตรวจสอบ'],
                                        'suspended' => ['gradient' => 'from-red-400 to-red-600', 'icon' => 'fa-ban', 'label' => 'ระงับ'],
                                    ];
                                    $sConfig = $statusConfig[$account->status] ?? $statusConfig['inactive'];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r {{ $sConfig['gradient'] }} text-white shadow-lg">
                                    <i class="fas {{ $sConfig['icon'] }}"></i>
                                    {{ $sConfig['label'] }}
                                </span>
                            </td>

                            {{-- Products Count --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-sm font-bold bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">
                                    <i class="fas fa-boxes"></i>
                                    {{ number_format($account->total_products_synced ?? 0) }}
                                </span>
                            </td>

                            {{-- Orders Count --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-sm font-bold bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300">
                                    <i class="fas fa-shopping-cart"></i>
                                    {{ number_format($account->total_orders_synced ?? 0) }}
                                </span>
                            </td>

                            {{-- Commission --}}
                            <td class="px-6 py-4 text-right">
                                <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                    ฿{{ number_format($account->total_commission ?? 0, 2) }}
                                </span>
                            </td>

                            {{-- Last Sync --}}
                            <td class="px-6 py-4 text-center">
                                @if($account->last_sync_at)
                                    <div class="text-sm text-gray-700 dark:text-gray-300 font-medium">
                                        {{ $account->last_sync_at->diffForHumans() }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $account->last_sync_at->format('d/m/Y H:i') }}
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-sm italic">ยังไม่เคย Sync</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- View --}}
                                    <a href="{{ route('admin.marketplace.accounts.show', $account) }}"
                                       class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-200 dark:hover:bg-blue-800 transition-all hover:scale-110 shadow-sm"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.marketplace.accounts.edit', $account) }}"
                                       class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400 flex items-center justify-center hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-all hover:scale-110 shadow-sm"
                                       title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    {{-- Sync --}}
                                    <button type="button"
                                            onclick="syncAll({{ $account->id }})"
                                            class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 flex items-center justify-center hover:bg-green-200 dark:hover:bg-green-800 transition-all hover:scale-110 shadow-sm"
                                            title="Sync ข้อมูล">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>

                                    {{-- Delete --}}
                                    <button type="button"
                                            onclick="confirmDelete({{ $account->id }})"
                                            class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 flex items-center justify-center hover:bg-red-200 dark:hover:bg-red-800 transition-all hover:scale-110 shadow-sm"
                                            title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-32 h-32 bg-gradient-to-br from-orange-100 to-red-100 dark:from-orange-900/30 dark:to-red-900/30 rounded-full flex items-center justify-center mb-6 shadow-xl">
                                        <i class="fas fa-store text-5xl text-orange-500 dark:text-orange-400"></i>
                                    </div>
                                    <h4 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-3">ไม่พบบัญชี Marketplace</h4>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md">เริ่มต้นเชื่อมต่อบัญชี Lazada, Shopee หรือ TikTok Shop เพื่อรับค่า Affiliate Commission</p>
                                    <a href="{{ route('admin.marketplace.accounts.create') }}"
                                       class="px-8 py-4 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center gap-2">
                                        <i class="fas fa-plus"></i>
                                        เพิ่มบัญชีแรกของคุณ
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($accounts->hasPages())
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 transform transition-all">
        <div class="text-center">
            <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">ยืนยันการลบ?</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">คุณแน่ใจหรือไม่ที่จะลบบัญชีนี้? การกระทำนี้ไม่สามารถยกเลิกได้</p>
            <div class="flex gap-4 justify-center">
                <button onclick="closeDeleteModal()" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                    ยกเลิก
                </button>
                <form id="deleteForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg">
                        <i class="fas fa-trash mr-2"></i>
                        ลบบัญชี
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
    }
    .dark .glass-card {
        background: rgba(31, 41, 55, 0.8);
    }
</style>
@endpush

@push('scripts')
<script>
    // Sync All Products & Orders
    function syncAll(accountId) {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`{{ url('admin/marketplace/accounts') }}/${accountId}/sync-all`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success toast
                showToast('success', data.message || 'Sync สำเร็จ!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('error', 'เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            showToast('error', 'เกิดข้อผิดพลาด: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    // Delete confirmation
    function confirmDelete(accountId) {
        const modal = document.getElementById('deleteModal');
        const form = document.getElementById('deleteForm');
        form.action = `{{ url('admin/marketplace/accounts') }}/${accountId}`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Toast notification
    function showToast(type, message) {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'from-green-500 to-emerald-600' : 'from-red-500 to-red-600';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        toast.className = `fixed top-4 right-4 z-50 px-6 py-4 bg-gradient-to-r ${bgColor} text-white rounded-xl shadow-2xl transform transition-all duration-300 flex items-center gap-3`;
        toast.innerHTML = `<i class="fas ${icon} text-xl"></i><span>${message}</span>`;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Close modal on outside click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
</script>
@endpush
@endsection

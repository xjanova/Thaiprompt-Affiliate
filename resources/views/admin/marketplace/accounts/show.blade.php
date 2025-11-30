@extends('layouts.admin-v3')

@section('title', 'รายละเอียดบัญชี - ' . $account->account_name)

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .float-animation {
        animation: float 6s ease-in-out infinite;
    }
    @keyframes pulse-slow {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 0.8; }
    }
    .pulse-slow {
        animation: pulse-slow 4s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-700 p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl pulse-slow"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-400/20 rounded-full blur-3xl pulse-slow" style="animation-delay: 2s;"></div>

        {{-- Floating Icon --}}
        <div class="absolute right-8 top-1/2 -translate-y-1/2 hidden lg:block">
            <div class="w-32 h-32 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center float-animation">
                @if($account->platform)
                    @if($account->platform->slug == 'lazada')
                        <span class="text-5xl font-bold text-orange-300">L</span>
                    @elseif($account->platform->slug == 'shopee')
                        <span class="text-5xl font-bold text-red-300">S</span>
                    @elseif($account->platform->slug == 'tiktok')
                        <span class="text-5xl font-bold text-pink-300">T</span>
                    @else
                        <i class="fas fa-store text-5xl text-white/80"></i>
                    @endif
                @else
                    <i class="fas fa-store text-5xl text-white/80"></i>
                @endif
            </div>
        </div>

        <div class="relative">
            {{-- Back Button --}}
            <a href="{{ route('admin.marketplace.accounts.index') }}"
               class="inline-flex items-center gap-2 text-white/80 hover:text-white transition mb-4">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปรายการ</span>
            </a>

            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                    {{ strtoupper(substr($account->account_name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ $account->account_name }}</h1>
                    <p class="text-white/80 mt-1">{{ $account->shop_name ?? $account->shop_id ?? 'ไม่ระบุร้านค้า' }}</p>

                    {{-- Status Badges --}}
                    <div class="flex items-center gap-2 mt-3">
                        @php
                            $statusColors = [
                                'active' => 'bg-emerald-500',
                                'inactive' => 'bg-gray-500',
                                'pending' => 'bg-amber-500',
                                'suspended' => 'bg-red-500',
                            ];
                            $statusLabels = [
                                'active' => 'ใช้งาน',
                                'inactive' => 'ไม่ใช้งาน',
                                'pending' => 'รอตรวจสอบ',
                                'suspended' => 'ระงับ',
                            ];
                        @endphp
                        <span class="px-3 py-1 {{ $statusColors[$account->status] ?? 'bg-gray-500' }} text-white rounded-full text-sm font-medium shadow-lg">
                            {{ $statusLabels[$account->status] ?? $account->status }}
                        </span>

                        @if($account->platform)
                            <span class="px-3 py-1 bg-white/20 text-white rounded-full text-sm font-medium">
                                {{ $account->platform->name }}
                            </span>
                        @endif

                        @if($account->last_error)
                            <span class="px-3 py-1 bg-red-500 text-white rounded-full text-sm font-medium animate-pulse">
                                <i class="fas fa-exclamation-triangle mr-1"></i>มีข้อผิดพลาด
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-3 mt-6">
                <button type="button" onclick="testConnection()"
                        class="px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition flex items-center gap-2">
                    <i class="fas fa-plug"></i>
                    <span>ทดสอบ API</span>
                </button>
                <button type="button" onclick="syncAll()"
                        class="px-5 py-2.5 bg-white text-indigo-600 rounded-xl hover:bg-white/90 transition font-medium flex items-center gap-2">
                    <i class="fas fa-sync"></i>
                    <span>Sync ทั้งหมด</span>
                </button>
                <a href="{{ route('admin.marketplace.accounts.edit', $account) }}"
                   class="px-5 py-2.5 bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    <span>แก้ไข</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-card p-5 rounded-xl text-center group hover:scale-105 transition-transform duration-300">
            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-blue-500/25">
                <i class="fas fa-box text-white text-lg"></i>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_products']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สินค้าทั้งหมด</p>
        </div>

        <div class="glass-card p-5 rounded-xl text-center group hover:scale-105 transition-transform duration-300">
            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-emerald-500/25">
                <i class="fas fa-check-circle text-white text-lg"></i>
            </div>
            <p class="text-3xl font-bold text-emerald-600">{{ number_format($stats['active_products']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สินค้าใช้งาน</p>
        </div>

        <div class="glass-card p-5 rounded-xl text-center group hover:scale-105 transition-transform duration-300">
            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-purple-500/25">
                <i class="fas fa-shopping-cart text-white text-lg"></i>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_orders']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ออเดอร์ทั้งหมด</p>
        </div>

        <div class="glass-card p-5 rounded-xl text-center group hover:scale-105 transition-transform duration-300">
            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-amber-500/25">
                <i class="fas fa-clock text-white text-lg"></i>
            </div>
            <p class="text-3xl font-bold text-amber-600">{{ number_format($stats['pending_orders']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">รอดำเนินการ</p>
        </div>

        <div class="glass-card p-5 rounded-xl text-center group hover:scale-105 transition-transform duration-300">
            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-cyan-500/25">
                <i class="fas fa-chart-line text-white text-lg"></i>
            </div>
            <p class="text-3xl font-bold text-blue-600">฿{{ number_format($stats['total_sales'], 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ยอดขายรวม</p>
        </div>

        <div class="glass-card p-5 rounded-xl text-center group hover:scale-105 transition-transform duration-300">
            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-teal-500 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-teal-500/25">
                <i class="fas fa-coins text-white text-lg"></i>
            </div>
            <p class="text-3xl font-bold text-emerald-600">฿{{ number_format($stats['total_commission'], 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">คอมมิชชั่นรวม</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Account Info Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        ข้อมูลบัญชี
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Shop ID</p>
                            <p class="font-semibold text-gray-900 dark:text-white font-mono">{{ $account->shop_id ?? '-' }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ชื่อร้านค้า</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $account->shop_name ?? '-' }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">เจ้าของบัญชี</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $account->user->name ?? 'Admin' }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">อัตราคอมมิชชั่น</p>
                            <p class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($account->commission_rate ?? 0, 2) }}%</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Sync สินค้าอัตโนมัติ</p>
                            <p class="font-semibold {{ $account->auto_sync_products ? 'text-emerald-600' : 'text-gray-400' }}">
                                <i class="fas {{ $account->auto_sync_products ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $account->auto_sync_products ? 'เปิดใช้งาน' : 'ปิด' }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Sync ออเดอร์อัตโนมัติ</p>
                            <p class="font-semibold {{ $account->auto_sync_orders ? 'text-emerald-600' : 'text-gray-400' }}">
                                <i class="fas {{ $account->auto_sync_orders ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $account->auto_sync_orders ? 'เปิดใช้งาน' : 'ปิด' }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Sync ล่าสุด</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $account->last_sync_at ? $account->last_sync_at->format('d/m/Y H:i') : '-' }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Token หมดอายุ</p>
                            <p class="font-semibold {{ $account->isTokenExpired() ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                {{ $account->token_expires_at ? $account->token_expires_at->format('d/m/Y H:i') : '-' }}
                                @if($account->isTokenExpired())
                                    <span class="text-red-500 text-sm ml-1 animate-pulse">(หมดอายุ!)</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($account->last_error)
                        <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/30 rounded-xl border border-red-200 dark:border-red-800">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-medium text-red-700 dark:text-red-400">ข้อผิดพลาดล่าสุด</p>
                                    <p class="text-sm text-red-600 dark:text-red-300 mt-1">{{ $account->last_error }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sync Logs Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-history text-purple-500"></i>
                        ประวัติ Sync (10 รายการล่าสุด)
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">รายการ</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ระยะเวลา</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">เวลา</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($account->syncLogs as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-6 py-4 text-sm">
                                        <span class="flex items-center gap-2">
                                            <i class="fas {{ $log->sync_type == 'products' ? 'fa-box text-blue-500' : 'fa-shopping-cart text-purple-500' }}"></i>
                                            <span class="text-gray-900 dark:text-white">{{ $log->sync_type == 'products' ? 'สินค้า' : 'ออเดอร์' }}</span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($log->sync_status == 'completed')
                                            <span class="px-3 py-1 bg-gradient-to-r from-emerald-500 to-green-500 text-white rounded-full text-xs font-medium">สำเร็จ</span>
                                        @elseif($log->sync_status == 'failed')
                                            <span class="px-3 py-1 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-full text-xs font-medium">ล้มเหลว</span>
                                        @elseif($log->sync_status == 'partial')
                                            <span class="px-3 py-1 bg-gradient-to-r from-amber-500 to-yellow-500 text-white rounded-full text-xs font-medium">บางส่วน</span>
                                        @else
                                            <span class="px-3 py-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-full text-xs font-medium animate-pulse">กำลังทำงาน</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                        <span class="text-emerald-600">+{{ $log->items_created ?? 0 }}</span>
                                        /
                                        <span class="text-blue-600">{{ $log->items_updated ?? 0 }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                        {{ $log->duration_seconds ?? 0 }}s
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                        {{ $log->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                                <i class="fas fa-history text-gray-400 text-2xl"></i>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีประวัติ Sync</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Sync Actions Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-cyan-500/10 to-blue-500/10 dark:from-cyan-500/20 dark:to-blue-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-sync text-cyan-500"></i>
                        Sync ข้อมูล
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <button type="button" onclick="syncProducts()"
                            class="w-full px-4 py-3 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-xl hover:shadow-lg hover:shadow-purple-500/25 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-box"></i>
                        <span>Sync สินค้า</span>
                    </button>

                    <button type="button" onclick="syncOrders()"
                            class="w-full px-4 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:shadow-lg hover:shadow-orange-500/25 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sync ออเดอร์</span>
                    </button>

                    <button type="button" onclick="syncAll()"
                            class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white rounded-xl hover:shadow-lg hover:shadow-indigo-500/25 transition-all flex items-center justify-center gap-2 font-medium">
                        <i class="fas fa-sync"></i>
                        <span>Sync ทั้งหมด</span>
                    </button>
                </div>
            </div>

            {{-- Quick Links Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/20 dark:to-teal-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-link text-emerald-500"></i>
                        ลิงก์ด่วน
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('admin.marketplace.products.index', ['account' => $account->id]) }}"
                       class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <span class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-box text-blue-500"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">สินค้าทั้งหมด</span>
                        </span>
                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full text-sm font-medium">
                            {{ $stats['total_products'] }}
                        </span>
                    </a>

                    <a href="{{ route('admin.marketplace.orders.index', ['account' => $account->id]) }}"
                       class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                        <span class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-shopping-cart text-purple-500"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">ออเดอร์ทั้งหมด</span>
                        </span>
                        <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full text-sm font-medium">
                            {{ $stats['total_orders'] }}
                        </span>
                    </a>
                </div>
            </div>

            {{-- Danger Zone Card --}}
            <div class="glass-card rounded-2xl overflow-hidden border-2 border-red-200 dark:border-red-800">
                <div class="bg-gradient-to-r from-red-500/10 to-rose-500/10 dark:from-red-500/20 dark:to-rose-500/20 px-6 py-4 border-b border-red-200 dark:border-red-800">
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        Danger Zone
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        การลบบัญชีจะลบข้อมูลสินค้าและออเดอร์ทั้งหมดที่เกี่ยวข้อง
                    </p>
                    <form action="{{ route('admin.marketplace.accounts.destroy', $account) }}" method="POST"
                          onsubmit="return confirm('⚠️ คุณแน่ใจหรือไม่?\n\nการลบบัญชีนี้จะลบข้อมูลสินค้าและออเดอร์ทั้งหมดอย่างถาวร!');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:shadow-lg hover:shadow-red-500/25 transition-all">
                            <i class="fas fa-trash mr-2"></i>ลบบัญชี
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast Notification --}}
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
    <div class="flex items-center gap-3 px-6 py-4 rounded-xl shadow-lg" id="toast-content">
        <i id="toast-icon" class="fas"></i>
        <span id="toast-message"></span>
    </div>
</div>

@push('scripts')
<script>
    const accountId = {{ $account->id }};

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        const icon = document.getElementById('toast-icon');
        const msg = document.getElementById('toast-message');

        content.className = 'flex items-center gap-3 px-6 py-4 rounded-xl shadow-lg ';
        icon.className = 'fas ';

        if (type === 'success') {
            content.className += 'bg-emerald-500 text-white';
            icon.className += 'fa-check-circle';
        } else if (type === 'error') {
            content.className += 'bg-red-500 text-white';
            icon.className += 'fa-exclamation-circle';
        } else {
            content.className += 'bg-blue-500 text-white';
            icon.className += 'fa-info-circle';
        }

        msg.textContent = message;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    function testConnection() {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลังทดสอบ...</span>';

        fetch(`{{ route('admin.marketplace.accounts.test-connection', $account) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('เกิดข้อผิดพลาด: ' + error.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    function syncProducts() {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลัง Sync...</span>';

        fetch(`{{ route('admin.marketplace.accounts.sync-products', $account) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('เกิดข้อผิดพลาด: ' + error.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    function syncOrders() {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลัง Sync...</span>';

        fetch(`{{ route('admin.marketplace.accounts.sync-orders', $account) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('เกิดข้อผิดพลาด: ' + error.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    function syncAll() {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลัง Sync...</span>';

        fetch(`{{ route('admin.marketplace.accounts.sync-all', $account) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('เกิดข้อผิดพลาด: ' + error.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }
</script>
@endpush
@endsection

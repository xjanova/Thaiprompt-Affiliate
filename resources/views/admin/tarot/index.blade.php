@extends('layouts.admin-v3')

@section('title', 'ระบบไพ่ทาโร่ต์')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 via-indigo-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                <i class="fas fa-hand-sparkles text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ระบบไพ่ทาโร่ต์</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการระบบทำนายไพ่ทาโร่ต์ทั้งหมด</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.tarot.analytics') }}"
               class="inline-flex items-center px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition transform hover:scale-[1.02] shadow-lg">
                <i class="fas fa-chart-line mr-2"></i>
                Analytics
            </a>
            <a href="{{ route('admin.tarot.settings') }}"
               class="inline-flex items-center px-5 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition">
                <i class="fas fa-cog mr-2"></i>
                ตั้งค่า
            </a>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- Total Readings --}}
        <div class="glass-fusion rounded-xl shadow-lg p-5 border border-white/20 dark:border-white/10 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">การทำนายทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_readings']) }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-eye text-white text-lg"></i>
                </div>
            </div>
        </div>

        {{-- Today Readings --}}
        <div class="glass-fusion rounded-xl shadow-lg p-5 border border-white/20 dark:border-white/10 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">วันนี้</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['today_readings']) }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-calendar-day text-white text-lg"></i>
                </div>
            </div>
        </div>

        {{-- Free vs Paid --}}
        <div class="glass-fusion rounded-xl shadow-lg p-5 border border-white/20 dark:border-white/10 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">ฟรี / จ่าย</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <span class="text-green-600">{{ number_format($stats['free_readings']) }}</span>
                        <span class="text-gray-400 mx-1">/</span>
                        <span class="text-yellow-600">{{ number_format($stats['paid_readings']) }}</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-coins text-white text-lg"></i>
                </div>
            </div>
        </div>

        {{-- Total Revenue --}}
        <div class="glass-fusion rounded-xl shadow-lg p-5 border border-white/20 dark:border-white/10 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">รายได้รวม</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">฿{{ number_format($stats['total_revenue'], 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-baht-sign text-white text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-bolt text-yellow-500"></i>
        จัดการด่วน
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        {{-- Cards --}}
        <a href="{{ route('admin.tarot.cards.index') }}"
           class="group glass-fusion rounded-xl shadow p-5 border border-white/20 dark:border-white/10 hover:shadow-xl hover:border-purple-500/50 transition-all duration-300 hover:-translate-y-1 text-center">
            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-cards text-white text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ไพ่ทั้งหมด</h3>
            <p class="text-sm text-purple-600 dark:text-purple-400 font-medium">{{ $stats['active_cards'] }}/{{ $stats['total_cards'] }} ใบ</p>
        </a>

        {{-- Card Backs --}}
        <a href="{{ route('admin.tarot.card-backs.index') }}"
           class="group glass-fusion rounded-xl shadow p-5 border border-white/20 dark:border-white/10 hover:shadow-xl hover:border-pink-500/50 transition-all duration-300 hover:-translate-y-1 text-center">
            <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-layer-group text-white text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">หลังไพ่</h3>
            <p class="text-sm text-pink-600 dark:text-pink-400 font-medium">จัดการรูปหลังไพ่</p>
        </a>

        {{-- Interpretations --}}
        <a href="{{ route('admin.tarot.interpretations.index') }}"
           class="group glass-fusion rounded-xl shadow p-5 border border-white/20 dark:border-white/10 hover:shadow-xl hover:border-indigo-500/50 transition-all duration-300 hover:-translate-y-1 text-center">
            <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-book-open text-white text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">คำทำนาย</h3>
            <p class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">แก้ไขคำทำนาย</p>
        </a>

        {{-- Categories --}}
        <a href="{{ route('admin.tarot.categories.index') }}"
           class="group glass-fusion rounded-xl shadow p-5 border border-white/20 dark:border-white/10 hover:shadow-xl hover:border-blue-500/50 transition-all duration-300 hover:-translate-y-1 text-center">
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-th-large text-white text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">หมวดหมู่</h3>
            <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">{{ $stats['categories_count'] }} หมวด</p>
        </a>

        {{-- Readings --}}
        <a href="{{ route('admin.tarot.readings.index') }}"
           class="group glass-fusion rounded-xl shadow p-5 border border-white/20 dark:border-white/10 hover:shadow-xl hover:border-green-500/50 transition-all duration-300 hover:-translate-y-1 text-center">
            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-history text-white text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ประวัติ</h3>
            <p class="text-sm text-green-600 dark:text-green-400 font-medium">ดูการทำนาย</p>
        </a>

        {{-- Settings --}}
        <a href="{{ route('admin.tarot.settings') }}"
           class="group glass-fusion rounded-xl shadow p-5 border border-white/20 dark:border-white/10 hover:shadow-xl hover:border-gray-500/50 transition-all duration-300 hover:-translate-y-1 text-center">
            <div class="w-14 h-14 bg-gradient-to-br from-gray-500 to-gray-700 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-cog text-white text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ตั้งค่า</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">ตั้งค่าระบบ</p>
        </a>
    </div>

    {{-- Recent Readings --}}
    <div class="glass-fusion rounded-xl shadow-lg border border-white/20 dark:border-white/10 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-clock text-purple-500"></i>
                การทำนายล่าสุด
            </h2>
            <a href="{{ route('admin.tarot.readings.index') }}"
               class="text-sm text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 font-medium">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">หมวดหมู่</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">รูปแบบ</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recent_readings as $reading)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                        {{ strtoupper(substr($reading->user->name ?? 'G', 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ $reading->user->name ?? 'Guest' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300">
                                    {{ $reading->category->name_th ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $reading->spreadType->name_th ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($reading->is_free)
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                        <i class="fas fa-gift mr-1"></i>ฟรี
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300">
                                        <i class="fas fa-coins mr-1"></i>฿{{ number_format($reading->amount_paid) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $reading->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a href="{{ route('admin.tarot.readings.show', $reading->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition">
                                    <i class="fas fa-eye mr-1"></i>ดู
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีการทำนาย</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

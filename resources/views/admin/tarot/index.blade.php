@extends('layouts.admin-v3')

@section('title', 'Tarot Reading System')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-hand-sparkles text-purple-600 mr-2"></i>
                ระบบหมอดูไพ่ทาโร่ต์
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">จัดการระบบทำนายไพ่ทาโร่ต์</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.tarot.analytics') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl">
                <i class="fas fa-chart-line mr-2"></i> Analytics
            </a>
            <a href="{{ route('admin.tarot.settings') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-xl">
                <i class="fas fa-cog mr-2"></i> ตั้งค่า
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Readings -->
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">การทำนายทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_readings']) }}</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-cards text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Today Readings -->
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">วันนี้</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['today_readings']) }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-calendar-day text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Free vs Paid -->
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ฟรี / จ่าย</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($stats['free_readings']) }} / {{ number_format($stats['paid_readings']) }}
                    </p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">รายได้รวม</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_revenue'], 2) }}</p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-coins text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="{{ route('admin.tarot.cards.index') }}" class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl p-6 text-white hover:shadow-lg transform hover:scale-105 transition-all">
            <i class="fas fa-cards text-4xl mb-4"></i>
            <h3 class="text-xl font-bold mb-2">จัดการไพ่</h3>
            <p class="text-purple-100">{{ $stats['active_cards'] }} / {{ $stats['total_cards'] }} ไพ่</p>
        </a>

        <a href="{{ route('admin.tarot.categories.index') }}" class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-6 text-white hover:shadow-lg transform hover:scale-105 transition-all">
            <i class="fas fa-th-large text-4xl mb-4"></i>
            <h3 class="text-xl font-bold mb-2">หมวดหมู่</h3>
            <p class="text-blue-100">{{ $stats['categories_count'] }} หมวดหมู่</p>
        </a>

        <a href="{{ route('admin.tarot.readings.index') }}" class="bg-gradient-to-br from-green-500 to-green-700 rounded-xl p-6 text-white hover:shadow-lg transform hover:scale-105 transition-all">
            <i class="fas fa-history text-4xl mb-4"></i>
            <h3 class="text-xl font-bold mb-2">ประวัติการทำนาย</h3>
            <p class="text-green-100">ดูทั้งหมด</p>
        </a>
    </div>

    <!-- Recent Readings -->
    <div class="glass-fusion rounded-xl shadow" border border-white/20 dark:border-white/10>
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">การทำนายล่าสุด</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">หมวดหมู่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">รูปแบบ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion divide-y divide-gray-200">
                    @forelse($recent_readings as $reading)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $reading->user->name ?? 'Guest' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $reading->category->name_th }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $reading->spreadType->name_th }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reading->is_free)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    ฟรี
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    ฿{{ number_format($reading->amount_paid) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $reading->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('admin.tarot.readings.show', $reading->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                ดู
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            ยังไม่มีการทำนาย
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

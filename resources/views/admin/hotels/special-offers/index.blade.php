@extends('layouts.admin-v3')

@section('title', 'จัดการโปรโมชั่นพิเศษ')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-tags text-blue-600 dark:text-blue-400 mr-2"></i>
            โปรโมชั่นพิเศษ
        </h1>
        <a href="{{ route('admin.hotels.special-offers.create') }}"
           class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium transition shadow-lg hover:shadow-xl">
            <i class="fas fa-plus mr-2"></i>สร้างโปรโมชั่น
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ชื่อ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">โรงแรม/ห้องพัก</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ส่วนลด</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ระยะเวลา</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($offers as $offer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $offer->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $offer->code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($offer->hotel)
                                    <a href="{{ route('admin.hotels.show', $offer->hotel_id) }}"
                                       class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                        {{ $offer->hotel->name }}
                                    </a>
                                @elseif($offer->roomType)
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $offer->roomType->hotel->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $offer->roomType->name }}</div>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">ทุกโรงแรม</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                    @if($offer->discount_type == 'percentage')
                                        {{ $offer->discount_value }}%
                                    @else
                                        ฿{{ number_format($offer->discount_value) }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $offer->start_date->format('d/m/Y') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">ถึง {{ $offer->end_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @if($offer->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            เปิดใช้งาน
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400">
                                            ปิดใช้งาน
                                        </span>
                                    @endif
                                    @if($offer->is_featured)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                            แนะนำ
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.hotels.special-offers.edit', $offer->id) }}"
                                       class="px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition text-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.hotels.special-offers.destroy', $offer->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ยืนยันการลบ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg transition text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p>ยังไม่มีโปรโมชั่น</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($offers->hasPages())
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                {{ $offers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

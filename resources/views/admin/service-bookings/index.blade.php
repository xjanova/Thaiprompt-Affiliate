@extends('layouts.admin')

@section('title', 'การจองบริการทั้งหมด')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
            <i class="fas fa-calendar-check mr-3"></i>การจองบริการทั้งหมด
        </h1>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-2xl font-bold">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">รอดำเนินการ</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">กำลังทำ</p>
            <p class="text-2xl font-bold text-purple-600">{{ $stats['in_progress'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">เสร็จสิ้น</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Bookings Table --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-xl overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เลขที่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">บริการ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ลูกค้า</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ให้บริการ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ราคา</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($bookings as $booking)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 text-sm font-mono">{{ $booking->booking_number }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ $booking->service->category->icon ?? '🔧' }}</span>
                            <span class="text-sm font-semibold">{{ $booking->service->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $booking->user->name }}</td>
                    <td class="px-6 py-4 text-sm">{{ $booking->provider->name ?? 'ยังไม่มี' }}</td>
                    <td class="px-6 py-4 text-sm font-bold">฿{{ number_format($booking->total_price, 2) }}</td>
                    <td class="px-6 py-4">
                        <x-booking-status-badge :status="$booking->status" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.service-bookings.show', $booking) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">ไม่มีการจอง</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bookings->hasPages())
        <div class="mt-6">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection

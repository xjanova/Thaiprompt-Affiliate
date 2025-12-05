{{--
    หน้าประวัติอัพเกรดดาว

    @author Video Reward System
--}}

@extends('layouts.user-arrow-x')

@section('title', $pageTitle ?? 'ประวัติอัพเกรดดาว')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('user.star-upgrade.index') }}"
               class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="text-4xl">📜</span>
                    ประวัติอัพเกรดดาว
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    รายการอัพเกรดดาวทั้งหมดของคุณ
                </p>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">🔄</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">จำนวนครั้งที่อัพเกรด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_upgrades'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">💰</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Coins ที่ใช้ทั้งหมด</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['total_coins_spent'], 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">⭐</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ดาวสูงสุดที่ได้</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['highest_star'] }} ดาว</p>
                </div>
            </div>
        </div>
    </div>

    {{-- History Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        @if($history->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="text-left py-4 px-6 text-gray-500 dark:text-gray-400 text-sm font-medium">วันที่</th>
                        <th class="text-center py-4 px-6 text-gray-500 dark:text-gray-400 text-sm font-medium">จาก</th>
                        <th class="text-center py-4 px-6 text-gray-500 dark:text-gray-400 text-sm font-medium"></th>
                        <th class="text-center py-4 px-6 text-gray-500 dark:text-gray-400 text-sm font-medium">เป็น</th>
                        <th class="text-right py-4 px-6 text-gray-500 dark:text-gray-400 text-sm font-medium">ราคา</th>
                        <th class="text-center py-4 px-6 text-gray-500 dark:text-gray-400 text-sm font-medium">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($history as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="py-4 px-6">
                                <p class="text-gray-900 dark:text-white font-medium">
                                    {{ $item->created_at->format('d/m/Y') }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->created_at->format('H:i:s') }}
                                </p>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex justify-center items-center gap-0.5">
                                    @for($i = 0; $i < $item->from_stars; $i++)
                                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $item->from_stars }} ดาว</p>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <svg class="w-6 h-6 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex justify-center items-center gap-0.5">
                                    @for($i = 0; $i < $item->to_stars; $i++)
                                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $item->to_stars }} ดาว</p>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ number_format($item->coins_paid, 0) }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Coins</p>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($item->status === 'completed')
                                    <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-sm rounded-full font-medium">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        สำเร็จ
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 text-sm rounded-full font-medium">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                        </svg>
                                        คืนเงิน
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
            {{ $history->links() }}
        </div>
        @else
        {{-- Empty State --}}
        <div class="text-center py-16">
            <div class="text-6xl mb-4">⭐</div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีประวัติการอัพเกรด</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                เริ่มต้นอัพเกรดดาวเพื่อรับสิทธิประโยชน์พิเศษ
            </p>
            <a href="{{ route('user.star-upgrade.index') }}"
               class="inline-flex items-center px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition">
                ไปอัพเกรดดาว
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

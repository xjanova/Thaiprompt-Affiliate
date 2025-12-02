{{--
    หน้าจัดการราคาอัพเกรดดาว (Admin)

    @author Video Reward System
--}}

@extends('layouts.admin')

@section('title', $pageTitle ?? 'จัดการราคาอัพเกรดดาว')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-3xl">⭐</span>
                จัดการราคาอัพเกรดดาว
            </h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">
                กำหนดราคาและเงื่อนไขการอัพเกรดดาวสำหรับผู้ใช้
            </p>
        </div>

        <a href="{{ route('admin.star-upgrade.history') }}"
           class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ดูประวัติการอัพเกรด
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">🔄</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">อัพเกรดทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_upgrades']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">💰</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Coins รวม</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['total_coins_collected'], 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">📈</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">วันนี้</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['upgrades_today']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">📊</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เดือนนี้</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['upgrades_this_month']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Star Prices Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
        @foreach($starPrices as $price)
            @php
                $colorInfo = $starColors[$price->star_color] ?? $starColors['bronze'];
                $upgradeStats = $upgradesByStars->get($price->stars);
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden {{ !$price->is_active ? 'opacity-60' : '' }}">
                {{-- Header --}}
                <div class="bg-gradient-to-br {{ $colorInfo['gradient'] }} p-5 text-center text-white">
                    <div class="flex justify-center items-center gap-1 mb-2">
                        @for($i = 0; $i < $price->stars; $i++)
                            <svg class="w-6 h-6 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <h3 class="font-bold">{{ $price->display_name }}</h3>
                    <p class="text-sm text-white/80">{{ $colorInfo['name'] }}</p>
                </div>

                {{-- Content --}}
                <div class="p-4">
                    {{-- Pricing --}}
                    <div class="mb-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">ราคาเต็ม</p>
                        <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($price->price_coins, 0) }} C</p>
                        @if($price->price_coins_from_previous)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                อัพจากก่อนหน้า: {{ number_format($price->price_coins_from_previous, 0) }} C
                            </p>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <p>Level ขั้นต่ำ: {{ $price->min_level }}</p>
                        <p>EXP: x{{ number_format($price->exp_multiplier, 2) }}</p>
                        <p>Coins: x{{ number_format($price->coin_multiplier, 2) }}</p>
                    </div>

                    {{-- Stats --}}
                    @if($upgradeStats)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 mb-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">ยอดขาย</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ number_format($upgradeStats->count) }} ครั้ง</p>
                            <p class="text-xs text-yellow-600 dark:text-yellow-400">{{ number_format($upgradeStats->total_coins, 0) }} C</p>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        <a href="{{ route('admin.star-upgrade.edit', $price) }}"
                           class="flex-1 py-2 text-center bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition">
                            แก้ไข
                        </a>
                        <form action="{{ route('admin.star-upgrade.toggle-active', $price) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="py-2 px-3 {{ $price->is_active ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600' }} text-white rounded-lg text-sm transition"
                                    title="{{ $price->is_active ? 'ปิดการขาย' : 'เปิดการขาย' }}">
                                @if($price->is_active)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Status Badge --}}
                @if(!$price->is_active)
                    <div class="bg-red-500 text-white text-center py-1 text-xs font-semibold">
                        ปิดการขาย
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
        <h3 class="font-bold text-blue-800 dark:text-blue-200 mb-2">วิธีการทำงานของระบบดาว</h3>
        <ul class="list-disc list-inside space-y-1 text-sm text-blue-700 dark:text-blue-300">
            <li>ผู้ใช้สามารถซื้อดาวได้ตามลำดับ (1 → 2 → 3 → 4 → 5)</li>
            <li>ดาวที่แสดง = ค่าสูงสุดระหว่าง "ดาวจาก Level" กับ "ดาวที่ซื้อ"</li>
            <li>ราคา "อัพจากก่อนหน้า" ใช้เมื่อผู้ใช้มีดาวก่อนหน้าอยู่แล้ว (ส่วนลด)</li>
            <li>EXP และ Coin multiplier จะใช้กับดาวที่ซื้อ (ไม่ใช่ดาวจาก Level)</li>
        </ul>
    </div>
</div>
@endsection

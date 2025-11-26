@extends('layouts.admin')

@section('title', 'จัดการ Virtual ID Card')

@section('content')
{{--
/**
 * Admin ID Card Settings Index
 *
 * หน้ารายการการตั้งค่า Virtual ID Card ทั้งหมด
 *
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

<div class="container mx-auto px-4 py-8 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-id-card text-purple-600"></i>
                จัดการ Virtual ID Card
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                ออกแบบและจัดการบัตรประจำตัวเสมือนสำหรับสมาชิกแต่ละระดับ
            </p>
        </div>
        <a href="{{ route('admin.id-card.designer') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
            <i class="fas fa-paint-brush"></i>
            เปิด Designer
        </a>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($ranks as $rank)
            @php
                $setting = $settings->firstWhere('rank_level', $rank->level);
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300">
                {{-- Card Preview --}}
                <div class="h-40 relative overflow-hidden"
                     style="background: linear-gradient(to bottom right, {{ $setting?->background_gradient['from'] ?? $rank->color ?? '#8B5CF6' }}, {{ $setting?->background_gradient['to'] ?? '#EC4899' }});">
                    <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                        <span class="text-5xl">{{ $rank->badge_icon ?? '🏆' }}</span>
                    </div>
                    @if($setting)
                        <div class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-0.5 rounded-full">
                            ตั้งค่าแล้ว
                        </div>
                    @else
                        <div class="absolute top-2 right-2 bg-yellow-500 text-white text-xs px-2 py-0.5 rounded-full">
                            ใช้ค่าเริ่มต้น
                        </div>
                    @endif
                </div>

                {{-- Card Info --}}
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $rank->name_th ?? $rank->name }}
                        </h3>
                        <span class="text-sm text-gray-500">Level {{ $rank->level }}</span>
                    </div>

                    <div class="flex flex-wrap gap-1 mb-4">
                        @for($i = 0; $i < $rank->stars; $i++)
                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                        @endfor
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.id-card.designer', ['rank_level' => $rank->level]) }}"
                           class="flex-1 text-center px-3 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors text-sm font-medium">
                            <i class="fas fa-edit mr-1"></i>
                            แก้ไข
                        </a>
                        <a href="{{ route('admin.id-card.designer', ['rank_level' => $rank->level, 'preview' => 1]) }}"
                           class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm"
                           title="ดูตัวอย่าง">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Default Setting Card --}}
    <div class="mt-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-cog mr-2 text-gray-500"></i>
            การตั้งค่าเริ่มต้น
        </h2>

        @php
            $defaultSetting = $settings->firstWhere('is_default', true);
        @endphp

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $defaultSetting?->name_th ?? $defaultSetting?->name ?? 'Default ID Card' }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        ใช้เมื่อไม่มีการตั้งค่าเฉพาะสำหรับระดับนั้นๆ
                    </p>
                </div>
                <a href="{{ route('admin.id-card.designer') }}"
                   class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium">
                    <i class="fas fa-edit mr-1"></i>
                    แก้ไข Default
                </a>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-4">
            <i class="fas fa-lightbulb mr-2"></i>
            คำแนะนำ
        </h3>
        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5 text-green-500"></i>
                <span>ออกแบบบัตรให้สวยงามขึ้นตามระดับ เพื่อสร้างแรงจูงใจให้สมาชิก</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5 text-green-500"></i>
                <span>ใช้ Gradient หลากสีสำหรับระดับสูงๆ เช่น Diamond, Crown, Legend</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5 text-green-500"></i>
                <span>สามารถอัพโหลดภาพพื้นหลังที่กำหนดเองได้</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5 text-green-500"></i>
                <span>ลากจัดตำแหน่งข้อมูลบนบัตรได้ตามต้องการ</span>
            </li>
        </ul>
    </div>
</div>
@endsection

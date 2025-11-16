@extends('layouts.admin')

@section('title', 'ตั้งค่าสี - Arrow X')

@section('content')
<div class="container-fluid px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('admin.arrow-x-theme.index') }}" class="text-purple-600 dark:text-purple-400 hover:underline mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i> กลับ
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">ตั้งค่าสี</h1>
        </div>

        @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-xl mb-6">
            {{ session('success') }}
        </div>
        @endif

        {{-- Theme Presets --}}
        <div class="bg-gradient-to-r from-purple-50 via-pink-50 to-orange-50 dark:from-purple-900/20 dark:via-pink-900/20 dark:to-orange-900/20 rounded-2xl p-6 mb-6 border-2 border-purple-200 dark:border-purple-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-palette text-white"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Theme Presets</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เลือกชุดสีที่มีให้เพื่อใช้ทันที</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @php
                    $presets = \App\Models\ThemePreset::where('is_active', true)
                        ->orderBy('is_featured', 'desc')
                        ->orderBy('usage_count', 'desc')
                        ->get();
                @endphp

                @foreach($presets as $preset)
                    <form action="{{ route('admin.arrow-x-theme.apply-preset') }}" method="POST" class="group">
                        @csrf
                        <input type="hidden" name="preset_id" value="{{ $preset->id }}">

                        <button type="submit" class="w-full bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300 border-2 border-gray-200 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-400">
                            {{-- Gradient Preview --}}
                            <div class="h-16 rounded-lg mb-3" style="background: linear-gradient(to right, {{ $preset->colors['primary_start'] }}, {{ $preset->colors['primary_middle'] }}, {{ $preset->colors['primary_end'] }});"></div>

                            {{-- Name --}}
                            <h4 class="font-bold text-gray-900 dark:text-white text-sm mb-1">{{ $preset->display_name }}</h4>

                            {{-- Featured Badge --}}
                            @if($preset->is_featured)
                                <span class="inline-block px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 text-xs rounded-full">
                                    <i class="fas fa-star mr-1"></i>แนะนำ
                                </span>
                            @endif

                            {{-- Usage Count --}}
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ใช้ {{ number_format($preset->usage_count) }} ครั้ง
                            </p>
                        </button>
                    </form>
                @endforeach
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mt-4 text-center">
                <i class="fas fa-info-circle mr-1"></i>
                คลิกที่ preset เพื่อใช้งานทันที หรือปรับแต่งเพิ่มเติมด้านล่าง
            </p>
        </div>

        {{-- Divider --}}
        <div class="relative mb-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="px-4 bg-gray-50 dark:bg-gray-900 text-sm text-gray-500 dark:text-gray-400">
                    หรือปรับแต่งเอง
                </span>
            </div>
        </div>

        <form action="{{ route('admin.arrow-x-theme.color-settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Primary Gradient --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Primary Gradient</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Color</label>
                        <input type="color" name="primary_start" value="{{ $color->primary_start ?? '#9333EA' }}" class="w-full h-12 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Middle Color</label>
                        <input type="color" name="primary_middle" value="{{ $color->primary_middle ?? '#EC4899' }}" class="w-full h-12 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Color</label>
                        <input type="color" name="primary_end" value="{{ $color->primary_end ?? '#F97316' }}" class="w-full h-12 rounded-lg">
                    </div>
                </div>
            </div>

            {{-- Status Colors --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Status Colors</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Success</label>
                        <input type="color" name="success_color" value="{{ $color->success_color ?? '#10B981' }}" class="w-full h-12 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Warning</label>
                        <input type="color" name="warning_color" value="{{ $color->warning_color ?? '#F59E0B' }}" class="w-full h-12 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Error</label>
                        <input type="color" name="error_color" value="{{ $color->error_color ?? '#EF4444' }}" class="w-full h-12 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Info</label>
                        <input type="color" name="info_color" value="{{ $color->info_color ?? '#3B82F6' }}" class="w-full h-12 rounded-lg">
                    </div>
                </div>
            </div>

            <input type="hidden" name="scheme_name" value="Arrow X Default">
            <input type="hidden" name="secondary_start" value="#3B82F6">
            <input type="hidden" name="secondary_middle" value="#06B6D4">
            <input type="hidden" name="secondary_end" value="#14B8A6">
            <input type="hidden" name="accent_color" value="#F59E0B">
            <input type="hidden" name="gradient_direction" value="to-right">

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.arrow-x-theme.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 rounded-xl">ยกเลิก</a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white rounded-xl">บันทึก</button>
            </div>
        </form>
    </div>
</div>
@endsection

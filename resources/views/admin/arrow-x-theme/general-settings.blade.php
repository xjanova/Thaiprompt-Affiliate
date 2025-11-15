@extends('layouts.admin')

@section('title', 'การตั้งค่าทั่วไป - Arrow X')

@section('content')
<div class="container-fluid px-4 py-8">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.arrow-x-theme.index') }}" class="text-purple-600 dark:text-purple-400 hover:underline mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i> กลับ
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">การตั้งค่าทั่วไป</h1>
            <p class="text-gray-600 dark:text-gray-400">โลโก้, Favicon, ขนาด, ความโปร่งใส</p>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-xl mb-6">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('admin.arrow-x-theme.general-settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Basic Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ Theme</label>
                        <input type="text" name="theme_name" value="{{ $themeSetting->theme_name }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อแบรนด์</label>
                        <input type="text" name="brand_name" value="{{ $themeSetting->brand_name }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Layout --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Layout</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sidebar Width (px)</label>
                        <input type="number" name="sidebar_width" value="{{ $themeSetting->sidebar_width }}" min="200" max="400" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Navbar Height (px)</label>
                        <input type="number" name="navbar_height" value="{{ $themeSetting->navbar_height }}" min="50" max="100" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Layout Type</label>
                        <select name="layout_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="fluid" {{ $themeSetting->layout_type === 'fluid' ? 'selected' : '' }}>Fluid</option>
                            <option value="fixed" {{ $themeSetting->layout_type === 'fixed' ? 'selected' : '' }}>Fixed</option>
                            <option value="boxed" {{ $themeSetting->layout_type === 'boxed' ? 'selected' : '' }}>Boxed</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Opacity --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ความโปร่งใส (Opacity)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Global Opacity (%)</label>
                        <input type="range" name="global_opacity" value="{{ $themeSetting->global_opacity }}" min="0" max="100" class="w-full">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $themeSetting->global_opacity }}%</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Card Opacity (%)</label>
                        <input type="range" name="card_opacity" value="{{ $themeSetting->card_opacity }}" min="0" max="100" class="w-full">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $themeSetting->card_opacity }}%</span>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.arrow-x-theme.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white rounded-xl hover:shadow-lg transition">
                    <i class="fas fa-save mr-2"></i> บันทึก
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

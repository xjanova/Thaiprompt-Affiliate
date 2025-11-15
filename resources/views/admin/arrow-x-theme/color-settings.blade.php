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

@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบ')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่าทั่วไป</h3>

                <div class="mb-4">
                    <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">ชื่อแอพพลิเคชั่น</label>
                    <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $settings->get('general')->firstWhere('key', 'app_name')->value ?? 'TP-Affiliate') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่า Affiliate</h3>

                <div class="mb-4">
                    <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">อัตราคอมมิชชั่น (%)</label>
                    <input type="number" name="commission_rate" id="commission_rate" min="0" max="100" step="0.01"
                           value="{{ old('commission_rate', $settings->get('affiliate')->firstWhere('key', 'commission_rate')->value ?? 10) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="multi_level_enabled" value="1"
                               {{ old('multi_level_enabled', $settings->get('affiliate')->firstWhere('key', 'multi_level_enabled')->value ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งานระบบหลายระดับ</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

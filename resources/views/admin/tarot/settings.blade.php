@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบทาโร่ต์')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold mb-6">ตั้งค่าระบบทาโร่ต์</h1>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('admin.tarot.settings.update') }}" method="POST" class="glass-fusion rounded-xl shadow p-6">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <h3 class="text-lg font-bold mb-4">การเปิดใช้งาน</h3>
            <label class="flex items-center mb-3">
                <input type="checkbox" name="enable_tarot_system" value="1" {{ $settings['enable_tarot_system'] ?? false ? 'checked' : '' }} class="rounded">
                <span class="ml-2">เปิดใช้งานระบบทาโร่ต์</span>
            </label>
            <label class="flex items-center mb-3">
                <input type="checkbox" name="allow_guest_readings" value="1" {{ $settings['allow_guest_readings'] ?? false ? 'checked' : '' }} class="rounded">
                <span class="ml-2">อนุญาตให้ผู้เยี่ยมชมทำนาย</span>
            </label>
            <label class="flex items-center mb-3">
                <input type="checkbox" name="show_reversed_cards" value="1" {{ $settings['show_reversed_cards'] ?? false ? 'checked' : '' }} class="rounded">
                <span class="ml-2">แสดงไพ่กลับหัว</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="enable_ai_interpretation" value="1" {{ $settings['enable_ai_interpretation'] ?? false ? 'checked' : '' }} class="rounded">
                <span class="ml-2">ใช้ AI ในการแปลความหมาย</span>
            </label>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-bold mb-4">การตั้งค่าทั่วไป</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">จำนวนวันที่เก็บบันทึก</label>
                <input type="number" name="save_readings_days" value="{{ $settings['save_readings_days'] ?? 90 }}" class="w-full rounded-xl border-gray-300 dark:border-gray-600">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">ความเร็วอนิเมชั่น</label>
                <select name="animation_speed" class="w-full rounded-xl border-gray-300 dark:border-gray-600">
                    <option value="slow" {{ ($settings['animation_speed'] ?? 'medium') == 'slow' ? 'selected' : '' }}>ช้า</option>
                    <option value="medium" {{ ($settings['animation_speed'] ?? 'medium') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                    <option value="fast" {{ ($settings['animation_speed'] ?? 'medium') == 'fast' ? 'selected' : '' }}>เร็ว</option>
                </select>
            </div>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold">
            บันทึกการตั้งค่า
        </button>
    </form>
</div>
@endsection

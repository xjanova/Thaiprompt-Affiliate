@extends('layouts.admin')

@section('title', 'แก้ไข: ' . $sectionData['name'])

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.premium-page.index') }}" class="text-gray-600 hover:text-gray-900 transition">
            ← กลับ
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <span class="text-3xl">{{ $sectionData['icon'] }}</span>
                {{ $sectionData['name'] }}
            </h1>
            <p class="text-sm text-gray-600 mt-1">แก้ไขเนื้อหาและการตั้งค่าการแสดงผล</p>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.premium-page.update', $section) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">เนื้อหา</h2>

            <div class="space-y-5">
                @php
                    $enabled = old('enabled', \App\Models\Setting::get("premium_page_{$section}_enabled", true));
                @endphp

                <!-- Enable/Disable -->
                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                    <input type="checkbox" name="enabled" value="1" id="enabled"
                           {{ $enabled ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="enabled" class="ml-3">
                        <span class="text-sm font-medium text-gray-900">เปิดการแสดงผลส่วนนี้ในหน้าแรก</span>
                        <p class="text-xs text-gray-600 mt-1">ปิดเครื่องหมายถูกเพื่อซ่อนส่วนนี้โดยไม่ลบข้อมูล</p>
                    </label>
                </div>

                @if(in_array('title', $sectionData['fields']))
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        หัวข้อหลัก
                    </label>
                    <input type="text" name="title"
                           value="{{ old('title', \App\Models\Setting::get("premium_page_{$section}_title")) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="ใส่หัวข้อหลักของส่วนนี้">
                </div>
                @endif

                @if(in_array('subtitle', $sectionData['fields']))
                <!-- Subtitle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        คำบรรยายรอง
                    </label>
                    <input type="text" name="subtitle"
                           value="{{ old('subtitle', \App\Models\Setting::get("premium_page_{$section}_subtitle")) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="ใส่คำบรรยายรอง">
                </div>
                @endif

                @if(in_array('description', $sectionData['fields']))
                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        รายละเอียด
                    </label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="ใส่รายละเอียดเพิ่มเติม">{{ old('description', \App\Models\Setting::get("premium_page_{$section}_description")) }}</textarea>
                </div>
                @endif

                @if(in_array('cta_text', $sectionData['fields']))
                <!-- CTA Text -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ข้อความปุ่ม Call-to-Action
                    </label>
                    <input type="text" name="cta_text"
                           value="{{ old('cta_text', \App\Models\Setting::get("premium_page_{$section}_cta_text")) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="เช่น: เริ่มต้นฟรีวันนี้, สมัครเลย">
                </div>
                @endif

                @if(in_array('limit', $sectionData['fields']))
                <!-- Limit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        จำนวนที่แสดง
                    </label>
                    <input type="number" name="limit" min="1" max="20"
                           value="{{ old('limit', \App\Models\Setting::get("premium_page_{$section}_limit", 5)) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="mt-1 text-xs text-gray-500">จำนวนรายการที่จะแสดงในส่วนนี้ (1-20)</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Section Preview (Optional) -->
        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6 border border-indigo-100">
            <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                ตัวอย่างการแสดงผล
            </h3>
            <p class="text-sm text-gray-600">
                บันทึกการเปลี่ยนแปลงเพื่อดูผลลัพธ์ที่หน้าแรกของเว็บไซต์
            </p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4">
            <a href="{{ route('admin.premium-page.index') }}"
               class="px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>
@endsection

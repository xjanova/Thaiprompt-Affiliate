@extends('layouts.admin')

@section('title', 'สร้าง Section ใหม่')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.home-sections.index') }}" class="text-gray-600 hover:text-gray-900">
            ← กลับ
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">สร้าง Section ใหม่</h1>
            <p class="text-sm text-gray-600 mt-1">สร้าง section สำหรับหน้าแรก</p>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.home-sections.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ชื่อ Section <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">เช่น hero-main, features-section</p>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ประเภท <span class="text-red-500">*</span>
                        </label>
                        <select name="type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หัวข้อ</label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Subtitle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำบรรยายรอง</label>
                    <input type="text" name="subtitle" value="{{ old('subtitle') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เนื้อหา (JSON หรือ Text)</label>
                    <textarea name="content" rows="8"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono text-sm">{{ old('content') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">สำหรับเนื้อหาเพิ่มเติม สามารถใส่ JSON หรือ HTML</p>
                </div>
            </div>
        </div>

        <!-- Permissions & Schedule -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">การตั้งค่าการแสดงผล</h2>

            <div class="space-y-4">
                <!-- Allowed Roles -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">สิทธิ์การมองเห็น</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach(['admin' => 'Admin', 'manager' => 'Manager', 'affiliate' => 'Affiliate', 'user' => 'User', 'guest' => 'ผู้เยี่ยมชม'] as $role => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="allowed_roles[]" value="{{ $role }}"
                                       class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500">ไม่เลือกเท่ากับทุกคนเห็นได้</p>
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">เริ่มแสดงตั้งแต่</label>
                        <input type="datetime-local" name="schedule_start" value="{{ old('schedule_start') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">หยุดแสดงเมื่อ</label>
                        <input type="datetime-local" name="schedule_end" value="{{ old('schedule_end') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลำดับการแสดง</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Active -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" id="is_active" checked
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">
                        เปิดใช้งานทันที
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.home-sections.index') }}"
               class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                สร้าง Section
            </button>
        </div>
    </form>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'แก้ไข: ' . $homeSection->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.home-sections.index') }}" class="text-gray-600 hover:text-gray-900">
            ← กลับ
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">แก้ไข: {{ $homeSection->name }}</h1>
            <p class="text-sm text-gray-600 mt-1">อัปเดตการตั้งค่า section</p>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.home-sections.update', $homeSection) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

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
                        <input type="text" name="name" value="{{ old('name', $homeSection->name) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ประเภท <span class="text-red-500">*</span>
                        </label>
                        <select name="type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" {{ old('type', $homeSection->type) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หัวข้อ</label>
                    <input type="text" name="title" value="{{ old('title', $homeSection->title) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Subtitle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำบรรยายรอง</label>
                    <input type="text" name="subtitle" value="{{ old('subtitle', $homeSection->subtitle) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เนื้อหา (JSON หรือ Text)</label>
                    <textarea name="content" rows="8"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono text-sm">{{ old('content', is_array($homeSection->content) ? json_encode($homeSection->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $homeSection->content) }}</textarea>
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
                        @php $allowedRoles = old('allowed_roles', $homeSection->allowed_roles ?? []); @endphp
                        @foreach(['admin' => 'Admin', 'manager' => 'Manager', 'affiliate' => 'Affiliate', 'user' => 'User', 'guest' => 'ผู้เยี่ยมชม'] as $role => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="allowed_roles[]" value="{{ $role }}"
                                       {{ in_array($role, $allowedRoles) ? 'checked' : '' }}
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
                        <input type="datetime-local" name="schedule_start"
                               value="{{ old('schedule_start', $homeSection->schedule_start?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">หยุดแสดงเมื่อ</label>
                        <input type="datetime-local" name="schedule_end"
                               value="{{ old('schedule_end', $homeSection->schedule_end?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลำดับการแสดง</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $homeSection->sort_order) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Active -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $homeSection->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">
                        เปิดใช้งาน
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
                บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>
@endsection

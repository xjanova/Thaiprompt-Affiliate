@extends('layouts.admin-v3')

@section('title', 'แก้ไขเทมเพลตการแจ้งเตือน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.notification-templates.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900">
            ← กลับ
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">แก้ไขเทมเพลต: {{ $template->name }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">แก้ไขเทมเพลตการแจ้งเตือน</p>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('admin.notification-templates.update', $template->id) }}" method="POST" class="glass-fusion rounded-xl shadow-md p-6">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- Template Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อเทมเพลต <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $template->name) }}" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="เช่น ยินดีต้อนรับสมาชิกใหม่">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    คำอธิบาย
                </label>
                <input type="text" name="description" value="{{ old('description', $template->description) }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="อธิบายว่าเทมเพลตนี้ใช้สำหรับอะไร">
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภท <span class="text-red-500">*</span>
                </label>
                <select name="type" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- เลือกประเภท --</option>
                    <option value="system" {{ old('type', $template->type) === 'system' ? 'selected' : '' }}>ระบบ</option>
                    <option value="announcement" {{ old('type', $template->type) === 'announcement' ? 'selected' : '' }}>ประกาศ</option>
                    <option value="alert" {{ old('type', $template->type) === 'alert' ? 'selected' : '' }}>แจ้งเตือน</option>
                    <option value="wallet" {{ old('type', $template->type) === 'wallet' ? 'selected' : '' }}>กระเป๋าเงิน</option>
                    <option value="withdrawal" {{ old('type', $template->type) === 'withdrawal' ? 'selected' : '' }}>ถอนเงิน</option>
                    <option value="deposit" {{ old('type', $template->type) === 'deposit' ? 'selected' : '' }}>ฝากเงิน</option>
                    <option value="commission" {{ old('type', $template->type) === 'commission' ? 'selected' : '' }}>คอมมิชชั่น</option>
                </select>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    หัวข้อการแจ้งเตือน <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $template->title) }}" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="เช่น ยินดีต้อนรับสู่ระบบ!">
            </div>

            <!-- Message -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ข้อความ <span class="text-red-500">*</span>
                </label>
                <textarea name="message" rows="4" required
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="พิมพ์ข้อความการแจ้งเตือน...">{{ old('message', $template->message) }}</textarea>
            </div>

            <!-- Icon and Color -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ไอคอน (Emoji)
                    </label>
                    <input type="text" name="icon" value="{{ old('icon', $template->icon) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="เช่น 👋 🎉 💰">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใช้ emoji เพื่อแสดงไอคอน</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สี
                    </label>
                    <select name="color" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- เลือกสี --</option>
                        <option value="blue" {{ old('color', $template->color) === 'blue' ? 'selected' : '' }}>🔵 น้ำเงิน</option>
                        <option value="green" {{ old('color', $template->color) === 'green' ? 'selected' : '' }}>🟢 เขียว</option>
                        <option value="red" {{ old('color', $template->color) === 'red' ? 'selected' : '' }}>🔴 แดง</option>
                        <option value="orange" {{ old('color', $template->color) === 'orange' ? 'selected' : '' }}>🟠 ส้ม</option>
                        <option value="purple" {{ old('color', $template->color) === 'purple' ? 'selected' : '' }}>🟣 ม่วง</option>
                        <option value="gray" {{ old('color', $template->color) === 'gray' ? 'selected' : '' }}>⚪ เทา</option>
                    </select>
                </div>
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ระดับความสำคัญ <span class="text-red-500">*</span>
                </label>
                <select name="priority" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="low" {{ old('priority', $template->priority) === 'low' ? 'selected' : '' }}>ต่ำ</option>
                    <option value="normal" {{ old('priority', $template->priority) === 'normal' ? 'selected' : '' }}>ปกติ</option>
                    <option value="high" {{ old('priority', $template->priority) === 'high' ? 'selected' : '' }}>สูง</option>
                    <option value="urgent" {{ old('priority', $template->priority) === 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                </select>
            </div>

            <!-- Action URL and Text -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        URL ปุ่มดำเนินการ
                    </label>
                    <input type="text" name="action_url" value="{{ old('action_url', $template->action_url) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="เช่น /dashboard">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ข้อความปุ่ม
                    </label>
                    <input type="text" name="action_text" value="{{ old('action_text', $template->action_text) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="เช่น ดูรายละเอียด">
                </div>
            </div>

            <!-- Options -->
            <div class="space-y-3">
                <div class="flex items-center">
                    <input type="checkbox" name="is_important" value="1" id="is_important"
                           {{ old('is_important', $template->is_important) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                    <label for="is_important" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        ทำเครื่องหมายเป็นสำคัญ
                    </label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="show_immediately" value="1" id="show_immediately"
                           {{ old('show_immediately', $template->show_immediately) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                    <label for="show_immediately" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        แสดงป๊อบอัพทันที
                    </label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                    <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        เปิดใช้งานเทมเพลต
                    </label>
                </div>
            </div>

            <!-- Usage Stats -->
            <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 rounded-xl p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    📊 <strong>สถิติการใช้งาน:</strong> เทมเพลตนี้ถูกใช้งานไปแล้ว <strong>{{ $template->usage_count }}</strong> ครั้ง
                </p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.notification-templates.index') }}"
                   class="px-6 py-3 bg-gray-500 text-white rounded-xl hover:bg-gray-600 transition">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
                    บันทึกการแก้ไข
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

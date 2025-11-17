@extends('layouts.admin-v3')

@section('title', 'แก้ไข Email Template')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไข Email Template</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">{{ $template->name }}</p>
        </div>
        <a href="{{ route('admin.email.templates.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-700">
            ← กลับ
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.email.templates.update', $template) }}" method="POST" class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    ชื่อ Template
                </label>
                <input type="text" value="{{ $template->name }}" disabled
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white bg-gray-100/50 dark:bg-gray-800/50 cursor-not-allowed">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ไม่สามารถแก้ไขชื่อได้</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    หมวดหมู่ <span class="text-red-500">*</span>
                </label>
                <select name="category" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                    <option value="system" {{ $template->category === 'system' ? 'selected' : '' }}>System - ระบบ</option>
                    <option value="transactional" {{ $template->category === 'transactional' ? 'selected' : '' }}>Transactional - ธุรกรรม</option>
                    <option value="marketing" {{ $template->category === 'marketing' ? 'selected' : '' }}>Marketing - การตลาด</option>
                </select>
            </div>
        </div>

        <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">เปิดใช้งาน</span>
            </label>
        </div>

        <!-- Subject -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                หัวข้ออีเมล (Subject) <span class="text-red-500">*</span>
            </label>
            <input type="text" name="subject" value="{{ old('subject', $template->subject) }}"
                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('subject') border-red-500 @enderror"
                   required>
            @error('subject')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Body HTML -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                เนื้อหา HTML <span class="text-red-500">*</span>
            </label>
            <textarea name="body_html" rows="15"
                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm @error('body_html') border-red-500 @enderror"
                      required>{{ old('body_html', $template->body_html) }}</textarea>
            @error('body_html')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Body Text -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                เนื้อหา Plain Text
            </label>
            <textarea name="body_text" rows="8"
                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm">{{ old('body_text', $template->body_text) }}</textarea>
        </div>

        <!-- Variables -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                Variables (คั่นด้วยจุลภาค)
            </label>
            <input type="text" name="variables" value="{{ old('variables', is_array($template->variables) ? implode(', ', $template->variables) : '') }}"
                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                คำอธิบาย
            </label>
            <textarea name="description" rows="3"
                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description', $template->description) }}</textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
            <button type="button" onclick="confirmDelete()" class="px-6 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
                🗑️ ลบ Template
            </button>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.email.templates.index') }}" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
                    บันทึกการแก้ไข
                </button>
            </div>
        </div>
    </form>

    <!-- Delete Form -->
    <form id="delete-form" action="{{ route('admin.email.templates.destroy', $template) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
function confirmDelete() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบ Template นี้?')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endsection

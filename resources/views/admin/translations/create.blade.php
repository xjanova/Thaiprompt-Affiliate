@extends('layouts.admin')

@section('title', 'เพิ่มคำแปล')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">เพิ่มคำแปลใหม่</h1>
                <p class="mt-1 text-sm text-gray-600">
                    กำหนดคำแปลเฉพาะที่จะถูกใช้แทน Google Translate
                </p>
            </div>
            <a href="{{ route('admin.translations.index') }}"
               class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.translations.store') }}" method="POST" class="bg-white rounded-lg shadow-md p-6 space-y-6">
        @csrf

        <!-- Source Text -->
        <div>
            <label for="key" class="block text-sm font-medium text-gray-700 mb-2">
                คำหรือวลีภาษาต้นทาง <span class="text-red-500">*</span>
            </label>
            <input type="text" id="key" name="key" value="{{ old('key') }}" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('key') border-red-500 @enderror"
                   placeholder="เช่น: สวัสดี, ยินดีต้อนรับ, ขอบคุณ">
            @error('key')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">
                ข้อความที่ต้องการแปล (ต้องตรงกับข้อความที่ส่งมาจาก API ทุกอักขระ)
            </p>
        </div>

        <!-- Language Pair -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="source_lang" class="block text-sm font-medium text-gray-700 mb-2">
                    ภาษาต้นทาง <span class="text-red-500">*</span>
                </label>
                <select id="source_lang" name="source_lang" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('source_lang') border-red-500 @enderror">
                    <option value="">เลือกภาษา</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang->code }}" {{ old('source_lang', 'th') === $lang->code ? 'selected' : '' }}>
                            {{ $lang->flag_emoji }} {{ $lang->native_name }} ({{ $lang->code }})
                        </option>
                    @endforeach
                </select>
                @error('source_lang')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="target_lang" class="block text-sm font-medium text-gray-700 mb-2">
                    ภาษาปลายทาง <span class="text-red-500">*</span>
                </label>
                <select id="target_lang" name="target_lang" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('target_lang') border-red-500 @enderror">
                    <option value="">เลือกภาษา</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang->code }}" {{ old('target_lang', 'en') === $lang->code ? 'selected' : '' }}>
                            {{ $lang->flag_emoji }} {{ $lang->native_name }} ({{ $lang->code }})
                        </option>
                    @endforeach
                </select>
                @error('target_lang')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Translation -->
        <div>
            <label for="translation" class="block text-sm font-medium text-gray-700 mb-2">
                คำแปล <span class="text-red-500">*</span>
            </label>
            <textarea id="translation" name="translation" rows="3" required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('translation') border-red-500 @enderror"
                      placeholder="คำแปลที่ต้องการใช้...">{{ old('translation') }}</textarea>
            @error('translation')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Context -->
        <div>
            <label for="context" class="block text-sm font-medium text-gray-700 mb-2">
                บริบทหรือคำอธิบาย
            </label>
            <textarea id="context" name="context" rows="2"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="อธิบายบริบทการใช้งาน หรือเหตุผลที่ต้องการคำแปลนี้...">{{ old('context') }}</textarea>
            <p class="mt-1 text-xs text-gray-500">
                ช่วยให้ผู้ดูแลระบบเข้าใจว่าคำแปลนี้ใช้ในบริบทใด
            </p>
        </div>

        <!-- Priority -->
        <div>
            <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                ลำดับความสำคัญ
            </label>
            <input type="number" id="priority" name="priority" value="{{ old('priority', 0) }}" min="0" max="100"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <p class="mt-1 text-xs text-gray-500">
                หากมีคำแปลซ้ำกัน คำแปลที่มีค่ามากกว่าจะถูกใช้ก่อน (0-100, ค่าเริ่มต้น: 0)
            </p>
        </div>

        <!-- Active Status -->
        <div class="flex items-center">
            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                เปิดใช้งานทันที
            </label>
        </div>

        <!-- Example -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-900 mb-2">💡 ตัวอย่างการใช้งาน</h3>
            <ul class="text-sm text-blue-700 space-y-1">
                <li><strong>คำต้นทาง:</strong> "บริการของเรา" (th)</li>
                <li><strong>คำแปล:</strong> "Our Services" (en)</li>
                <li><strong>บริบท:</strong> "ใช้ในเมนูหลัก - ต้องการคำที่เป็นทางการ"</li>
            </ul>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.translations.index') }}"
               class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                บันทึกคำแปล
            </button>
        </div>
    </form>
</div>
@endsection

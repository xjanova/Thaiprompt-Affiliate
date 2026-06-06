@extends('layouts.admin-v3')

@section('title', 'แก้ไขรูปกติกา')

@section('content')
@php
    $scopes = [
        'both' => '🔁 อ่านกติกา + ตอนยกเลิก',
        'all' => '🌐 ทุกจังหวะ (กติกา + ยกเลิก + หมดเวลา)',
        'consent' => '📜 เฉพาะตอนอ่านกติกา',
        'cancel' => '🚫 เฉพาะตอนยกเลิก (เบี้ยว)',
        'expire' => '⏰ เฉพาะตอนหมดเวลาเอง (นุ่ม)',
    ];
@endphp
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">✏️ แก้ไขรูป: {{ $image->name }}</h1>
        <a href="{{ route('admin.fortune.consent.index') }}" class="text-sm text-blue-600 hover:underline">← กลับ</a>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.fortune.consent.update', $image) }}" method="POST" enctype="multipart/form-data"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-4">
        @csrf @method('PUT')

        {{-- รูปปัจจุบัน --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รูปปัจจุบัน</label>
            <img src="{{ $image->image_url }}" alt="{{ $image->name }}"
                 class="max-h-56 rounded-lg border border-gray-200 dark:border-gray-700">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อรูป (ภายใน) <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $image->name) }}" required maxlength="100"
                   class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
            <input type="text" name="description" value="{{ old('description', $image->description) }}" maxlength="500"
                   class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ใช้รูปนี้ตอนไหน <span class="text-red-500">*</span></label>
            <select name="usage_scope" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                @foreach($scopes as $val => $label)
                    <option value="{{ $val }}" @selected(old('usage_scope', $image->usage_scope) === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div x-data="{ preview: null }">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เปลี่ยนรูป (เว้นว่าง = ใช้รูปเดิม)</label>
            <input type="file" name="image" accept="image/png,image/jpeg"
                   @change="preview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null"
                   class="w-full text-sm text-gray-700 dark:text-gray-300">
            <template x-if="preview"><img :src="preview" class="mt-3 max-h-56 rounded-lg border border-gray-200 dark:border-gray-700"></template>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $image->is_active)) class="w-4 h-4 text-blue-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
            </label>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ลำดับ (sort)</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $image->sort_order) }}" min="0"
                       class="w-full px-3 py-1.5 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow">💾 บันทึก</button>
            <a href="{{ route('admin.fortune.consent.index') }}" class="px-5 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection

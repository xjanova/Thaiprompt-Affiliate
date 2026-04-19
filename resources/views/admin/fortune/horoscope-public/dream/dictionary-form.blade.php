{{-- ฟอร์มเพิ่ม/แก้ไขสัญลักษณ์ฝัน --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}"
           class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $pageTitle }}
        </h1>
    </div>

    {{-- Errors --}}
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl">
            <p class="text-red-800 dark:text-red-200 font-semibold mb-2">❌ พบข้อผิดพลาด:</p>
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $symbol
            ? route('admin.fortune.horoscope-public.dream.update', $symbol)
            : route('admin.fortune.horoscope-public.dream.store') }}"
          method="POST">
        @csrf
        @if($symbol)
            @method('PUT')
        @endif

        {{-- ข้อมูลหลัก --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">💭 ข้อมูลสัญลักษณ์</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        หมวดหมู่ <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                    {{ old('category_id', $symbol?->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->icon }} {{ $cat->name_th }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำสำคัญ (keyword) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="keyword_th"
                           value="{{ old('keyword_th', $symbol?->keyword_th) }}"
                           placeholder="เช่น งู, น้ำท่วม, ไฟไหม้"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แสดงเป็น "ฝันเห็น{keyword}" ในหน้า frontend</p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ความหมาย <span class="text-red-500">*</span>
                </label>
                <textarea name="meaning_th" rows="4" required
                          placeholder="อธิบายความหมายของสัญลักษณ์นี้ในฝัน..."
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('meaning_th', $symbol?->meaning_th) }}</textarea>
            </div>
        </div>

        {{-- เลขเด็ด + ลาง --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 เลขเด็ด + ลางบอกเหตุ</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เลขเด็ด
                    </label>
                    <input type="text" name="lucky_numbers"
                           value="{{ old('lucky_numbers', is_array($symbol?->lucky_numbers) ? implode(', ', $symbol->lucky_numbers) : '') }}"
                           placeholder="เช่น 12, 34, 56"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">คั่นด้วย comma หรือ space</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ระดับความเข้มข้น <span class="text-red-500">*</span>
                    </label>
                    <select name="intensity" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ old('intensity', $symbol?->intensity ?? 3) == $i ? 'selected' : '' }}>
                                {{ $i }} {{ str_repeat('⭐', $i) }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-3 cursor-pointer pb-2">
                        <input type="hidden" name="is_good_omen" value="0">
                        <input type="checkbox" name="is_good_omen" value="1"
                               class="w-5 h-5 text-green-600 rounded focus:ring-green-500"
                               {{ old('is_good_omen', $symbol?->is_good_omen ?? true) ? 'checked' : '' }}>
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">✅ ลางดี</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ปิด = ⚠️ ลางระวัง</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- สถานะ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"
                       {{ old('is_active', $symbol?->is_active ?? true) ? 'checked' : '' }}>
                <div>
                    <span class="font-semibold text-gray-900 dark:text-white">เปิดใช้งาน</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">แสดงสัญลักษณ์นี้ในหน้า frontend</p>
                </div>
            </label>
        </div>

        {{-- ปุ่มบันทึก --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}"
               class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                ← ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition font-semibold shadow-lg">
                💾 {{ $symbol ? 'บันทึกการแก้ไข' : 'เพิ่มสัญลักษณ์' }}
            </button>
        </div>
    </form>
</div>
@endsection

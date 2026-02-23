{{-- ฟอร์มแก้ไขราศี --}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}"
           class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $zodiac->symbol_emoji }} แก้ไขราศี{{ $zodiac->name_th }}
            </h1>
        </div>
    </div>

    {{-- Flash Messages --}}
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

    <form action="{{ route('admin.fortune.horoscope-public.zodiac.update', $zodiac) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- ข้อมูลหลัก --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📝 ข้อมูลหลัก</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อราศี (ไทย) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name_th" value="{{ old('name_th', $zodiac->name_th) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อราศี (อังกฤษ) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name_en" value="{{ old('name_en', $zodiac->name_en) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Symbol Emoji <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="symbol_emoji" value="{{ old('symbol_emoji', $zodiac->symbol_emoji) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ช่วงวันเกิดแสดงผล (ไทย)
                    </label>
                    <input type="text" name="date_range_display_th" value="{{ old('date_range_display_th', $zodiac->date_range_display_th) }}"
                           placeholder="เช่น 21 มี.ค. — 19 เม.ย."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ธาตุ <span class="text-red-500">*</span>
                    </label>
                    <select name="element"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        @foreach(['ไฟ', 'ดิน', 'ลม', 'น้ำ'] as $el)
                            <option value="{{ $el }}" {{ old('element', $zodiac->element) === $el ? 'selected' : '' }}>{{ $el }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ดาวประจำราศี <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="ruling_planet" value="{{ old('ruling_planet', $zodiac->ruling_planet) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คุณภาพ (Quality)
                    </label>
                    <select name="quality"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- ไม่ระบุ --</option>
                        @foreach(['Cardinal', 'Fixed', 'Mutable'] as $q)
                            <option value="{{ $q }}" {{ old('quality', $zodiac->quality) === $q ? 'selected' : '' }}>{{ $q }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลำดับ <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $zodiac->sort_order) }}"
                           min="0"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                </div>
            </div>
        </div>

        {{-- สี --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎨 สีและ Gradient</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สีหลัก (hex) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="color" name="color_hex" value="{{ old('color_hex', $zodiac->color_hex) }}"
                               class="w-12 h-10 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                        <input type="text" value="{{ old('color_hex', $zodiac->color_hex) }}" disabled
                               class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gradient From</label>
                    <input type="text" name="gradient_from" value="{{ old('gradient_from', $zodiac->gradient_from) }}"
                           placeholder="#818cf8"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gradient To</label>
                    <input type="text" name="gradient_to" value="{{ old('gradient_to', $zodiac->gradient_to) }}"
                           placeholder="#6366f1"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>
        </div>

        {{-- คำอธิบาย --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📄 คำอธิบาย</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบายราศี</label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('description_th', $zodiac->description_th) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">บุคลิกภาพ</label>
                    <textarea name="personality_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('personality_th', $zodiac->personality_th) }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จุดแข็ง</label>
                        <textarea name="strengths_th" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('strengths_th', $zodiac->strengths_th) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จุดอ่อน</label>
                        <textarea name="weaknesses_th" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('weaknesses_th', $zodiac->weaknesses_th) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- สถานะ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"
                       {{ old('is_active', $zodiac->is_active) ? 'checked' : '' }}>
                <div>
                    <span class="font-semibold text-gray-900 dark:text-white">เปิดใช้งาน</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">แสดงราศีนี้ในหน้า frontend</p>
                </div>
            </label>
        </div>

        {{-- ปุ่มบันทึก --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}"
               class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                ← ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition font-semibold shadow-lg">
                💾 บันทึก
            </button>
        </div>
    </form>
</div>
@endsection

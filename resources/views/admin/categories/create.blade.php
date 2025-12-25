@extends('layouts.admin-v3')

@section('title', 'สร้างหมวดหมู่ใหม่')

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายการหมวดหมู่
        </a>
    </div>

    {{-- Premium Hero Header (Green-Emerald-Teal for Create) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-500 via-emerald-500 to-teal-600 dark:from-green-700 dark:via-emerald-700 dark:to-teal-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-folder-plus"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-folder-plus text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">สร้างหมวดหมู่ใหม่</h1>
                    <p class="text-green-100 text-lg mt-1">เพิ่มหมวดหมู่ใหม่สำหรับจัดระเบียบเนื้อหา</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        {{-- Error Messages --}}
        @if($errors->any())
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl p-6 mb-6">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-red-900 dark:text-red-200 mb-2">มีข้อผิดพลาด:</h3>
                        <ul class="list-disc list-inside text-sm text-red-800 dark:text-red-300 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form Card --}}
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-8">
                @csrf

                {{-- Basic Information Section --}}
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-info-circle text-green-600 dark:text-green-400"></i>
                        ข้อมูลพื้นฐาน
                    </h2>

                    <div class="space-y-6">
                        {{-- Category Name --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อหมวดหมู่ <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   placeholder="เช่น AI Tools, Digital Marketing"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('name') border-red-500 @enderror">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ชื่อหมวดหมู่ที่จะแสดงให้ผู้ใช้เห็น</p>
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Slug (URL)
                            </label>
                            <input type="text"
                                   id="slug"
                                   name="slug"
                                   value="{{ old('slug') }}"
                                   placeholder="เช่น ai-tools (ถ้าไม่ระบุจะสร้างอัตโนมัติ)"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('slug') border-red-500 @enderror">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">URL สำหรับหมวดหมู่นี้ ควรเป็นตัวอักษรภาษาอังกฤษและขีดกลาง</p>
                            @error('slug')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบาย
                            </label>
                            <textarea id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="อธิบายเกี่ยวกับหมวดหมู่นี้"
                                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คำอธิบายสั้นๆ เกี่ยวกับหมวดหมู่นี้</p>
                            @error('description')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Appearance Section --}}
                <div class="pt-6 border-t border-gray-200 dark:border-white/10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-palette text-purple-600 dark:text-purple-400"></i>
                        รูปแบบและการแสดงผล
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Icon --}}
                        <div>
                            <label for="icon-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ไอคอน (Emoji)
                            </label>
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 rounded-xl flex items-center justify-center text-4xl shadow-lg bg-white/60 dark:bg-white/10 backdrop-blur-sm border-2 border-gray-200 dark:border-white/20" id="icon-preview-box" style="background: {{ old('color', '#10b981') }}22;">
                                    <span id="icon-preview">{{ old('icon', '📚') }}</span>
                                </div>
                                <div class="flex-1">
                                    <input type="text"
                                           id="icon-input"
                                           name="icon"
                                           value="{{ old('icon', '📚') }}"
                                           maxlength="50"
                                           placeholder="เช่น 📚, 🤖, 💡"
                                           class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใส่ Emoji ที่ต้องการใช้เป็นไอคอน</p>
                                </div>
                            </div>
                            @error('icon')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Color --}}
                        <div>
                            <label for="color-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                สีประจำหมวดหมู่
                            </label>
                            <input type="color"
                                   id="color-input"
                                   name="color"
                                   value="{{ old('color', '#10b981') }}"
                                   class="w-full h-14 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 rounded-xl cursor-pointer">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เลือกสีที่จะใช้แสดงกับหมวดหมู่นี้</p>
                            @error('color')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Display Order --}}
                        <div class="md:col-span-2">
                            <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ลำดับการแสดงผล
                            </label>
                            <input type="number"
                                   id="order"
                                   name="order"
                                   value="{{ old('order', 0) }}"
                                   min="0"
                                   step="1"
                                   placeholder="0"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ตัวเลขที่น้อยกว่าจะแสดงก่อน (0 = แรกสุด)</p>
                            @error('order')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Settings Section --}}
                <div class="pt-6 border-t border-gray-200 dark:border-white/10">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-cog text-blue-600 dark:text-blue-400"></i>
                        การตั้งค่า
                    </h2>

                    <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm rounded-xl p-6 border-2 border-gray-200 dark:border-white/20">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   id="is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500">
                            <div>
                                <span class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-toggle-on text-green-600 dark:text-green-400"></i>
                                    เปิดใช้งานหมวดหมู่นี้
                                </span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">หมวดหมู่ที่ปิดใช้งานจะไม่แสดงในส่วนหน้าเว็บไซต์</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-white/10">
                    <a href="{{ route('admin.categories.index') }}"
                       class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-white/60 dark:bg-white/10 backdrop-blur-sm border border-gray-200 dark:border-white/20 rounded-xl hover:bg-gray-100 dark:hover:bg-white/20 transition-all duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        ยกเลิก
                    </a>

                    <button type="submit"
                            style="background: var(--arrow-x-primary-gradient)"
                            class="px-6 py-2.5 text-white font-semibold rounded-xl focus:outline-none shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                        <i class="fas fa-plus mr-2"></i>
                        สร้างหมวดหมู่
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Update icon preview
document.getElementById('icon-input').addEventListener('input', function() {
    document.getElementById('icon-preview').textContent = this.value || '📚';
});

// Update icon preview background color
document.getElementById('color-input').addEventListener('change', function() {
    const color = this.value;
    const preview = document.getElementById('icon-preview-box');
    preview.style.background = color + '22';
});
</script>
@endpush

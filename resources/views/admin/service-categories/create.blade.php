@extends('layouts.admin')

@section('title', 'เพิ่มหมวดหมู่บริการใหม่')

@section('content')
<div class="max-w-4xl mx-auto" x-data="categoryForm()">
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('admin.service-categories.index') }}"
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors mb-4">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปหน้ารายการ</span>
        </a>

        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                <i class="fas fa-plus-circle mr-3"></i>เพิ่มหมวดหมู่บริการใหม่
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                สร้างหมวดหมู่บริการใหม่สำหรับจัดกลุ่มบริการต่างๆ
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.service-categories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="space-y-6">
            {{-- Basic Information --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-info-circle mr-2 text-purple-600 dark:text-purple-400"></i>
                        ข้อมูลพื้นฐาน
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Category Name --}}
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อหมวดหมู่ <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               required
                               @input="updateSlug()"
                               x-model="form.name"
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all duration-200"
                               placeholder="เช่น นวดและสปา">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label for="slug" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            URL Slug <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="slug"
                               name="slug"
                               value="{{ old('slug') }}"
                               required
                               x-model="form.slug"
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all duration-200 font-mono"
                               placeholder="massage-and-spa">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle mr-1"></i>
                            URL slug จะถูกสร้างอัตโนมัติจากชื่อหมวดหมู่
                        </p>
                        @error('slug')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย
                        </label>
                        <textarea id="description"
                                  name="description"
                                  rows="3"
                                  class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all duration-200"
                                  placeholder="คำอธิบายหมวดหมู่บริการ">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Appearance Settings --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-palette mr-2 text-purple-600 dark:text-purple-400"></i>
                        การแสดงผล
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Icon (Emoji) --}}
                    <div>
                        <label for="icon" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ไอคอน (Emoji)
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="text"
                                   id="icon"
                                   name="icon"
                                   value="{{ old('icon') }}"
                                   maxlength="10"
                                   x-model="form.icon"
                                   class="w-32 px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white text-center text-2xl transition-all duration-200"
                                   placeholder="💆">
                            <div class="flex-1">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    ตัวอย่าง: 💆 (นวด), 🧹 (ทำความสะอาด), 🚚 (จัดส่ง), 🍕 (อาหาร)
                                </p>
                            </div>
                        </div>
                        @error('icon')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Color Picker --}}
                    <div>
                        <label for="color" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            สีประจำหมวดหมู่
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="color"
                                   id="color"
                                   name="color"
                                   value="{{ old('color', '#9333EA') }}"
                                   x-model="form.color"
                                   class="h-12 w-24 rounded-xl border-2 border-gray-300 dark:border-gray-600 cursor-pointer">
                            <div class="flex-1">
                                <input type="text"
                                       :value="form.color"
                                       @input="form.color = $event.target.value"
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white font-mono transition-all duration-200"
                                       placeholder="#9333EA">
                            </div>
                            {{-- Color Preview --}}
                            <div class="w-24 h-12 rounded-xl shadow-lg flex items-center justify-center text-2xl"
                                 :style="`background: linear-gradient(135deg, ${form.color}CC, ${form.color}FF);`">
                                <span x-text="form.icon || '📦'"></span>
                            </div>
                        </div>
                        @error('color')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Image Upload --}}
                    <div>
                        <label for="image" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            รูปภาพหมวดหมู่
                        </label>
                        <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <input type="file"
                                       id="image"
                                       name="image"
                                       accept="image/*"
                                       @change="previewImage($event)"
                                       class="block w-full text-sm text-gray-900 dark:text-white
                                              file:mr-4 file:py-3 file:px-6
                                              file:rounded-xl file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-purple-50 dark:file:bg-purple-900/30
                                              file:text-purple-700 dark:file:text-purple-400
                                              hover:file:bg-purple-100 dark:hover:file:bg-purple-900/50
                                              file:cursor-pointer cursor-pointer
                                              border border-gray-300 dark:border-gray-600 rounded-xl
                                              bg-white dark:bg-gray-700
                                              transition-all duration-200">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    รองรับ PNG, JPG, GIF (แนะนำขนาด 500x500px)
                                </p>
                            </div>
                            {{-- Image Preview --}}
                            <div x-show="form.imagePreview"
                                 class="w-24 h-24 rounded-xl border-2 border-gray-300 dark:border-gray-600 overflow-hidden bg-gray-100 dark:bg-gray-700">
                                <img :src="form.imagePreview" alt="Preview" class="w-full h-full object-cover">
                            </div>
                        </div>
                        @error('image')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Settings --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-cog mr-2 text-purple-600 dark:text-purple-400"></i>
                        การตั้งค่า
                    </h2>
                </div>

                <div class="p-6 space-y-4">
                    {{-- Sort Order --}}
                    <div>
                        <label for="sort_order" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ลำดับการแสดงผล
                        </label>
                        <input type="number"
                               id="sort_order"
                               name="sort_order"
                               value="{{ old('sort_order', 0) }}"
                               min="0"
                               class="w-full md:w-48 px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle mr-1"></i>
                            ตัวเลขน้อยจะแสดงก่อน (หรือลากเรียงลำดับในหน้ารายการ)
                        </p>
                        @error('sort_order')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Is Active Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div>
                            <label for="is_active" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                เปิดใช้งานหมวดหมู่
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                หมวดหมู่ที่ปิดใช้งานจะไม่แสดงในหน้าเว็บไซต์
                            </p>
                        </div>
                        <div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <i class="fas fa-save"></i>
                    <span>บันทึกหมวดหมู่</span>
                </button>

                <a href="{{ route('admin.service-categories.index') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl font-semibold shadow hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-times"></i>
                    <span>ยกเลิก</span>
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function categoryForm() {
    return {
        form: {
            name: '',
            slug: '',
            icon: '',
            color: '#9333EA',
            imagePreview: null
        },

        updateSlug() {
            // แปลงภาษาไทยเป็น Romanization (ง่ายๆ)
            let slug = this.form.name.toLowerCase()
                .replace(/\s+/g, '-')           // แทนที่ช่องว่างด้วย -
                .replace(/[^\w\-ก-๙]+/g, '')    // เอาอักษรพิเศษออก
                .replace(/\-\-+/g, '-')         // แทนที่ -- หลายตัวด้วย -
                .replace(/^-+/, '')             // ตัด - หน้า
                .replace(/-+$/, '');            // ตัด - หลัง

            this.form.slug = slug;
        },

        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.form.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    }
}
</script>
@endpush

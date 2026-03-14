{{--
    ลงขายสินค้าใหม่ - ตลาดสดไทยพร๊อม

    ตัวแปรที่ใช้:
    - $seller: ข้อมูลผู้ขาย (model)
    - $categories: หมวดหมู่สินค้า (collection)
--}}
@extends('layouts.taladsod')

@section('title', 'ลงขายสินค้าใหม่ - ตลาดสดไทยพร๊อม')

@section('content')

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        {{-- ===== หัวข้อ ===== --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                    &#x1F4E6; ลงขายสินค้าใหม่
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    กรอกข้อมูลสินค้าเพื่อเริ่มขายในตลาดสดไทยพร๊อม
                </p>
            </div>
            <a href="{{ route('taladsod.seller.dashboard') }}"
               class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> กลับแดชบอร์ด
            </a>
        </div>

        {{-- ===== ฟอร์มลงขายสินค้า ===== --}}
        <form method="POST"
              action="{{ route('taladsod.listing.store') }}"
              enctype="multipart/form-data"
              x-data="listingForm()"
              class="space-y-6">
            @csrf

            {{-- การ์ดข้อมูลสินค้า --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 sm:p-6 space-y-5">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-info-circle text-green-500"></i> ข้อมูลสินค้า
                </h2>

                {{-- ชื่อสินค้า --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        ชื่อสินค้า <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="title"
                           name="title"
                           value="{{ old('title') }}"
                           required
                           placeholder="เช่น ผักคะน้าสด จากสวนตัว"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors placeholder-gray-400 dark:placeholder-gray-500">
                    @error('title')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- คำอธิบาย --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        คำอธิบายสินค้า
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="4"
                              placeholder="อธิบายรายละเอียดสินค้า เช่น แหล่งที่มา ความสดใหม่ วิธีการปลูก..."
                              class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors resize-none placeholder-gray-400 dark:placeholder-gray-500">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- หมวดหมู่ --}}
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        หมวดหมู่ <span class="text-red-500">*</span>
                    </label>
                    <select id="category_id"
                            name="category_id"
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        @if(isset($categories))
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('category_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ราคา + หน่วย --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            ราคา (บาท) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-medium">&#x0E3F;</span>
                            <input type="number"
                                   id="price"
                                   name="price"
                                   value="{{ old('price') }}"
                                   required
                                   min="0"
                                   step="0.01"
                                   placeholder="0"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        </div>
                        @error('price')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            หน่วย <span class="text-red-500">*</span>
                        </label>
                        <select id="unit"
                                name="unit"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                            <option value="กก." {{ old('unit') === 'กก.' ? 'selected' : '' }}>กิโลกรัม (กก.)</option>
                            <option value="ขีด" {{ old('unit') === 'ขีด' ? 'selected' : '' }}>ขีด</option>
                            <option value="กรัม" {{ old('unit') === 'กรัม' ? 'selected' : '' }}>กรัม</option>
                            <option value="ถุง" {{ old('unit') === 'ถุง' ? 'selected' : '' }}>ถุง</option>
                            <option value="กำ" {{ old('unit') === 'กำ' ? 'selected' : '' }}>กำ</option>
                            <option value="ชิ้น" {{ old('unit') === 'ชิ้น' ? 'selected' : '' }}>ชิ้น</option>
                            <option value="ลูก" {{ old('unit') === 'ลูก' ? 'selected' : '' }}>ลูก</option>
                            <option value="ตัว" {{ old('unit') === 'ตัว' ? 'selected' : '' }}>ตัว</option>
                            <option value="แพ็ค" {{ old('unit') === 'แพ็ค' ? 'selected' : '' }}>แพ็ค</option>
                            <option value="ลัง" {{ old('unit') === 'ลัง' ? 'selected' : '' }}>ลัง</option>
                        </select>
                        @error('unit')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- จำนวนสินค้า --}}
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        จำนวนสินค้าที่มี
                    </label>
                    <input type="number"
                           id="quantity"
                           name="quantity"
                           value="{{ old('quantity') }}"
                           min="0"
                           placeholder="เช่น 50"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                    @error('quantity')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- สินค้าออร์แกนิค --}}
                <div>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="is_organic"
                               value="1"
                               {{ old('is_organic') ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-green-500 focus:ring-green-500 focus:ring-offset-0">
                        <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors flex items-center gap-1.5">
                            <i class="fas fa-leaf text-green-500"></i> สินค้านี้เป็นออร์แกนิค / ปลอดสารเคมี
                        </span>
                    </label>
                </div>
            </div>

            {{-- การ์ดอัพโหลดรูปภาพ --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 sm:p-6 space-y-5">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-camera text-green-500"></i> รูปภาพสินค้า
                </h2>

                {{-- พื้นที่ลากวาง --}}
                <div class="relative"
                     @dragover.prevent="$el.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20')"
                     @dragleave.prevent="$el.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20')"
                     @drop.prevent="$el.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20'); handleImageSelect({target: {files: $event.dataTransfer.files}})">
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-8 text-center hover:border-green-400 dark:hover:border-green-500 transition-colors cursor-pointer"
                         @click="$refs.imageInput.click()">
                        <input type="file"
                               x-ref="imageInput"
                               name="images[]"
                               multiple
                               accept="image/*"
                               class="hidden"
                               @change="handleImageSelect($event)">
                        <div class="text-5xl mb-3">&#x1F4F7;</div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ลากรูปภาพมาวางที่นี่ หรือ <span class="text-green-600 dark:text-green-400 underline">เลือกไฟล์</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            รองรับ JPG, PNG ขนาดไม่เกิน 5MB ต่อรูป (สูงสุด 5 รูป)
                        </p>
                    </div>
                </div>

                {{-- แสดงตัวอย่างรูปภาพ --}}
                <div x-show="previewUrls.length > 0" x-cloak class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                    <template x-for="(url, index) in previewUrls" :key="index">
                        <div class="relative aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 group">
                            <img :src="url" alt="" class="w-full h-full object-cover">
                            <button type="button"
                                    @click="removeImage(index)"
                                    class="absolute top-1 right-1 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs">
                                <i class="fas fa-times"></i>
                            </button>
                            <div x-show="index === 0" class="absolute bottom-0 left-0 right-0 bg-green-500/80 text-white text-xs text-center py-0.5">
                                รูปหลัก
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- การ์ดตำแหน่งที่ตั้ง --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 sm:p-6 space-y-5"
                 x-data="geolocation()">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-green-500"></i> ตำแหน่งที่ตั้ง
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    ระบุตำแหน่งที่ตั้งเพื่อให้ลูกค้าค้นหาสินค้าของคุณได้ง่ายขึ้น
                </p>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ละติจูด</label>
                        <input type="text"
                               name="latitude"
                               :value="lat"
                               readonly
                               placeholder="กดปุ่มระบุตำแหน่ง"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ลองจิจูด</label>
                        <input type="text"
                               name="longitude"
                               :value="lng"
                               readonly
                               placeholder="กดปุ่มระบุตำแหน่ง"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                    </div>
                </div>

                <button type="button"
                        @click="getLocation()"
                        :disabled="loading"
                        class="w-full py-3 bg-green-50 dark:bg-green-900/30 hover:bg-green-100 dark:hover:bg-green-900/50 text-green-700 dark:text-green-400 rounded-xl font-medium transition-colors flex items-center justify-center gap-2 text-sm border border-green-200 dark:border-green-800"
                        :class="{ 'animate-pulse': loading }">
                    <i class="fas fa-location-crosshairs"></i>
                    <span x-text="loading ? 'กำลังค้นหาตำแหน่ง...' : (lat ? 'อัพเดทตำแหน่ง (พบตำแหน่งแล้ว)' : 'ระบุตำแหน่งปัจจุบัน')"></span>
                </button>

                <template x-if="error">
                    <p class="text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-triangle"></i> <span x-text="error"></span>
                    </p>
                </template>

                <template x-if="lat && lng">
                    <p class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                        <i class="fas fa-check-circle"></i> พบตำแหน่งของคุณแล้ว
                    </p>
                </template>
            </div>

            {{-- ปุ่มบันทึก --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit"
                        class="flex-1 py-4 bg-green-500 hover:bg-green-600 text-white text-lg font-bold rounded-2xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] flex items-center justify-center gap-2">
                    <i class="fas fa-check-circle"></i> ลงขายสินค้า
                </button>
                <a href="{{ route('taladsod.seller.dashboard') }}"
                   class="sm:w-40 py-4 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-center font-medium rounded-2xl transition-colors">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>

@endsection

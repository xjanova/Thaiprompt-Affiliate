@extends('layouts.app')

@section('title', 'รีวิวสินค้า')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-yellow-50/20 to-amber-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-yellow-500 via-amber-500 to-orange-500 dark:from-yellow-600 dark:via-amber-600 dark:to-orange-600">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="container mx-auto px-4 py-8 relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('orders.show', $order->id) }}"
                   class="p-2 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl border border-white/30 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full border border-white/30">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="font-semibold text-white">รีวิวสินค้า</span>
                </div>
            </div>

            <h1 class="text-3xl md:text-4xl font-black text-white mb-2 tracking-tight drop-shadow-lg">
                แบ่งปันประสบการณ์ของคุณ
            </h1>
            <p class="text-xl text-yellow-100 font-medium">
                รีวิวของคุณจะช่วยให้ผู้ซื้อรายอื่นตัดสินใจได้ดียิ่งขึ้น
            </p>
        </div>

        <!-- Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full dark:hidden">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(249, 250, 251)"/>
            </svg>
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full hidden dark:block">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(17, 24, 39)"/>
            </svg>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 -mt-6 relative z-10">
        <div class="max-w-3xl mx-auto">

            <!-- Product Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden mb-6">
                <div class="p-6">
                    <div class="flex gap-4">
                        <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden flex-shrink-0">
                            @if($item->product_image)
                                <img src="{{ asset($item->product_image) }}"
                                     alt="{{ $item->product_name }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                {{ $item->product_name }}
                            </h2>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                SKU: {{ $item->product_sku }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                คำสั่งซื้อ: {{ $order->order_number }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Form -->
            <form action="{{ route('orders.review.submit', [$order->id, $item->id]) }}" method="POST" enctype="multipart/form-data" x-data="reviewForm()">
                @csrf

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-500 to-amber-500 dark:from-yellow-600 dark:to-amber-600 text-white px-6 py-4">
                        <h3 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            เขียนรีวิว
                        </h3>
                    </div>

                    <div class="p-6 space-y-6">

                        <!-- Rating -->
                        <div>
                            <label class="block text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">
                                คุณให้คะแนนสินค้านี้เท่าไหร่? <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <template x-for="star in 5" :key="star">
                                    <button type="button"
                                            @click="rating = star"
                                            class="focus:outline-none transition-all transform hover:scale-110">
                                        <svg class="w-12 h-12 transition-colors"
                                             :class="star <= rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                                             fill="currentColor"
                                             viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                </template>
                                <span x-show="rating > 0"
                                      class="ml-3 text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                                      x-text="rating + ' ดาว'"></span>
                            </div>
                            <input type="hidden" name="rating" x-model="rating" required>
                            @error('rating')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-bold text-gray-900 dark:text-gray-100 mb-2">
                                หัวเรื่อง (ไม่บังคับ)
                            </label>
                            <input type="text"
                                   name="title"
                                   x-model="title"
                                   placeholder="เช่น สินค้าดีมาก คุณภาพเยี่ยม"
                                   maxlength="200"
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-yellow-500 dark:focus:border-yellow-400 focus:ring-4 focus:ring-yellow-100 dark:focus:ring-yellow-900/50 transition-all placeholder-gray-400 dark:placeholder-gray-500">
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-right">
                                <span x-text="title.length"></span>/200
                            </div>
                        </div>

                        <!-- Comment -->
                        <div>
                            <label class="block text-sm font-bold text-gray-900 dark:text-gray-100 mb-2">
                                รีวิวของคุณ <span class="text-red-500">*</span>
                            </label>
                            <textarea name="comment"
                                      x-model="comment"
                                      rows="6"
                                      placeholder="แบ่งปันประสบการณ์การใช้งานสินค้า คุณภาพ บริการจัดส่ง หรือสิ่งที่คุณประทับใจ..."
                                      maxlength="1000"
                                      required
                                      class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-yellow-500 dark:focus:border-yellow-400 focus:ring-4 focus:ring-yellow-100 dark:focus:ring-yellow-900/50 transition-all placeholder-gray-400 dark:placeholder-gray-500"></textarea>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-right">
                                <span x-text="comment.length"></span>/1000
                            </div>
                            @error('comment')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Images Upload -->
                        <div>
                            <label class="block text-sm font-bold text-gray-900 dark:text-gray-100 mb-2">
                                อัปโหลดรูปภาพ (ไม่บังคับ)
                            </label>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                อัปโหลดได้สูงสุด 5 ภาพ (แต่ละภาพไม่เกิน 2MB)
                            </p>

                            <div class="space-y-3">
                                <!-- Upload Button -->
                                <label class="flex items-center justify-center w-full px-6 py-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                            คลิกเพื่ออัปโหลดรูปภาพ
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            PNG, JPG, JPEG (สูงสุด 2MB)
                                        </p>
                                    </div>
                                    <input type="file"
                                           name="images[]"
                                           accept="image/png,image/jpeg,image/jpg"
                                           multiple
                                           @change="handleImages"
                                           class="hidden"
                                           max="5">
                                </label>

                                <!-- Preview Images -->
                                <div x-show="imageFiles.length > 0"
                                     class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <template x-for="(image, index) in imageFiles" :key="index">
                                        <div class="relative group">
                                            <img :src="image.url"
                                                 :alt="image.name"
                                                 class="w-full h-32 object-cover rounded-xl border-2 border-gray-200 dark:border-gray-600">
                                            <button type="button"
                                                    @click="removeImage(index)"
                                                    class="absolute -top-2 -right-2 w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full shadow-lg flex items-center justify-center transition-all opacity-0 group-hover:opacity-100">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            @error('images')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Guidelines -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 dark:border-blue-400 p-4 rounded-r-xl">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div class="text-sm text-blue-800 dark:text-blue-300">
                                    <p class="font-semibold mb-1">แนวทางการเขียนรีวิว:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>เขียนรีวิวที่ตรงไปตรงมาและเป็นประโยชน์</li>
                                        <li>แบ่งปันประสบการณ์การใช้งานจริงของคุณ</li>
                                        <li>หลีกเลี่ยงภาษาไม่สุภาพหรือเนื้อหาไม่เหมาะสม</li>
                                        <li>รีวิวของคุณจะช่วยให้ผู้ซื้อรายอื่นตัดสินใจได้ดีขึ้น</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t-2 border-gray-200 dark:border-gray-600">
                            <button type="submit"
                                    class="flex-1 px-6 py-4 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                ส่งรีวิว
                            </button>

                            <a href="{{ route('orders.show', $order->id) }}"
                               class="px-6 py-4 border-2 border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 text-gray-700 dark:text-gray-300 font-bold rounded-xl transition-all flex items-center justify-center gap-2 bg-white dark:bg-gray-700">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                ยกเลิก
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function reviewForm() {
    return {
        rating: 0,
        title: '',
        comment: '',
        imageFiles: [],

        handleImages(event) {
            const files = Array.from(event.target.files);

            // Limit to 5 images
            if (this.imageFiles.length + files.length > 5) {
                alert('คุณสามารถอัปโหลดได้สูงสุด 5 ภาพเท่านั้น');
                return;
            }

            files.forEach(file => {
                // Check file size (2MB)
                if (file.size > 2048000) {
                    alert(`ไฟล์ ${file.name} มีขนาดใหญ่เกิน 2MB`);
                    return;
                }

                // Create preview URL
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imageFiles.push({
                        name: file.name,
                        url: e.target.result,
                        file: file
                    });
                };
                reader.readAsDataURL(file);
            });

            // Reset input
            event.target.value = '';
        },

        removeImage(index) {
            this.imageFiles.splice(index, 1);
        }
    }
}
</script>
@endsection

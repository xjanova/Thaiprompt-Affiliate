@extends('layouts.admin-v3')

@section('title', 'แก้ไขโฆษณา')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    adType: '{{ $advertisement->type }}',
    previewImage: '{{ $advertisement->image_url ? Storage::url($advertisement->image_url) : null }}',
    previewVideo: '{{ $advertisement->video_url ? Storage::url($advertisement->video_url) : null }}'
}">
    <!-- Header -->
    <div class="bg-gradient-to-r from-yellow-500 via-orange-500 to-red-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-edit mr-3"></i>
                    แก้ไขโฆษณา
                </h1>
                <p class="text-orange-100">{{ $advertisement->title }}</p>
            </div>
            <a href="{{ route('admin.pos.advertisements.show', $advertisement) }}"
               class="bg-white text-orange-600 px-4 py-2 rounded-lg hover:bg-orange-50 transition-all">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับ
            </a>
        </div>
    </div>

    <form action="{{ route('admin.pos.advertisements.update', $advertisement) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-info-circle text-indigo-600 mr-2"></i>
                ข้อมูลพื้นฐาน
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อโฆษณา <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $advertisement->title) }}" required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">{{ old('description', $advertisement->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ร้านค้า</label>
                        <select name="store_id" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <option value="">ทั้งหมด</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ old('store_id', $advertisement->store_id) == $store->id ? 'selected' : '' }}>
                                    {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท <span class="text-red-500">*</span></label>
                        <select name="type" x-model="adType" required class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <option value="image">รูปภาพ</option>
                            <option value="video">วิดีโอ</option>
                            <option value="html">HTML</option>
                            <option value="promotion">โปรโมชั่น</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Media Upload -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-photo-video text-purple-600 mr-2"></i>
                สื่อโฆษณา
            </h2>

            <div x-show="adType === 'image'" class="space-y-4">
                @if($advertisement->image_url)
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">รูปภาพปัจจุบัน:</p>
                        <img src="{{ Storage::url($advertisement->image_url) }}" class="max-w-xs rounded-lg shadow-md">
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อัปโหลดรูปภาพใหม่</label>
                    <input type="file" name="image" accept="image/*"
                           @change="previewImage = URL.createObjectURL($event.target.files[0])"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div x-show="previewImage !== '{{ $advertisement->image_url ? Storage::url($advertisement->image_url) : '' }}'" class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">ตัวอย่างใหม่:</p>
                    <img :src="previewImage" class="max-w-xs rounded-lg shadow-md">
                </div>
            </div>

            <div x-show="adType === 'video'" class="space-y-4">
                @if($advertisement->video_url)
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">วิดีโอปัจจุบัน:</p>
                        <video controls class="max-w-xs rounded-lg shadow-md">
                            <source src="{{ Storage::url($advertisement->video_url) }}" type="video/mp4">
                        </video>
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อัปโหลดวิดีโอใหม่</label>
                    <input type="file" name="video" accept="video/*"
                           @change="previewVideo = URL.createObjectURL($event.target.files[0])"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            <div x-show="adType === 'html'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">HTML Code</label>
                    <textarea name="html_content" rows="10"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono text-sm">{{ old('html_content', $advertisement->html_content) }}</textarea>
                </div>
            </div>

            <div x-show="adType === 'promotion'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โค้ดโปรโมชั่น</label>
                        <input type="text" name="promotion_code" value="{{ old('promotion_code', $advertisement->promotion_code) }}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ส่วนลด (บาท)</label>
                        <input type="number" name="discount_amount" value="{{ old('discount_amount', $advertisement->discount_amount) }}" step="0.01"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ส่วนลด (%)</label>
                        <input type="number" name="discount_percentage" value="{{ old('discount_percentage', $advertisement->discount_percentage) }}" step="0.01" max="100"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Display Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-cog text-green-600 mr-2"></i>
                การตั้งค่าการแสดงผล
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระยะเวลา (วินาที) <span class="text-red-500">*</span></label>
                    <input type="number" name="duration_seconds" value="{{ old('duration_seconds', $advertisement->duration_seconds) }}" min="1" max="300" required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', $advertisement->order) }}" min="0"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URL ลิงก์</label>
                    <input type="url" name="link_url" value="{{ old('link_url', $advertisement->link_url) }}"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $advertisement->is_active) ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <span class="ml-3 text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="show_on_idle_only" value="1" {{ old('show_on_idle_only', $advertisement->show_on_idle_only) ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <span class="ml-3 text-gray-700 dark:text-gray-300">แสดงเฉพาะเมื่อ POS ไม่ได้ใช้งาน</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="link_opens_new_tab" value="1" {{ old('link_opens_new_tab', $advertisement->link_opens_new_tab) ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <span class="ml-3 text-gray-700 dark:text-gray-300">เปิดลิงก์ในแท็บใหม่</span>
                </label>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.pos.advertisements.show', $advertisement) }}"
                   class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    <i class="fas fa-times mr-2"></i>ยกเลิก
                </a>
                <button type="submit"
                        class="bg-gradient-to-r from-yellow-500 to-orange-600 text-white px-8 py-3 rounded-lg font-semibold hover:from-yellow-600 hover:to-orange-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i>บันทึกการแก้ไข
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

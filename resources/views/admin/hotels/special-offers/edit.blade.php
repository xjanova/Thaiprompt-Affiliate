@extends('layouts.admin-v3')

@section('title', 'แก้ไขโปรโมชั่นพิเศษ - ' . $offer->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-edit text-blue-600 dark:text-blue-400 mr-2"></i>
                แก้ไขโปรโมชั่นพิเศษ
            </h1>
            <a href="{{ route('admin.hotels.special-offers.show', $offer->id) }}"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>

        <!-- Breadcrumb -->
        <nav class="flex text-sm text-gray-500 dark:text-gray-400 space-x-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a>
            <span>/</span>
            <a href="{{ route('admin.hotels.special-offers.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">โปรโมชั่นพิเศษ</a>
            <span>/</span>
            <a href="{{ route('admin.hotels.special-offers.show', $offer->id) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $offer->name }}</a>
            <span>/</span>
            <span class="text-gray-900 dark:text-white font-medium">แก้ไข</span>
        </nav>
    </div>

    <form action="{{ route('admin.hotels.special-offers.update', $offer->id) }}" method="POST" enctype="multipart/form-data" x-data="offerForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content (Left 2 columns) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Tab Navigation -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <div class="flex overflow-x-auto">
                            <button type="button" @click="activeTab = 'basic'"
                                    :class="activeTab === 'basic' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition">
                                <i class="fas fa-info-circle mr-2"></i>ข้อมูลพื้นฐาน
                            </button>
                            <button type="button" @click="activeTab = 'discount'"
                                    :class="activeTab === 'discount' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition">
                                <i class="fas fa-percent mr-2"></i>การตั้งค่าส่วนลด
                            </button>
                            <button type="button" @click="activeTab = 'validity'"
                                    :class="activeTab === 'validity' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition">
                                <i class="fas fa-calendar mr-2"></i>ระยะเวลาใช้งาน
                            </button>
                            <button type="button" @click="activeTab = 'limits'"
                                    :class="activeTab === 'limits' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition">
                                <i class="fas fa-chart-line mr-2"></i>ข้อจำกัด
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Basic Information Tab -->
                        <div x-show="activeTab === 'basic'" x-transition>
                            <div class="space-y-6">
                                <div>
                                    <label for="hotel_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        โรงแรม <span class="text-red-500">*</span>
                                    </label>
                                    <select id="hotel_id" name="hotel_id" required @change="loadRoomTypes($event.target.value)"
                                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        <option value="">เลือกโรงแรม</option>
                                        @foreach($hotels as $hotel)
                                            <option value="{{ $hotel->id }}" {{ old('hotel_id', $offer->hotel_id) == $hotel->id ? 'selected' : '' }}>
                                                {{ $hotel->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hotel_id')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            ชื่อโปรโมชั่น <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="name" name="name" value="{{ old('name', $offer->name) }}" required
                                               placeholder="เช่น ลดพิเศษวันหยุดยาว"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        @error('name')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            โค้ดส่วนลด <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="code" name="code" value="{{ old('code', $offer->code) }}" required
                                               placeholder="เช่น SUMMER2024"
                                               @input="$event.target.value = $event.target.value.toUpperCase().replace(/\s/g, '')"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        @error('code')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตัวพิมพ์ใหญ่, ไม่มีช่องว่าง</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        คำอธิบาย
                                    </label>
                                    <textarea id="description" name="description" rows="3"
                                              placeholder="อธิบายรายละเอียดโปรโมชั่น..."
                                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">{{ old('description', $offer->description) }}</textarea>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Discount Settings Tab -->
                        <div x-show="activeTab === 'discount'" x-transition x-cloak>
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="discount_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            ประเภทส่วนลด <span class="text-red-500">*</span>
                                        </label>
                                        <select id="discount_type" name="discount_type" required x-model="discountType"
                                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                            <option value="percentage" {{ old('discount_type', $offer->discount_type) == 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์ (%)</option>
                                            <option value="fixed" {{ old('discount_type', $offer->discount_type) == 'fixed' ? 'selected' : '' }}>จำนวนเงินคงที่ (฿)</option>
                                            <option value="free_nights" {{ old('discount_type', $offer->discount_type) == 'free_nights' ? 'selected' : '' }}>ฟรีจำนวนคืน</option>
                                        </select>
                                        @error('discount_type')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div x-show="discountType !== 'free_nights'">
                                        <label for="discount_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            มูลค่าส่วนลด <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="discount_value" name="discount_value" value="{{ old('discount_value', $offer->discount_value) }}"
                                               step="0.01" min="0" placeholder="เช่น 20"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        @error('discount_value')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div x-show="discountType === 'free_nights'" x-cloak>
                                        <label for="free_nights" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            จำนวนคืนฟรี <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="free_nights" name="free_nights" value="{{ old('free_nights', $offer->free_nights) }}"
                                               min="1" placeholder="เช่น 1"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        @error('free_nights')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="min_nights" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            จำนวนคืนขั้นต่ำ
                                        </label>
                                        <input type="number" id="min_nights" name="min_nights" value="{{ old('min_nights', $offer->min_nights) }}"
                                               min="1" placeholder="เช่น 2"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        @error('min_nights')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวนคืนขั้นต่ำที่ต้องจองเพื่อใช้โปรโมชั่น</p>
                                    </div>

                                    <div>
                                        <label for="min_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            ยอดขั้นต่ำ (฿)
                                        </label>
                                        <input type="number" id="min_amount" name="min_amount" value="{{ old('min_amount', $offer->min_amount) }}"
                                               step="0.01" min="0" placeholder="เช่น 5000"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        @error('min_amount')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ยอดการจองขั้นต่ำเพื่อใช้โปรโมชั่น</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Validity Period Tab -->
                        <div x-show="activeTab === 'validity'" x-transition x-cloak>
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="valid_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            วันที่เริ่มต้น <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" id="valid_from" name="valid_from" value="{{ old('valid_from', \Carbon\Carbon::parse($offer->valid_from)->format('Y-m-d')) }}" required
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        @error('valid_from')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="valid_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            วันที่สิ้นสุด <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" id="valid_to" name="valid_to" value="{{ old('valid_to', \Carbon\Carbon::parse($offer->valid_to)->format('Y-m-d')) }}" required
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                        @error('valid_to')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        วันที่ใช้ได้
                                    </label>
                                    @php
                                        $days = ['monday' => 'จันทร์', 'tuesday' => 'อังคาร', 'wednesday' => 'พุธ',
                                                 'thursday' => 'พฤหัสบดี', 'friday' => 'ศุกร์', 'saturday' => 'เสาร์', 'sunday' => 'อาทิตย์'];
                                        $selectedDays = old('applicable_days', $offer->applicable_days ?? []);
                                    @endphp
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        @foreach($days as $key => $label)
                                            <label class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                <input type="checkbox" name="applicable_days[]" value="{{ $key }}"
                                                       {{ in_array($key, $selectedDays) ? 'checked' : '' }}
                                                       class="w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-400">
                                                <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">หากไม่เลือก จะใช้ได้ทุกวัน</p>
                                </div>
                            </div>
                        </div>

                        <!-- Usage Limits Tab -->
                        <div x-show="activeTab === 'limits'" x-transition x-cloak>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="max_uses" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        จำนวนครั้งที่ใช้ได้ทั้งหมด
                                    </label>
                                    <input type="number" id="max_uses" name="max_uses" value="{{ old('max_uses', $offer->max_uses) }}"
                                           min="1" placeholder="ไม่จำกัด"
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                    @error('max_uses')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างหากไม่จำกัด</p>
                                </div>

                                <div>
                                    <label for="max_uses_per_user" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        จำนวนครั้งที่ใช้ได้ต่อผู้ใช้
                                    </label>
                                    <input type="number" id="max_uses_per_user" name="max_uses_per_user" value="{{ old('max_uses_per_user', $offer->max_uses_per_user) }}"
                                           min="1" placeholder="ไม่จำกัด"
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition">
                                    @error('max_uses_per_user')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างหากไม่จำกัด</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Right 1 column) -->
            <div class="space-y-6">
                <!-- Status Card -->
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-toggle-on mr-2"></i>สถานะ
                    </h3>

                    <div class="space-y-4">
                        <label class="flex items-center justify-between cursor-pointer">
                            <div>
                                <div class="font-medium">เปิดใช้งาน</div>
                            </div>
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $offer->is_active) ? 'checked' : '' }}
                                   class="sr-only peer"
                                   id="is_active">
                            <div class="relative w-11 h-6 bg-white/20 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/40"></div>
                        </label>

                        <label class="flex items-center justify-between cursor-pointer">
                            <div>
                                <div class="font-medium">แนะนำพิเศษ</div>
                            </div>
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $offer->is_featured) ? 'checked' : '' }}
                                   class="sr-only peer"
                                   id="is_featured">
                            <div class="relative w-11 h-6 bg-white/20 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/40"></div>
                        </label>
                    </div>
                </div>

                <!-- Banner Image Card -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-image mr-2"></i>รูปแบนเนอร์
                    </h3>

                    @if($offer->banner_image)
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">รูปปัจจุบัน:</label>
                            <img src="{{ Storage::url($offer->banner_image) }}" alt="Current banner" class="w-full rounded-lg shadow-md">
                        </div>
                    @endif

                    <label class="block text-sm font-medium mb-2">เปลี่ยนรูปแบนเนอร์</label>
                    <input type="file" name="banner_image" accept="image/*"
                           @change="previewBanner($event)"
                           class="w-full px-4 py-2 rounded-lg bg-white/20 border border-white/30 text-white placeholder-white/70 focus:bg-white/30 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-white/30 file:text-white file:cursor-pointer hover:file:bg-white/40">
                    <p class="mt-2 text-xs text-purple-100">รองรับไฟล์ JPG, PNG (สูงสุด 5MB)</p>

                    <div x-show="bannerPreview" x-cloak class="mt-3">
                        <img :src="bannerPreview" class="w-full rounded-lg shadow-md">
                    </div>

                    @error('banner_image')
                        <p class="mt-2 text-sm text-red-200">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Room Types Card -->
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-bed mr-2"></i>ห้องพักที่ใช้ได้
                    </h3>

                    <div x-html="roomTypesHtml" class="space-y-2"></div>

                    <p class="mt-3 text-xs text-indigo-100">หากไม่เลือก จะใช้ได้กับทุกห้อง</p>
                </div>

                <!-- Actions Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <button type="submit"
                            class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium transition shadow-lg hover:shadow-xl mb-3">
                        <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                    <a href="{{ route('admin.hotels.special-offers.show', $offer->id) }}"
                       class="block w-full px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white text-center rounded-lg font-medium transition">
                        <i class="fas fa-times mr-2"></i>ยกเลิก
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function offerForm() {
    return {
        activeTab: 'basic',
        discountType: '{{ old("discount_type", $offer->discount_type) }}',
        bannerPreview: null,
        roomTypesHtml: @json($roomTypes->count() > 0 ?
            collect($roomTypes)->map(function($room) use ($offer) {
                $selectedRoomTypes = old('room_type_ids', $offer->room_type_ids ?? []);
                $checked = in_array($room->id, $selectedRoomTypes) ? 'checked' : '';
                return '<label class="flex items-center space-x-3 p-3 bg-white/20 rounded-lg cursor-pointer hover:bg-white/30 transition">
                    <input type="checkbox" name="room_type_ids[]" value="' . $room->id . '" ' . $checked . '
                           class="w-5 h-5 text-blue-600 border-white/30 rounded focus:ring-blue-500">
                    <span class="text-sm">' . $room->name . '</span>
                </label>';
            })->join('') : '<p class="text-sm text-indigo-100">ไม่มีห้องพัก</p>'),

        previewBanner(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.bannerPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        loadRoomTypes(hotelId) {
            if (!hotelId) {
                this.roomTypesHtml = '<p class="text-sm text-indigo-100">กรุณาเลือกโรงแรมก่อน</p>';
                return;
            }

            this.roomTypesHtml = '<p class="text-sm text-indigo-100">กำลังโหลด...</p>';

            fetch(`/admin/hotels/${hotelId}/room-types`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        this.roomTypesHtml = '<p class="text-sm text-indigo-100">ไม่มีห้องพัก</p>';
                        return;
                    }

                    let html = '';
                    data.forEach(room => {
                        html += `
                            <label class="flex items-center space-x-3 p-3 bg-white/20 rounded-lg cursor-pointer hover:bg-white/30 transition">
                                <input type="checkbox" name="room_type_ids[]" value="${room.id}"
                                       class="w-5 h-5 text-blue-600 border-white/30 rounded focus:ring-blue-500">
                                <span class="text-sm">${room.name}</span>
                            </label>
                        `;
                    });
                    this.roomTypesHtml = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.roomTypesHtml = '<p class="text-sm text-red-200">เกิดข้อผิดพลาด</p>';
                });
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection

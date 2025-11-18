@extends('layouts.admin-v3')

@section('title', 'แก้ไขห้องพัก - ' . $roomType->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-edit text-blue-600 dark:text-blue-400 mr-2"></i>
                แก้ไขห้องพัก: {{ $roomType->name }}
            </h1>
            <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>

        <!-- Breadcrumb -->
        <nav class="flex text-sm text-gray-500 dark:text-gray-400 space-x-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a>
            <span>/</span>
            <a href="{{ route('admin.hotels.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">โรงแรม</a>
            <span>/</span>
            <a href="{{ route('admin.hotels.show', $hotel->id) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $hotel->name }}</a>
            <span>/</span>
            <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}" class="hover:text-blue-600 dark:hover:text-blue-400">ห้องพัก</a>
            <span>/</span>
            <span class="text-gray-900 dark:text-white font-medium">แก้ไข</span>
        </nav>
    </div>

    <form action="{{ route('admin.hotels.rooms.update', [$hotel->id, $roomType->id]) }}" method="POST" enctype="multipart/form-data" x-data="roomForm()">
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
                            <button type="button" @click="activeTab = 'pricing'"
                                    :class="activeTab === 'pricing' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition">
                                <i class="fas fa-dollar-sign mr-2"></i>ราคา
                            </button>
                            <button type="button" @click="activeTab = 'capacity'"
                                    :class="activeTab === 'capacity' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition">
                                <i class="fas fa-users mr-2"></i>จำนวนผู้เข้าพัก
                            </button>
                            <button type="button" @click="activeTab = 'amenities'"
                                    :class="activeTab === 'amenities' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition">
                                <i class="fas fa-concierge-bell mr-2"></i>สิ่งอำนวยความสะดวก
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Basic Information Tab -->
                        <div x-show="activeTab === 'basic'" x-transition>
                            <div class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        ชื่อห้องพัก <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="name" name="name" value="{{ old('name', $roomType->name) }}" required
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('name') border-red-500 @enderror">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        รายละเอียดห้องพัก <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="description" name="description" rows="6" required
                                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('description') border-red-500 @enderror">{{ old('description', $roomType->description) }}</textarea>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="size_sqm" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            ขนาดห้อง (ตร.ม.) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="size_sqm" name="size_sqm" value="{{ old('size_sqm', $roomType->size_sqm) }}" step="0.01" required
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('size_sqm') border-red-500 @enderror">
                                        @error('size_sqm')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="bed_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            ประเภทเตียง
                                        </label>
                                        <select id="bed_type" name="bed_type"
                                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('bed_type') border-red-500 @enderror">
                                            <option value="">-- เลือกประเภทเตียง --</option>
                                            <option value="single" {{ old('bed_type', $roomType->bed_type) == 'single' ? 'selected' : '' }}>Single Bed (เตียงเดี่ยว)</option>
                                            <option value="twin" {{ old('bed_type', $roomType->bed_type) == 'twin' ? 'selected' : '' }}>Twin Beds (เตียงคู่แยก)</option>
                                            <option value="double" {{ old('bed_type', $roomType->bed_type) == 'double' ? 'selected' : '' }}>Double Bed (เตียงคู่)</option>
                                            <option value="queen" {{ old('bed_type', $roomType->bed_type) == 'queen' ? 'selected' : '' }}>Queen Bed (เตียงควีน)</option>
                                            <option value="king" {{ old('bed_type', $roomType->bed_type) == 'king' ? 'selected' : '' }}>King Bed (เตียงคิง)</option>
                                        </select>
                                        @error('bed_type')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="view_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            วิวจากห้อง
                                        </label>
                                        <select id="view_type" name="view_type"
                                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('view_type') border-red-500 @enderror">
                                            <option value="">-- เลือกวิว --</option>
                                            <option value="city" {{ old('view_type', $roomType->view_type) == 'city' ? 'selected' : '' }}>City View (วิวเมือง)</option>
                                            <option value="sea" {{ old('view_type', $roomType->view_type) == 'sea' ? 'selected' : '' }}>Sea View (วิวทะเล)</option>
                                            <option value="mountain" {{ old('view_type', $roomType->view_type) == 'mountain' ? 'selected' : '' }}>Mountain View (วิวภูเขา)</option>
                                            <option value="garden" {{ old('view_type', $roomType->view_type) == 'garden' ? 'selected' : '' }}>Garden View (วิวสวน)</option>
                                            <option value="pool" {{ old('view_type', $roomType->view_type) == 'pool' ? 'selected' : '' }}>Pool View (วิวสระน้ำ)</option>
                                        </select>
                                        @error('view_type')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Tab -->
                        <div x-show="activeTab === 'pricing'" x-transition x-cloak>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="base_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        ราคาพื้นฐาน (บาท/คืน) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="base_price" name="base_price" value="{{ old('base_price', $roomType->base_price) }}" step="0.01" required
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('base_price') border-red-500 @enderror">
                                    @error('base_price')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="weekend_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        ราคาวันหยุด (บาท/คืน)
                                    </label>
                                    <input type="number" id="weekend_price" name="weekend_price" value="{{ old('weekend_price', $roomType->weekend_price) }}" step="0.01"
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('weekend_price') border-red-500 @enderror">
                                    @error('weekend_price')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Capacity Tab -->
                        <div x-show="activeTab === 'capacity'" x-transition x-cloak>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="max_adults" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        ผู้ใหญ่สูงสุด <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="max_adults" name="max_adults" value="{{ old('max_adults', $roomType->max_adults) }}" min="1" required
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('max_adults') border-red-500 @enderror">
                                    @error('max_adults')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="max_children" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        เด็กสูงสุด
                                    </label>
                                    <input type="number" id="max_children" name="max_children" value="{{ old('max_children', $roomType->max_children) }}" min="0"
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('max_children') border-red-500 @enderror">
                                    @error('max_children')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="total_rooms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        จำนวนห้องทั้งหมด <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="total_rooms" name="total_rooms" value="{{ old('total_rooms', $roomType->total_rooms) }}" min="1" required
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('total_rooms') border-red-500 @enderror">
                                    @error('total_rooms')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Amenities Tab -->
                        <div x-show="activeTab === 'amenities'" x-transition x-cloak>
                            @php
                                $currentAmenities = $roomType->amenities ?? [];
                                $amenitiesList = [
                                    ['id' => 'wifi', 'label' => 'Free WiFi', 'icon' => 'wifi'],
                                    ['id' => 'ac', 'label' => 'Air Conditioning', 'icon' => 'snowflake'],
                                    ['id' => 'tv', 'label' => 'TV', 'icon' => 'tv'],
                                    ['id' => 'minibar', 'label' => 'Minibar', 'icon' => 'glass-martini'],
                                    ['id' => 'safe', 'label' => 'Safe', 'icon' => 'lock'],
                                    ['id' => 'balcony', 'label' => 'Balcony', 'icon' => 'door-open'],
                                    ['id' => 'bathtub', 'label' => 'Bathtub', 'icon' => 'bath'],
                                    ['id' => 'desk', 'label' => 'Desk', 'icon' => 'desk'],
                                    ['id' => 'coffee', 'label' => 'Coffee Maker', 'icon' => 'coffee'],
                                    ['id' => 'hairdryer', 'label' => 'Hairdryer', 'icon' => 'wind'],
                                    ['id' => 'shower', 'label' => 'Shower', 'icon' => 'shower'],
                                    ['id' => 'phone', 'label' => 'Telephone', 'icon' => 'phone'],
                                ];
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($amenitiesList as $amenity)
                                    <label class="flex items-center space-x-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                        <input type="checkbox" name="amenities[]" value="{{ $amenity['label'] }}"
                                               {{ in_array($amenity['label'], $currentAmenities) ? 'checked' : '' }}
                                               class="w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-400">
                                        <span class="flex items-center text-gray-900 dark:text-white">
                                            <i class="fas fa-{{ $amenity['icon'] }} mr-2 text-blue-500"></i>{{ $amenity['label'] }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Right 1 column) -->
            <div class="space-y-6">
                <!-- Current Images Card -->
                @if($roomType->main_image || ($roomType->gallery_images && count($roomType->gallery_images) > 0))
                    <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-lg p-6 text-white">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <i class="fas fa-images mr-2"></i>รูปภาพปัจจุบัน
                        </h3>

                        @if($roomType->main_image)
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">รูปหลัก:</label>
                                <img src="{{ $roomType->main_image_url }}" alt="{{ $roomType->name }}" class="w-full rounded-lg shadow-md">
                            </div>
                        @endif

                        @if($roomType->gallery_images && count($roomType->gallery_images) > 0)
                            <div>
                                <label class="block text-sm font-medium mb-2">แกลเลอรี่:</label>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($roomType->gallery_images as $image)
                                        <img src="{{ Storage::url($image) }}" alt="Gallery" class="w-full h-24 object-cover rounded-lg shadow-md">
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Upload New Images Card -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-upload mr-2"></i>อัปโหลดรูปใหม่
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">รูปหลักใหม่</label>
                            <input type="file" id="main_image" name="main_image" accept="image/*"
                                   @change="previewMainImage($event)"
                                   class="w-full px-4 py-2 rounded-lg bg-white/20 border border-white/30 text-white placeholder-white/70 focus:bg-white/30 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-white/30 file:text-white file:cursor-pointer hover:file:bg-white/40">
                            @error('main_image')
                                <p class="mt-1 text-sm text-red-200">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-purple-100">อัปโหลดเฉพาะเมื่อต้องการเปลี่ยนรูปหลัก</p>
                            <div id="main_image_preview" class="mt-3" x-show="mainImagePreview" x-cloak>
                                <img :src="mainImagePreview" class="w-full h-48 object-cover rounded-lg shadow-md">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">รูปแกลเลอรี่เพิ่มเติม</label>
                            <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple
                                   @change="previewGallery($event)"
                                   class="w-full px-4 py-2 rounded-lg bg-white/20 border border-white/30 text-white placeholder-white/70 focus:bg-white/30 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-white/30 file:text-white file:cursor-pointer hover:file:bg-white/40">
                            @error('gallery_images')
                                <p class="mt-1 text-sm text-red-200">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-purple-100">รูปใหม่จะถูกเพิ่มเข้าไปในแกลเลอรี่</p>
                            <div id="gallery_preview" class="mt-3 grid grid-cols-3 gap-2" x-show="galleryPreviews.length > 0" x-cloak>
                                <template x-for="(preview, index) in galleryPreviews" :key="index">
                                    <img :src="preview" class="w-full h-20 object-cover rounded-lg shadow-md">
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Card -->
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-cog mr-2"></i>การตั้งค่า
                    </h3>

                    <div class="space-y-4">
                        <label class="flex items-center justify-between cursor-pointer">
                            <div>
                                <div class="font-medium">เปิดใช้งาน</div>
                                <div class="text-sm text-indigo-100">ห้องพักนี้จะแสดงให้ลูกค้าเห็นและสามารถจองได้</div>
                            </div>
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $roomType->is_active) ? 'checked' : '' }}
                                   class="sr-only peer"
                                   id="is_active">
                            <div class="relative w-11 h-6 bg-white/20 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/40"></div>
                        </label>

                        <label class="flex items-center justify-between cursor-pointer">
                            <div>
                                <div class="font-medium">แนะนำพิเศษ</div>
                                <div class="text-sm text-indigo-100">แสดงห้องนี้ในส่วนแนะนำ</div>
                            </div>
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $roomType->is_featured) ? 'checked' : '' }}
                                   class="sr-only peer"
                                   id="is_featured">
                            <div class="relative w-11 h-6 bg-white/20 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/40"></div>
                        </label>
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <button type="submit"
                            class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium transition shadow-lg hover:shadow-xl mb-3">
                        <i class="fas fa-save mr-2"></i>บันทึกการแก้ไข
                    </button>
                    <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}"
                       class="block w-full px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white text-center rounded-lg font-medium transition mb-3">
                        <i class="fas fa-times mr-2"></i>ยกเลิก
                    </a>
                    <button type="button" @click="deleteRoom"
                            class="w-full px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-lg font-medium transition shadow-lg hover:shadow-xl">
                        <i class="fas fa-trash mr-2"></i>ลบห้องพักนี้
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Hidden Delete Form -->
<form id="deleteForm" action="{{ route('admin.hotels.rooms.destroy', [$hotel->id, $roomType->id]) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function roomForm() {
    return {
        activeTab: 'basic',
        mainImagePreview: null,
        galleryPreviews: [],

        previewMainImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.mainImagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        previewGallery(event) {
            const files = Array.from(event.target.files);
            this.galleryPreviews = [];

            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.galleryPreviews.push(e.target.result);
                };
                reader.readAsDataURL(file);
            });
        },

        deleteRoom() {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบห้องพักนี้? การกระทำนี้ไม่สามารถยกเลิกได้')) {
                document.getElementById('deleteForm').submit();
            }
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection

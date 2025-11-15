@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-hotel text-blue-600 dark:text-blue-400"></i>
                เพิ่มโรงแรมใหม่
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">กรอกข้อมูลโรงแรมทั้งหมดเพื่อเพิ่มเข้าสู่ระบบ</p>
        </div>
        <a href="{{ route('admin.hotels.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับสู่รายการ</span>
        </a>
    </div>

    <form action="{{ route('admin.hotels.store') }}" method="POST" enctype="multipart/form-data" id="hotelForm" x-data="hotelForm()">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Tabbed Interface --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" x-data="{ activeTab: 'basic' }">
                    {{-- Tabs Header --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <div class="flex overflow-x-auto">
                            <button type="button" @click="activeTab = 'basic'"
                                    :class="activeTab === 'basic' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                <i class="fas fa-info-circle"></i>
                                <span>ข้อมูลพื้นฐาน</span>
                            </button>
                            <button type="button" @click="activeTab = 'location'"
                                    :class="activeTab === 'location' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>ที่ตั้ง</span>
                            </button>
                            <button type="button" @click="activeTab = 'contact'"
                                    :class="activeTab === 'contact' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                <i class="fas fa-phone"></i>
                                <span>ข้อมูลติดต่อ</span>
                            </button>
                            <button type="button" @click="activeTab = 'policies'"
                                    :class="activeTab === 'policies' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                <i class="fas fa-file-contract"></i>
                                <span>นโยบาย</span>
                            </button>
                            <button type="button" @click="activeTab = 'seo'"
                                    :class="activeTab === 'seo' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                <i class="fas fa-search"></i>
                                <span>SEO</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        {{-- Basic Information Tab --}}
                        <div x-show="activeTab === 'basic'" x-transition>
                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                                ข้อมูลพื้นฐานของโรงแรม
                            </h5>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-building text-blue-600 dark:text-blue-400 mr-1"></i>
                                        ชื่อโรงแรม <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name"
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all @error('name') border-red-500 @enderror"
                                           value="{{ old('name') }}"
                                           placeholder="เช่น Grand Palace Hotel Bangkok" required>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ชื่อที่จะแสดงบนเว็บไซต์และผลการค้นหา</p>
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-link text-indigo-600 dark:text-indigo-400 mr-1"></i>
                                        Slug (URL)
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="flex-shrink-0 flex items-center px-4 bg-gray-100 dark:bg-gray-700 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg">
                                            <i class="fas fa-globe text-gray-500 dark:text-gray-400"></i>
                                        </div>
                                        <input type="text" name="slug"
                                               class="flex-1 px-4 py-3 rounded-r-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('slug') }}"
                                               placeholder="auto-generate จากชื่อโรงแรม">
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ปล่อยว่างเพื่อสร้างอัตโนมัติจากชื่อโรงแรม</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-align-left text-green-600 dark:text-green-400 mr-1"></i>
                                        คำอธิบายแบบย่อ
                                    </label>
                                    <textarea name="short_description" rows="2"
                                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all resize-none"
                                              placeholder="สรุปสั้นๆ เกี่ยวกับโรงแรม (1-2 ประโยค)">{{ old('short_description') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">แสดงในรายการค้นหาและการ์ดแสดงโรงแรม</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-align-justify text-green-600 dark:text-green-400 mr-1"></i>
                                        คำอธิบายแบบเต็ม
                                    </label>
                                    <textarea name="description" rows="6"
                                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all resize-none"
                                              placeholder="รายละเอียดเกี่ยวกับโรงแรม สิ่งอำนวยความสะดวก และบริการต่างๆ">{{ old('description') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">รายละเอียดแบบเต็มที่แสดงในหน้าข้อมูลโรงแรม</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-home text-amber-600 dark:text-amber-400 mr-1"></i>
                                            ประเภท <span class="text-red-500">*</span>
                                        </label>
                                        <select name="type"
                                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                                required>
                                            <option value="hotel">🏨 โรงแรม</option>
                                            <option value="resort">🏖️ รีสอร์ท</option>
                                            <option value="hostel">🛏️ โฮสเทล</option>
                                            <option value="apartment">🏢 อพาร์ทเมนท์</option>
                                            <option value="villa">🏡 วิลล่า</option>
                                            <option value="guesthouse">🏠 เกสต์เฮาส์</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-star text-amber-500 dark:text-amber-400 mr-1"></i>
                                            ระดับดาว
                                        </label>
                                        <select name="star_rating"
                                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all">
                                            <option value="">ไม่ระบุ</option>
                                            <option value="1">⭐ 1 ดาว</option>
                                            <option value="2">⭐⭐ 2 ดาว</option>
                                            <option value="3">⭐⭐⭐ 3 ดาว</option>
                                            <option value="4">⭐⭐⭐⭐ 4 ดาว</option>
                                            <option value="5">⭐⭐⭐⭐⭐ 5 ดาว</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Location Tab --}}
                        <div x-show="activeTab === 'location'" x-transition x-cloak>
                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                                <i class="fas fa-map-marker-alt text-red-600 dark:text-red-400"></i>
                                ที่ตั้งและแผนที่
                            </h5>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-map-signs text-red-600 dark:text-red-400 mr-1"></i>
                                        ที่อยู่ <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="address"
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                           value="{{ old('address') }}"
                                           placeholder="เช่น 123 ถนนสุขุมวิท" required>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ที่อยู่ตามถนน เลขที่อาคาร</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-city text-blue-600 dark:text-blue-400 mr-1"></i>
                                            เมือง/อำเภอ <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="city"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('city') }}"
                                               placeholder="เช่น กรุงเทพมหานคร" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-map text-blue-600 dark:text-blue-400 mr-1"></i>
                                            จังหวัด/รัฐ
                                        </label>
                                        <input type="text" name="state"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('state') }}"
                                               placeholder="เช่น กรุงเทพมหานคร">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-globe-asia text-green-600 dark:text-green-400 mr-1"></i>
                                            ประเทศ <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="country"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('country', 'Thailand') }}"
                                               placeholder="Thailand" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-mail-bulk text-gray-600 dark:text-gray-400 mr-1"></i>
                                            รหัสไปรษณีย์
                                        </label>
                                        <input type="text" name="postal_code"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('postal_code') }}"
                                               placeholder="เช่น 10110">
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                                    <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2 mb-4">
                                        <i class="fas fa-map-pin text-amber-600 dark:text-amber-400"></i>
                                        พิกัดแผนที่ (ไม่บังคับ)
                                    </h6>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-compass text-amber-600 dark:text-amber-400 mr-1"></i>
                                                Latitude (ละติจูด)
                                            </label>
                                            <input type="number" step="0.000001" name="latitude"
                                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                                   value="{{ old('latitude') }}"
                                                   placeholder="13.7563">
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">สำหรับแสดงตำแหน่งบนแผนที่</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-compass text-amber-600 dark:text-amber-400 mr-1"></i>
                                                Longitude (ลองจิจูด)
                                            </label>
                                            <input type="number" step="0.000001" name="longitude"
                                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                                   value="{{ old('longitude') }}"
                                                   placeholder="100.5018">
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">สำหรับแสดงตำแหน่งบนแผนที่</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Contact Tab --}}
                        <div x-show="activeTab === 'contact'" x-transition x-cloak>
                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                                <i class="fas fa-phone text-green-600 dark:text-green-400"></i>
                                ข้อมูลติดต่อ
                            </h5>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-phone-alt text-green-600 dark:text-green-400 mr-1"></i>
                                        เบอร์โทรศัพท์
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="flex-shrink-0 flex items-center px-4 bg-gray-100 dark:bg-gray-700 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg">
                                            <i class="fas fa-phone text-gray-500 dark:text-gray-400"></i>
                                        </div>
                                        <input type="text" name="phone"
                                               class="flex-1 px-4 py-3 rounded-r-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('phone') }}"
                                               placeholder="02-123-4567">
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เบอร์โทรศัพท์ติดต่อโรงแรม</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-envelope text-red-600 dark:text-red-400 mr-1"></i>
                                        อีเมล
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="flex-shrink-0 flex items-center px-4 bg-gray-100 dark:bg-gray-700 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg">
                                            <i class="fas fa-at text-gray-500 dark:text-gray-400"></i>
                                        </div>
                                        <input type="email" name="email"
                                               class="flex-1 px-4 py-3 rounded-r-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('email') }}"
                                               placeholder="info@hotel.com">
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">อีเมลสำหรับติดต่อ</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-globe text-blue-600 dark:text-blue-400 mr-1"></i>
                                        เว็บไซต์
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="flex-shrink-0 flex items-center px-4 bg-gray-100 dark:bg-gray-700 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg">
                                            <i class="fas fa-link text-gray-500 dark:text-gray-400"></i>
                                        </div>
                                        <input type="url" name="website"
                                               class="flex-1 px-4 py-3 rounded-r-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('website') }}"
                                               placeholder="https://hotel.com">
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เว็บไซต์ของโรงแรม</p>
                                </div>
                            </div>
                        </div>

                        {{-- Policies Tab --}}
                        <div x-show="activeTab === 'policies'" x-transition x-cloak>
                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                                <i class="fas fa-file-contract text-purple-600 dark:text-purple-400"></i>
                                นโยบายและกฎระเบียบ
                            </h5>

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-clock text-green-600 dark:text-green-400 mr-1"></i>
                                            เวลาเช็คอิน
                                        </label>
                                        <input type="time" name="check_in_time"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('check_in_time', '14:00') }}">
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เวลาที่ผู้เข้าพักสามารถเช็คอินได้</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-clock text-amber-600 dark:text-amber-400 mr-1"></i>
                                            เวลาเช็คเอาท์
                                        </label>
                                        <input type="time" name="check_out_time"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                               value="{{ old('check_out_time', '12:00') }}">
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เวลาที่ต้องเช็คเอาท์</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-ban text-red-600 dark:text-red-400 mr-1"></i>
                                        นโยบายการยกเลิก
                                    </label>
                                    <textarea name="cancellation_policy" rows="3"
                                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all resize-none"
                                              placeholder="เช่น ยกเลิกฟรีก่อน 24 ชั่วโมง, หลังจากนั้นคิด 1 คืน">{{ old('cancellation_policy') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เงื่อนไขการยกเลิกการจอง</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-clipboard-list text-blue-600 dark:text-blue-400 mr-1"></i>
                                        กฎระเบียบของที่พัก
                                    </label>
                                    <textarea name="house_rules" rows="3"
                                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all resize-none"
                                              placeholder="เช่น ห้ามสูบบุหรี่, ห้ามนำสัตว์เลี้ยง, เงียบหลัง 22:00 น.">{{ old('house_rules') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">กฎและข้อบังคับที่ผู้เข้าพักต้องปฏิบัติตาม</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-credit-card text-green-600 dark:text-green-400 mr-1"></i>
                                        นโยบายการชำระเงิน
                                    </label>
                                    <textarea name="payment_policy" rows="3"
                                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all resize-none"
                                              placeholder="เช่น ชำระเต็มจำนวนเมื่อเช็คอิน, รับบัตรเครดิต, QR Code">{{ old('payment_policy') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">วิธีการและเงื่อนไขการชำระเงิน</p>
                                </div>
                            </div>
                        </div>

                        {{-- SEO Tab --}}
                        <div x-show="activeTab === 'seo'" x-transition x-cloak>
                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                                <i class="fas fa-search text-indigo-600 dark:text-indigo-400"></i>
                                การตั้งค่า SEO
                            </h5>

                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                                <div class="flex gap-3">
                                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                                    <div>
                                        <p class="font-medium text-blue-900 dark:text-blue-300">คำแนะนำ:</p>
                                        <p class="text-sm text-blue-800 dark:text-blue-400 mt-1">ข้อมูล SEO จะช่วยให้โรงแรมของคุณปรากฏในผลการค้นหาของ Google และ Search Engine อื่นๆ</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-heading text-blue-600 dark:text-blue-400 mr-1"></i>
                                        Meta Title
                                    </label>
                                    <input type="text" name="meta_title" x-model="metaTitle" maxlength="60"
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                           value="{{ old('meta_title') }}"
                                           placeholder="เช่น Grand Palace Hotel Bangkok - โรงแรมหรู 5 ดาว ใจกลางกรุงเทพฯ">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        <span x-text="metaTitle.length"></span>/60 ตัวอักษร - ชื่อที่แสดงในผลการค้นหา
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-align-left text-indigo-600 dark:text-indigo-400 mr-1"></i>
                                        Meta Description
                                    </label>
                                    <textarea name="meta_description" x-model="metaDescription" rows="3" maxlength="160"
                                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all resize-none"
                                              placeholder="คำอธิบายย่อเกี่ยวกับโรงแรม เพื่อดึงดูดผู้เข้าชมจากผลการค้นหา">{{ old('meta_description') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        <span x-text="metaDescription.length"></span>/160 ตัวอักษร - คำอธิบายที่แสดงในผลการค้นหา
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-tags text-green-600 dark:text-green-400 mr-1"></i>
                                        Meta Keywords
                                    </label>
                                    <input type="text" name="meta_keywords"
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                           value="{{ old('meta_keywords') }}"
                                           placeholder="เช่น โรงแรม, กรุงเทพ, ที่พัก, 5 ดาว (คั่นด้วยจุลภาค)">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">คำสำคัญที่เกี่ยวข้องกับโรงแรม คั่นด้วยจุลภาค (,)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Images Card --}}
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6">
                        <h5 class="text-lg font-semibold text-white flex items-center gap-2 mb-4">
                            <i class="fas fa-images"></i>
                            รูปภาพและสื่อ
                        </h5>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-white/90 mb-2">
                                    <i class="fas fa-image mr-1"></i>
                                    รูปหลัก <span class="px-2 py-0.5 bg-blue-500 text-xs rounded-full ml-1">แนะนำ</span>
                                </label>
                                <label class="flex items-center justify-center gap-2 px-4 py-3 bg-white/10 hover:bg-white/20 border-2 border-white/30 text-white rounded-lg cursor-pointer transition-all">
                                    <i class="fas fa-upload"></i>
                                    <span class="text-sm">เลือกไฟล์...</span>
                                    <input type="file" name="main_image" class="hidden" accept="image/*" x-ref="mainImage" @change="previewImage($event)">
                                </label>
                                <p class="mt-2 text-xs text-white/70">
                                    <i class="fas fa-info-circle mr-1"></i>ขนาดแนะนำ: 1200x800px (JPG, PNG)
                                </p>
                                <div x-show="imagePreview" class="mt-3">
                                    <img :src="imagePreview" class="w-full rounded-lg shadow-lg">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/90 mb-2">
                                    <i class="fas fa-photo-video mr-1"></i>
                                    แกลเลอรี่ <span class="px-2 py-0.5 bg-gray-600 text-xs rounded-full ml-1">หลายรูป</span>
                                </label>
                                <label class="flex items-center justify-center gap-2 px-4 py-3 bg-white/10 hover:bg-white/20 border-2 border-white/30 text-white rounded-lg cursor-pointer transition-all">
                                    <i class="fas fa-images"></i>
                                    <span class="text-sm">เลือกหลายไฟล์...</span>
                                    <input type="file" name="gallery_images[]" class="hidden" accept="image/*" multiple>
                                </label>
                                <p class="mt-2 text-xs text-white/70">
                                    <i class="fas fa-images mr-1"></i>เลือกหลายรูปพร้อมกัน (Ctrl+Click)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Facilities Card --}}
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6">
                        <h5 class="text-lg font-semibold text-white flex items-center gap-2 mb-4">
                            <i class="fas fa-concierge-bell"></i>
                            สิ่งอำนวยความสะดวก
                        </h5>

                        <div class="max-h-96 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                            @foreach($facilities as $facility)
                                <label class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-lg cursor-pointer transition-all group">
                                    <input type="checkbox" name="facilities[]" value="{{ $facility->id }}"
                                           class="w-5 h-5 rounded border-white/30 text-emerald-600 focus:ring-2 focus:ring-white/50">
                                    <span class="text-white text-sm font-medium group-hover:translate-x-1 transition-transform">
                                        {{ $facility->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Settings Card --}}
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6">
                        <h5 class="text-lg font-semibold text-white flex items-center gap-2 mb-4">
                            <i class="fas fa-cog"></i>
                            การตั้งค่า
                        </h5>

                        <div class="space-y-4">
                            <div class="bg-white/10 rounded-lg p-4 space-y-4">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" checked
                                           class="mt-1 w-5 h-5 rounded border-white/30 text-green-600 focus:ring-2 focus:ring-white/50">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 text-white font-medium">
                                            <i class="fas fa-toggle-on text-green-400"></i>
                                            เปิดใช้งาน
                                        </div>
                                        <p class="text-xs text-white/70 mt-1">เปิดให้ผู้ใช้สามารถจองได้</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_featured" value="1"
                                           class="mt-1 w-5 h-5 rounded border-white/30 text-amber-500 focus:ring-2 focus:ring-white/50">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 text-white font-medium">
                                            <i class="fas fa-star text-yellow-300"></i>
                                            โรงแรมแนะนำ
                                        </div>
                                        <p class="text-xs text-white/70 mt-1">แสดงในหน้าแรกและรายการแนะนำ</p>
                                    </div>
                                </label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/90 mb-2">
                                    <i class="fas fa-user-tie mr-1"></i>
                                    เจ้าของโรงแรม
                                </label>
                                <select name="owner_id"
                                        class="w-full px-4 py-3 rounded-lg border border-white/30 bg-white/10 text-white focus:ring-2 focus:ring-white/50 focus:border-transparent transition-all">
                                    <option value="" class="text-gray-900">-- ไม่ระบุ --</option>
                                    @if(isset($owners))
                                        @foreach($owners as $owner)
                                            <option value="{{ $owner->id }}" class="text-gray-900">{{ $owner->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <p class="mt-1 text-xs text-white/70">เจ้าของหรือผู้จัดการโรงแรม</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-white/90 mb-2">
                                    <i class="fas fa-percentage mr-1"></i>
                                    Commission Rate
                                </label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.01" name="commission_rate" value="{{ old('commission_rate', 0) }}" min="0" max="100"
                                           class="flex-1 px-4 py-3 rounded-lg border border-white/30 bg-white/10 text-white focus:ring-2 focus:ring-white/50 focus:border-transparent transition-all">
                                    <div class="flex-shrink-0 flex items-center px-4 bg-white/10 border border-white/30 rounded-lg text-white">
                                        %
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-white/70">ค่าคอมมิชชั่นสำหรับ Affiliate (0-100%)</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit Actions --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 space-y-3">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                            <i class="fas fa-save"></i>
                            <span>บันทึกโรงแรม</span>
                        </button>

                        <a href="{{ route('admin.hotels.index') }}"
                           class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-times"></i>
                            <span>ยกเลิก</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function hotelForm() {
    return {
        metaTitle: '{{ old("meta_title") }}',
        metaDescription: '{{ old("meta_description") }}',
        imagePreview: null,

        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    }
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

[x-cloak] {
    display: none !important;
}
</style>
@endpush
@endsection

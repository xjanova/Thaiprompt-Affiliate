{{--
    หน้าตั้งค่า Academy System
    ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
--}}
@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า Academy System')

@section('content')
<div x-data="academySettings()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 sm:p-8 text-white mb-8 shadow-xl">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold">⚙️ ตั้งค่า Academy System</h1>
                <p class="text-indigo-100 mt-2">จัดการการตั้งค่าทั้งหมดของระบบ Academy</p>
            </div>
            <div>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                    {{ $settings->is_active ? 'bg-green-500/20 text-green-100' : 'bg-red-500/20 text-red-100' }}">
                    {{ $settings->is_active ? '✅ เปิดใช้งาน' : '❌ ปิดใช้งาน' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-green-800 dark:text-green-200">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 p-4 mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-800 dark:text-red-200">
            <span>❌</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Tabs Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <!-- Tabs Navigation -->
        <div class="flex overflow-x-auto border-b-2 border-gray-100 dark:border-gray-700 scrollbar-thin">
            <template x-for="(tab, index) in tabs" :key="index">
                <button @click="activeTab = tab.id"
                        :class="activeTab === tab.id
                            ? 'text-indigo-600 dark:text-indigo-400 border-indigo-600 dark:border-indigo-400'
                            : 'text-gray-600 dark:text-gray-400 border-transparent hover:text-indigo-500 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        class="flex items-center gap-2 px-4 sm:px-6 py-4 font-semibold text-sm border-b-3 transition-all whitespace-nowrap">
                    <span x-text="tab.icon"></span>
                    <span x-text="tab.name"></span>
                </button>
            </template>
        </div>

        <!-- Tab Content -->
        <div class="p-6 sm:p-8">
            <!-- Basic Settings Tab -->
            <div x-show="activeTab === 'basic'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form action="{{ route('admin.academy.settings.update-basic') }}" method="POST">
                    @csrf
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                            ข้อมูลทั่วไป
                        </h3>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    ชื่อ Academy <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="academy_name"
                                       value="{{ old('academy_name', $settings->academy_name) }}" required
                                       class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    คำอธิบาย
                                </label>
                                <textarea name="academy_description" rows="4"
                                          class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-y">{{ old('academy_description', $settings->academy_description) }}</textarea>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">แนะนำ Academy ของคุณให้ผู้เรียนรู้จัก</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">อีเมล</label>
                                    <input type="email" name="academy_email"
                                           value="{{ old('academy_email', $settings->academy_email) }}"
                                           class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เบอร์โทร</label>
                                    <input type="text" name="academy_phone"
                                           value="{{ old('academy_phone', $settings->academy_phone) }}"
                                           class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เว็บไซต์</label>
                                    <input type="url" name="academy_website"
                                           value="{{ old('academy_website', $settings->academy_website) }}"
                                           class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Branding Tab -->
            <div x-show="activeTab === 'branding'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                        โลโก้
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Main Logo -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">โลโก้หลัก</label>
                            <div @click="$refs.logoMain.click()"
                                 class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="text-4xl mb-3">🖼️</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">คลิกเพื่ออัปโหลด</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">PNG, JPG (สูงสุด 2MB)</div>
                            </div>
                            <input type="file" x-ref="logoMain" class="hidden" accept="image/*"
                                   @change="uploadLogo($event, 'main')">
                            @if($settings->logo_url)
                                <div class="mt-4">
                                    <img src="{{ $settings->logo_url }}" class="max-w-[200px] max-h-[200px] rounded-lg border border-gray-200 dark:border-gray-600" alt="Logo">
                                </div>
                            @endif
                        </div>

                        <!-- Small Logo -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">โลโก้เล็ก (สำหรับใบประกาศ)</label>
                            <div @click="$refs.logoSmall.click()"
                                 class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="text-4xl mb-3">🏷️</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">คลิกเพื่ออัปโหลด</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">PNG, JPG (สูงสุด 2MB)</div>
                            </div>
                            <input type="file" x-ref="logoSmall" class="hidden" accept="image/*"
                                   @change="uploadLogo($event, 'small')">
                            @if($settings->small_logo_url)
                                <div class="mt-4">
                                    <img src="{{ $settings->small_logo_url }}" class="max-w-[200px] max-h-[200px] rounded-lg border border-gray-200 dark:border-gray-600" alt="Small Logo">
                                </div>
                            @endif
                        </div>

                        <!-- Favicon -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Favicon</label>
                            <div @click="$refs.logoFavicon.click()"
                                 class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="text-4xl mb-3">⭐</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">คลิกเพื่ออัปโหลด</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">ICO, PNG (32x32px)</div>
                            </div>
                            <input type="file" x-ref="logoFavicon" class="hidden" accept="image/*"
                                   @change="uploadLogo($event, 'favicon')">
                            @if($settings->favicon_url)
                                <div class="mt-4">
                                    <img src="{{ $settings->favicon_url }}" class="max-w-[200px] max-h-[200px] rounded-lg border border-gray-200 dark:border-gray-600" alt="Favicon">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.academy.settings.update-basic') }}" method="POST">
                    @csrf
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                            สีประจำ Academy
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">สีหลัก (Primary)</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="primary_color"
                                           x-model="primaryColor"
                                           value="{{ old('primary_color', $settings->primary_color) }}"
                                           class="w-12 h-12 rounded-lg border-2 border-gray-200 dark:border-gray-600 cursor-pointer">
                                    <input type="text" name="primary_color_text"
                                           x-model="primaryColor"
                                           placeholder="#6366f1"
                                           class="flex-1 px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">สีรอง (Secondary)</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="secondary_color"
                                           x-model="secondaryColor"
                                           value="{{ old('secondary_color', $settings->secondary_color) }}"
                                           class="w-12 h-12 rounded-lg border-2 border-gray-200 dark:border-gray-600 cursor-pointer">
                                    <input type="text" name="secondary_color_text"
                                           x-model="secondaryColor"
                                           placeholder="#06b6d4"
                                           class="flex-1 px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                            <span>💾</span>
                            <span>บันทึกสี</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Certificate Tab -->
            <div x-show="activeTab === 'certificate'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form action="{{ route('admin.academy.settings.update-certificate') }}" method="POST">
                    @csrf
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                            Template ใบประกาศนียบัตร
                        </h3>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ประเภท Template</label>
                                <select name="certificate_template_type"
                                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="default" {{ $settings->certificate_template_type === 'default' ? 'selected' : '' }}>Template เริ่มต้น</option>
                                    <option value="custom" {{ $settings->certificate_template_type === 'custom' ? 'selected' : '' }}>กำหนดเอง (HTML)</option>
                                    <option value="uploaded" {{ $settings->certificate_template_type === 'uploaded' ? 'selected' : '' }}>อัปโหลดไฟล์</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">รูปแบบขอบ</label>
                                <select name="certificate_border_style"
                                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="classic" {{ $settings->certificate_border_style === 'classic' ? 'selected' : '' }}>คลาสสิก</option>
                                    <option value="modern" {{ $settings->certificate_border_style === 'modern' ? 'selected' : '' }}>โมเดิร์น</option>
                                    <option value="minimal" {{ $settings->certificate_border_style === 'minimal' ? 'selected' : '' }}>มินิมอล</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">HTML Header (ถ้ามี)</label>
                                <textarea name="certificate_header_html" rows="3"
                                          class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-y font-mono text-sm">{{ old('certificate_header_html', $settings->certificate_header_html) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">HTML Footer (ถ้ามี)</label>
                                <textarea name="certificate_footer_html" rows="3"
                                          class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-y font-mono text-sm">{{ old('certificate_footer_html', $settings->certificate_footer_html) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                            การตั้งเลขที่ใบประกาศ
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Prefix</label>
                                <input type="text" name="certificate_number_prefix"
                                       value="{{ old('certificate_number_prefix', $settings->certificate_number_prefix) }}"
                                       placeholder="CERT"
                                       class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ความยาวตัวเลข</label>
                                <input type="number" name="certificate_number_length"
                                       value="{{ old('certificate_number_length', $settings->certificate_number_length) }}"
                                       min="4" max="20"
                                       class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">รูปแบบเลขที่</label>
                            <input type="text" name="certificate_number_format"
                                   value="{{ old('certificate_number_format', $settings->certificate_number_format) }}"
                                   placeholder="CERT-{YEAR}{MONTH}-{RANDOM}"
                                   class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใช้: {YEAR}, {MONTH}, {DAY}, {RANDOM}</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>

                <!-- Upload Background & Templates -->
                <div class="mt-8">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                        ไฟล์ Template
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">พื้นหลังใบประกาศ</label>
                            <div @click="$refs.certBg.click()"
                                 class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="text-4xl mb-3">🎨</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">อัปโหลดพื้นหลัง</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">PNG, JPG (สูงสุด 5MB)</div>
                            </div>
                            <input type="file" x-ref="certBg" class="hidden" accept="image/*"
                                   @change="uploadCertificateBackground($event)">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Word Template</label>
                            <div @click="$refs.certWord.click()"
                                 class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="text-4xl mb-3">📄</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">อัปโหลด .docx</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">DOCX (สูงสุด 10MB)</div>
                            </div>
                            <input type="file" x-ref="certWord" class="hidden" accept=".doc,.docx"
                                   @change="uploadCertificateTemplate($event, 'word')">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">PDF Template</label>
                            <div @click="$refs.certPdf.click()"
                                 class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="text-4xl mb-3">📑</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">อัปโหลด .pdf</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">PDF (สูงสุด 10MB)</div>
                            </div>
                            <input type="file" x-ref="certPdf" class="hidden" accept=".pdf"
                                   @change="uploadCertificateTemplate($event, 'pdf')">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signatures Tab -->
            <div x-show="activeTab === 'signatures'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                        ลายเซ็นบนใบประกาศ
                    </h3>

                    <div class="space-y-4" id="signature-list">
                        @forelse($settings->signatures_with_urls ?? [] as $index => $signature)
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600">
                                <img src="{{ $signature['signature_url'] }}"
                                     class="w-28 h-14 object-contain bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600"
                                     alt="Signature">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 dark:text-white truncate">{{ $signature['name'] }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $signature['title'] }}</div>
                                    <span class="inline-block mt-1 px-2 py-1 bg-white dark:bg-gray-800 rounded text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ ucfirst($signature['position']) }}
                                    </span>
                                </div>
                                <button type="button" @click="removeSignature({{ $index }})"
                                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition">
                                    🗑️ ลบ
                                </button>
                            </div>
                        @empty
                            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <div class="text-5xl mb-4">✍️</div>
                                <div>ยังไม่มีลายเซ็น</div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        <button type="button" @click="showSignatureForm = true"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                            ➕ เพิ่มลายเซ็น
                        </button>
                    </div>

                    <!-- Add Signature Form -->
                    <div x-show="showSignatureForm" x-transition
                         class="mt-6 p-6 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-4">เพิ่มลายเซ็นใหม่</h4>
                        <form @submit.prevent="addSignature">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อผู้เซ็น</label>
                                    <input type="text" x-model="signatureForm.name" required
                                           class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ตำแหน่ง</label>
                                    <input type="text" x-model="signatureForm.title" required
                                           class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ตำแหน่งบนใบประกาศ</label>
                                    <select x-model="signatureForm.position" required
                                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                        <option value="left">ซ้าย</option>
                                        <option value="center">กลาง</option>
                                        <option value="right">ขวา</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ไฟล์ลายเซ็น</label>
                                    <input type="file" x-ref="signatureFile" accept="image/*" required
                                           class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit"
                                            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                                        บันทึก
                                    </button>
                                    <button type="button" @click="showSignatureForm = false"
                                            class="px-6 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                                        ยกเลิก
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Email Tab -->
            <div x-show="activeTab === 'email'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form action="{{ route('admin.academy.settings.update-email') }}" method="POST">
                    @csrf
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                            การตั้งค่าอีเมล
                        </h3>

                        <div class="space-y-5">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" name="send_certificate_email" value="1"
                                           {{ $settings->send_certificate_email ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:bg-green-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow peer-checked:translate-x-7 transition-transform"></div>
                                </div>
                                <span class="font-semibold text-gray-700 dark:text-gray-300">ส่งอีเมลเมื่อได้รับใบประกาศ</span>
                            </label>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">หัวเรื่องอีเมล</label>
                                <input type="text" name="certificate_email_subject"
                                       value="{{ old('certificate_email_subject', $settings->certificate_email_subject) }}"
                                       placeholder="ยินดีด้วย! คุณได้รับใบประกาศนียบัตร"
                                       class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เนื้อหาอีเมล</label>
                                <textarea name="certificate_email_body" rows="6"
                                          class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-y">{{ old('certificate_email_body', $settings->certificate_email_body) }}</textarea>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใช้ {course_title}, {student_name} สำหรับแทนค่า</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Course Tab -->
            <div x-show="activeTab === 'course'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form action="{{ route('admin.academy.settings.update-course') }}" method="POST">
                    @csrf
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                            เงื่อนไขการรับใบประกาศ
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ความคืบหน้าขั้นต่ำ (%)</label>
                                <input type="number" name="min_completion_percentage"
                                       value="{{ old('min_completion_percentage', $settings->min_completion_percentage) }}"
                                       min="0" max="100"
                                       class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คะแนน Quiz ขั้นต่ำ (%)</label>
                                <input type="number" name="min_quiz_score"
                                       value="{{ old('min_quiz_score', $settings->min_quiz_score) }}"
                                       min="0" max="100"
                                       class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>
                        </div>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" name="require_all_quizzes" value="1"
                                       {{ $settings->require_all_quizzes ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:bg-green-500 transition-colors"></div>
                                <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow peer-checked:translate-x-7 transition-transform"></div>
                            </div>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">ต้องทำ Quiz ทั้งหมด</span>
                        </label>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                            ฟีเจอร์คอร์ส
                        </h3>

                        <div class="space-y-4">
                            @php
                                $courseFeatures = [
                                    ['name' => 'enable_student_reviews', 'label' => 'เปิดให้นักเรียนรีวิว'],
                                    ['name' => 'enable_course_discussions', 'label' => 'เปิดห้องสนทนา'],
                                    ['name' => 'enable_course_notes', 'label' => 'เปิดระบบจดโน้ต'],
                                    ['name' => 'enable_course_downloads', 'label' => 'อนุญาตให้ดาวน์โหลดเอกสาร'],
                                    ['name' => 'enable_course_preview', 'label' => 'อนุญาตให้ดูตัวอย่างคอร์ส'],
                                    ['name' => 'require_enrollment_approval', 'label' => 'ต้องได้รับอนุมัติก่อนลงเรียน'],
                                ];
                            @endphp

                            @foreach($courseFeatures as $feature)
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" name="{{ $feature['name'] }}" value="1"
                                               {{ $settings->{$feature['name']} ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:bg-green-500 transition-colors"></div>
                                        <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow peer-checked:translate-x-7 transition-transform"></div>
                                    </div>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $feature['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Instructor Tab -->
            <div x-show="activeTab === 'instructor'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form action="{{ route('admin.academy.settings.update-instructor') }}" method="POST">
                    @csrf
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                            การตั้งค่าครูผู้สอน
                        </h3>

                        <div class="space-y-5">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" name="allow_instructors_create_courses" value="1"
                                           {{ $settings->allow_instructors_create_courses ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:bg-green-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow peer-checked:translate-x-7 transition-transform"></div>
                                </div>
                                <span class="font-semibold text-gray-700 dark:text-gray-300">อนุญาตให้ครูสร้างคอร์สเอง</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" name="require_course_approval" value="1"
                                           {{ $settings->require_course_approval ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:bg-green-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow peer-checked:translate-x-7 transition-transform"></div>
                                </div>
                                <span class="font-semibold text-gray-700 dark:text-gray-300">คอร์สต้องได้รับอนุมัติก่อนเผยแพร่</span>
                            </label>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค่าคอมมิชชั่นครูผู้สอน (%)</label>
                                <input type="number" name="instructor_commission_percentage"
                                       value="{{ old('instructor_commission_percentage', $settings->instructor_commission_percentage) }}"
                                       min="0" max="100"
                                       class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เปอร์เซ็นต์รายได้ที่ครูจะได้รับจากการขายคอร์ส</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับจัดการ Academy Settings
 * ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
 */
function academySettings() {
    return {
        activeTab: 'basic',
        showSignatureForm: false,
        primaryColor: '{{ $settings->primary_color ?? '#6366f1' }}',
        secondaryColor: '{{ $settings->secondary_color ?? '#06b6d4' }}',
        signatureForm: {
            name: '',
            title: '',
            position: 'left'
        },
        tabs: [
            { id: 'basic', name: 'ข้อมูลพื้นฐาน', icon: '📝' },
            { id: 'branding', name: 'โลโก้และสี', icon: '🎨' },
            { id: 'certificate', name: 'ใบประกาศนียบัตร', icon: '📜' },
            { id: 'signatures', name: 'ลายเซ็น', icon: '✍️' },
            { id: 'email', name: 'อีเมล', icon: '✉️' },
            { id: 'course', name: 'คอร์ส', icon: '📚' },
            { id: 'instructor', name: 'ครูผู้สอน', icon: '👨‍🏫' }
        ],

        /**
         * อัปโหลดโลโก้
         */
        async uploadLogo(event, type) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('logo', file);
            formData.append('type', type);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('{{ route("admin.academy.settings.upload-logo") }}', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ อัปโหลดสำเร็จ!');
                    location.reload();
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด');
                console.error(error);
            }
        },

        /**
         * อัปโหลดพื้นหลังใบประกาศ
         */
        async uploadCertificateBackground(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('background', file);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('{{ route("admin.academy.settings.upload-certificate-background") }}', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ อัปโหลดสำเร็จ!');
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด');
                console.error(error);
            }
        },

        /**
         * อัปโหลด template ใบประกาศ
         */
        async uploadCertificateTemplate(event, type) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('template', file);
            formData.append('type', type);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('{{ route("admin.academy.settings.upload-certificate-template") }}', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ อัปโหลดสำเร็จ!');
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด');
                console.error(error);
            }
        },

        /**
         * เพิ่มลายเซ็น
         */
        async addSignature() {
            const fileInput = this.$refs.signatureFile;
            if (!fileInput.files[0]) {
                alert('กรุณาเลือกไฟล์ลายเซ็น');
                return;
            }

            const formData = new FormData();
            formData.append('name', this.signatureForm.name);
            formData.append('title', this.signatureForm.title);
            formData.append('position', this.signatureForm.position);
            formData.append('signature', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('{{ route("admin.academy.settings.add-signature") }}', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ เพิ่มลายเซ็นสำเร็จ!');
                    location.reload();
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด');
                console.error(error);
            }
        },

        /**
         * ลบลายเซ็น
         */
        async removeSignature(index) {
            if (!confirm('ต้องการลบลายเซ็นนี้?')) return;

            try {
                const response = await fetch(`{{ url('admin/academy/settings/remove-signature') }}/${index}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ ลบสำเร็จ!');
                    location.reload();
                } else {
                    alert('❌ เกิดข้อผิดพลาด');
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด');
                console.error(error);
            }
        }
    }
}
</script>
@endpush
@endsection

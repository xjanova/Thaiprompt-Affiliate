@extends('layouts.admin-v3')

@section('title', 'การตั้งค่าเว็บไซต์')

@section('content')
<div class="container-fluid px-4 py-6" x-data="siteSettingsManager()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-black bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 bg-clip-text text-transparent mb-2">
                    การตั้งค่าเว็บไซต์
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    จัดการโลโก้, ชื่อเว็บไซต์, SEO, Social Media และการตั้งค่าอื่นๆ
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300 border-2 border-gray-200 dark:border-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl shadow-lg flex items-center gap-3">
            <i class="fas fa-check-circle text-2xl"></i>
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl shadow-lg flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-2xl"></i>
            <span class="font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl shadow-lg">
            <div class="flex items-center gap-3 mb-2">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
                <span class="font-bold text-lg">พบข้อผิดพลาด:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 ml-8">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tabs Navigation --}}
    <div class="glass-fusion-card rounded-2xl p-2 mb-6 shadow-2xl border border-white/20 dark:border-gray-700/50">
        <div class="flex flex-wrap gap-2">
            <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-info-circle"></i>
                <span class="hidden sm:inline">ข้อมูลทั่วไป</span>
            </button>

            <button @click="activeTab = 'branding'" :class="activeTab === 'branding' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-palette"></i>
                <span class="hidden sm:inline">โลโก้ & สี</span>
            </button>

            <button @click="activeTab = 'seo'" :class="activeTab === 'seo' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-search"></i>
                <span class="hidden sm:inline">SEO</span>
            </button>

            <button @click="activeTab = 'contact'" :class="activeTab === 'contact' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-address-book"></i>
                <span class="hidden sm:inline">ติดต่อ</span>
            </button>

            <button @click="activeTab = 'social'" :class="activeTab === 'social' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-share-alt"></i>
                <span class="hidden sm:inline">Social Media</span>
            </button>

            <button @click="activeTab = 'analytics'" :class="activeTab === 'analytics' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-chart-line"></i>
                <span class="hidden sm:inline">Analytics</span>
            </button>

            <button @click="activeTab = 'advanced'" :class="activeTab === 'advanced' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-cog"></i>
                <span class="hidden sm:inline">ขั้นสูง</span>
            </button>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.site-settings.update') }}" method="POST" enctype="multipart/form-data" @change="formChanged = true" x-ref="settingsForm">
        @csrf
        @method('PUT')

        {{-- Tab: General --}}
        <div x-show="activeTab === 'general'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-info-circle text-white text-xl"></i>
                </div>
                ข้อมูลทั่วไป
            </h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- Site Name --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อเว็บไซต์ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="site_name" value="{{ old('site_name', $settings->site_name) }}" required class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="TP-Affiliate">
                </div>

                {{-- Site Description --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบายเว็บไซต์
                    </label>
                    <textarea name="site_description" rows="4" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="ระบบ Affiliate Marketing ที่ทรงพลังที่สุด">{{ old('site_description', $settings->site_description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Tab: Branding --}}
        <div x-show="activeTab === 'branding'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-palette text-white text-xl"></i>
                </div>
                โลโก้ & สีธีม
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Logo Light --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        โลโก้ (Light Mode)
                    </label>
                    <div class="relative group">
                        <div class="w-full h-48 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-xl flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-600 group-hover:border-purple-500 dark:group-hover:border-purple-400 transition-all">
                            <img :src="logoPreview || '{{ $settings->logo ? asset('storage/' . $settings->logo) : asset('images/logo.png') }}'" alt="Logo" class="max-w-full max-h-full object-contain">
                        </div>
                        <label for="logo-upload" class="mt-3 block w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-300 cursor-pointer text-center">
                            <i class="fas fa-upload mr-2"></i>อัพโหลดโลโก้
                        </label>
                        <input type="file" id="logo-upload" name="logo" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" class="hidden" @change="handleImageChange($event, 'logo')">

                        @if($settings->logo)
                            <button type="button" @click="deleteLogo('logo')" class="mt-2 w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg shadow-lg transition-all">
                                <i class="fas fa-trash mr-2"></i>ลบโลโก้
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Logo Dark --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        โลโก้ (Dark Mode)
                    </label>
                    <div class="relative group">
                        <div class="w-full h-48 bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-600 group-hover:border-purple-400 transition-all">
                            <img :src="logoDarkPreview || '{{ $settings->logo_dark ? asset('storage/' . $settings->logo_dark) : ($settings->logo ? asset('storage/' . $settings->logo) : asset('images/logo.png')) }}'" alt="Logo Dark" class="max-w-full max-h-full object-contain">
                        </div>
                        <label for="logo-dark-upload" class="mt-3 block w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-300 cursor-pointer text-center">
                            <i class="fas fa-upload mr-2"></i>อัพโหลดโลโก้ Dark
                        </label>
                        <input type="file" id="logo-dark-upload" name="logo_dark" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" class="hidden" @change="handleImageChange($event, 'logoDark')">

                        @if($settings->logo_dark)
                            <button type="button" @click="deleteLogo('logo_dark')" class="mt-2 w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg shadow-lg transition-all">
                                <i class="fas fa-trash mr-2"></i>ลบโลโก้ Dark
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Favicon --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Favicon (ไอคอนเว็บไซต์)
                    </label>
                    <div class="relative group">
                        <div class="w-full h-48 bg-gradient-to-br from-blue-100 to-cyan-200 dark:from-blue-900 dark:to-cyan-900 rounded-xl flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-600 group-hover:border-blue-500 dark:group-hover:border-blue-400 transition-all">
                            <img :src="faviconPreview || '{{ $settings->favicon ? asset('storage/' . $settings->favicon) : asset('favicon.ico') }}'" alt="Favicon" class="w-32 h-32 object-contain">
                        </div>
                        <label for="favicon-upload" class="mt-3 block w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-300 cursor-pointer text-center">
                            <i class="fas fa-upload mr-2"></i>อัพโหลด Favicon
                        </label>
                        <input type="file" id="favicon-upload" name="favicon" accept="image/jpeg,image/png,image/gif,image/webp,image/x-icon" class="hidden" @change="handleImageChange($event, 'favicon')">

                        @if($settings->favicon)
                            <button type="button" @click="deleteLogo('favicon')" class="mt-2 w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg shadow-lg transition-all">
                                <i class="fas fa-trash mr-2"></i>ลบ Favicon
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Logo Spin Toggle --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        การหมุนโลโก้
                    </label>
                    <div class="glass-fusion-card rounded-xl p-6 border border-white/20 dark:border-gray-700/50">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg">ให้โลโก้หมุน</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">เปิดใช้งานเอฟเฟกต์การหมุนโลโก้</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="logo_spin" value="1" {{ old('logo_spin', $settings->logo_spin) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-14 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-pink-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- หมายเหตุ: ระบบสีถูกจัดการโดย Custom Theme (ThemeSetting) แล้ว --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-xl mt-1"></i>
                        <div>
                            <h4 class="font-bold text-blue-900 dark:text-blue-300 mb-1">การตั้งค่าสี & Theme</h4>
                            <p class="text-sm text-blue-800 dark:text-blue-400">
                                ระบบใช้ <strong>Custom Theme (Arrow X)</strong> สำหรับจัดการสีและรูปแบบการแสดงผล
                                <br>สามารถปรับแต่งได้ที่ <strong>ตั้งค่า > Theme Settings</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: SEO --}}
        <div x-show="activeTab === 'seo'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-search text-white text-xl"></i>
                </div>
                การตั้งค่า SEO
            </h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- Meta Keywords --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        คำค้นหา (Meta Keywords)
                    </label>
                    <textarea name="meta_keywords" rows="3" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="affiliate, marketing, mlm, คอมมิชชั่น">{{ old('meta_keywords', $settings->meta_keywords) }}</textarea>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>คำค้นหาที่เกี่ยวข้องกับเว็บไซต์ (คั่นด้วย comma)
                    </p>
                </div>

                {{-- Meta Description --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย (Meta Description)
                    </label>
                    <textarea name="meta_description" rows="4" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="ระบบ Affiliate Marketing ที่ทรงพลังที่สุด พร้อมระบบ MLM หลายระดับ">{{ old('meta_description', $settings->meta_description) }}</textarea>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>คำอธิบายที่จะแสดงใน Search Engine Results (แนะนำ 150-160 ตัวอักษร)
                    </p>
                </div>
            </div>
        </div>

        {{-- Tab: Contact --}}
        <div x-show="activeTab === 'contact'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-address-book text-white text-xl"></i>
                </div>
                ข้อมูลติดต่อ
            </h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- Contact Email --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        อีเมลติดต่อ
                    </label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="contact@example.com">
                </div>

                {{-- Contact Phone --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        เบอร์โทรติดต่อ
                    </label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="02-xxx-xxxx">
                </div>

                {{-- Contact Address --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        ที่อยู่ติดต่อ
                    </label>
                    <textarea name="contact_address" rows="4" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="123 ถนน... เขต... กรุงเทพฯ">{{ old('contact_address', $settings->contact_address) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Tab: Social Media --}}
        <div x-show="activeTab === 'social'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-share-alt text-white text-xl"></i>
                </div>
                Social Media
            </h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- Facebook --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fab fa-facebook text-blue-600 text-xl"></i>
                        Facebook URL
                    </label>
                    <input type="url" name="facebook_url" value="{{ old('facebook_url', $settings->facebook_url) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="https://facebook.com/yourpage">
                </div>

                {{-- Twitter --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fab fa-twitter text-blue-400 text-xl"></i>
                        Twitter/X URL
                    </label>
                    <input type="url" name="twitter_url" value="{{ old('twitter_url', $settings->twitter_url) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="https://twitter.com/yourhandle">
                </div>

                {{-- Instagram --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fab fa-instagram text-pink-600 text-xl"></i>
                        Instagram URL
                    </label>
                    <input type="url" name="instagram_url" value="{{ old('instagram_url', $settings->instagram_url) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="https://instagram.com/yourhandle">
                </div>

                {{-- LINE --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fab fa-line text-green-600 text-xl"></i>
                        LINE URL
                    </label>
                    <input type="url" name="line_url" value="{{ old('line_url', $settings->line_url) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="https://line.me/ti/p/...">
                </div>

                {{-- YouTube --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fab fa-youtube text-red-600 text-xl"></i>
                        YouTube URL
                    </label>
                    <input type="url" name="youtube_url" value="{{ old('youtube_url', $settings->youtube_url) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="https://youtube.com/@yourchannel">
                </div>
            </div>
        </div>

        {{-- Tab: Analytics --}}
        <div x-show="activeTab === 'analytics'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                Analytics & Tracking
            </h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- Google Analytics --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Google Analytics ID
                    </label>
                    <input type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $settings->google_analytics_id) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all font-mono" placeholder="G-XXXXXXXXXX หรือ UA-XXXXXXXXX">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>รหัส Tracking ID จาก Google Analytics
                    </p>
                </div>

                {{-- Facebook Pixel --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Facebook Pixel ID
                    </label>
                    <input type="text" name="facebook_pixel_id" value="{{ old('facebook_pixel_id', $settings->facebook_pixel_id) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all font-mono" placeholder="123456789012345">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>รหัส Pixel ID จาก Facebook Business
                    </p>
                </div>

                {{-- Google Tag Manager --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Google Tag Manager ID
                    </label>
                    <input type="text" name="google_tag_manager_id" value="{{ old('google_tag_manager_id', $settings->google_tag_manager_id) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all font-mono" placeholder="GTM-XXXXXXX">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>รหัส Container ID จาก Google Tag Manager
                    </p>
                </div>
            </div>
        </div>

        {{-- Tab: Advanced --}}
        <div x-show="activeTab === 'advanced'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-gray-600 to-gray-800 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-cog text-white text-xl"></i>
                </div>
                การตั้งค่าขั้นสูง
            </h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- Maintenance Mode --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        โหมดปิดปรับปรุง
                    </label>
                    <div class="glass-fusion-card rounded-xl p-6 border border-white/20 dark:border-gray-700/50">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg">เปิดโหมดปิดปรับปรุง</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ผู้ใช้ทั่วไปจะไม่สามารถเข้าใช้งานได้ (Admin ยังเข้าได้)</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="maintenance_mode" value="1" {{ old('maintenance_mode', $settings->maintenance_mode) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-14 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-red-600 peer-checked:to-orange-600"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความแจ้งเตือน
                            </label>
                            <textarea name="maintenance_message" rows="3" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="เว็บไซต์กำลังปิดปรับปรุง กรุณากลับมาใหม่อีกครั้ง">{{ old('maintenance_message', $settings->maintenance_message) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Header Scripts --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Header Scripts (ใส่ใน &lt;head&gt;)
                    </label>
                    <textarea name="header_scripts" rows="6" class="w-full px-4 py-3 bg-gray-900 text-green-400 border-2 border-gray-700 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 transition-all font-mono text-sm" placeholder="<script>&#10;  // Your custom scripts here&#10;</script>">{{ old('header_scripts', $settings->header_scripts) }}</textarea>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>สำหรับผู้ใช้ที่มีความรู้เกี่ยวกับ HTML/JavaScript เท่านั้น
                    </p>
                </div>

                {{-- Footer Scripts --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Footer Scripts (ใส่ก่อน &lt;/body&gt;)
                    </label>
                    <textarea name="footer_scripts" rows="6" class="w-full px-4 py-3 bg-gray-900 text-green-400 border-2 border-gray-700 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 transition-all font-mono text-sm" placeholder="<script>&#10;  // Your custom scripts here&#10;</script>">{{ old('footer_scripts', $settings->footer_scripts) }}</textarea>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>สำหรับผู้ใช้ที่มีความรู้เกี่ยวกับ HTML/JavaScript เท่านั้น
                    </p>
                </div>
            </div>
        </div>

        {{-- Static Save Button (Mobile) --}}
        <div class="mt-8 lg:hidden">
            <button type="submit" class="w-full px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700 text-white font-bold text-lg rounded-xl shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center gap-3">
                <i class="fas fa-save text-2xl"></i>
                <span>บันทึกการตั้งค่า</span>
            </button>
        </div>
    </form>

    {{-- Floating Save Button (Desktop) --}}
    <div x-show="formChanged" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="hidden lg:block fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50" style="display: none;">
        <div class="glass-fusion-card rounded-full shadow-2xl p-2 flex items-center gap-4 border-2 border-purple-500/50">
            <button type="button" @click="$refs.settingsForm.submit()" class="px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700 text-white font-bold text-lg rounded-full shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300 flex items-center gap-3">
                <i class="fas fa-save text-2xl"></i>
                <span>บันทึกการตั้งค่า</span>
            </button>
            <button type="button" @click="formChanged = false; window.location.reload()" class="px-6 py-4 bg-white/20 hover:bg-white/30 dark:bg-gray-800/50 dark:hover:bg-gray-800/70 text-gray-700 dark:text-gray-300 font-semibold rounded-full transition-all">
                <i class="fas fa-times mr-2"></i>ยกเลิก
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function siteSettingsManager() {
    return {
        activeTab: 'general',
        formChanged: false,
        logoPreview: null,
        logoDarkPreview: null,
        faviconPreview: null,

        /**
         * จัดการการเปลี่ยนรูปภาพและแสดง Preview
         */
        handleImageChange(event, type) {
            const file = event.target.files[0];
            if (!file) return;

            // ตรวจสอบประเภทไฟล์
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/x-icon'];
            if (!allowedTypes.includes(file.type)) {
                alert('กรุณาเลือกไฟล์รูปภาพที่รองรับเท่านั้น (JPG, PNG, GIF, WebP, SVG, ICO)');
                event.target.value = '';
                return;
            }

            // ตรวจสอบขนาดไฟล์
            const maxSize = type === 'favicon' ? 1024 * 1024 : 2 * 1024 * 1024; // 1MB for favicon, 2MB for logos
            if (file.size > maxSize) {
                const maxSizeMB = maxSize / (1024 * 1024);
                alert(`ขนาดไฟล์ต้องไม่เกิน ${maxSizeMB}MB`);
                event.target.value = '';
                return;
            }

            // แสดง Preview
            const reader = new FileReader();
            reader.onload = (e) => {
                if (type === 'logo') {
                    this.logoPreview = e.target.result;
                } else if (type === 'logoDark') {
                    this.logoDarkPreview = e.target.result;
                } else if (type === 'favicon') {
                    this.faviconPreview = e.target.result;
                }
                this.formChanged = true;
            };
            reader.readAsDataURL(file);
        },

        /**
         * ลบโลโก้/Favicon
         */
        deleteLogo(type) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบรูปภาพนี้?')) {
                return;
            }

            // สร้าง form สำหรับส่งคำขอลบ
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.site-settings.logo.delete") }}';

            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            // Method DELETE
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            // Type
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'type';
            typeInput.value = type;
            form.appendChild(typeInput);

            // Submit form
            document.body.appendChild(form);
            form.submit();
        }
    };
}
</script>
@endpush
@endsection

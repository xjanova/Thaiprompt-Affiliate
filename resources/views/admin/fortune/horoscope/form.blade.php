{{-- resources/views/admin/fortune/horoscope/form.blade.php --}}
{{-- ฟอร์มสร้าง/แก้ไขแคมเปญดวงรายวัน --}}

@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="horoscopeCampaignForm()">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.fortune.horoscope.index') }}"
           class="px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
            ← กลับ
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🔮 {{ $pageTitle }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                ตั้งค่าแพลตฟอร์ม, AI provider, เวลาโพส, และ prompt template
            </p>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl">
            <p class="text-red-800 dark:text-red-200 font-semibold mb-2">กรุณาแก้ไขข้อผิดพลาด:</p>
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.fortune.horoscope.update', $campaign) : route('admin.fortune.horoscope.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-8">

            {{-- ============================================================ --}}
            {{-- Section 1: ข้อมูลทั่วไป --}}
            {{-- ============================================================ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    📝 ข้อมูลทั่วไป
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- ชื่อแคมเปญ --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ชื่อแคมเปญ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name"
                               value="{{ old('name', $campaign->name) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="เช่น ดวงรายวัน หน้าเพจหลัก"
                               required>
                    </div>

                    {{-- คำอธิบาย --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            คำอธิบาย
                        </label>
                        <input type="text" name="description"
                               value="{{ old('description', $campaign->description) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="คำอธิบายสั้นๆ">
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 2: แพลตฟอร์มที่จะโพส --}}
            {{-- ============================================================ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    📡 แพลตฟอร์มที่จะโพส
                </h2>

                {{-- Toggle: ใช้ Token จากการตั้งค่าดูดวง --}}
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="use_fortune_settings_tokens" value="0">
                        <input type="checkbox" name="use_fortune_settings_tokens" value="1"
                               x-model="useFortuneTokens"
                               class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
                               {{ old('use_fortune_settings_tokens', $campaign->use_fortune_settings_tokens ?? true) ? 'checked' : '' }}>
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">ใช้ Token จากการตั้งค่าดูดวง</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                ดึง Facebook Page Token / LINE Channel Access Token จากหน้าตั้งค่าระบบดูดวงอัตโนมัติ
                            </p>
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Facebook --}}
                    <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <label class="flex items-center gap-3 cursor-pointer mb-4">
                            <input type="hidden" name="post_to_facebook" value="0">
                            <input type="checkbox" name="post_to_facebook" value="1"
                                   x-model="postToFacebook"
                                   class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
                                   {{ old('post_to_facebook', $campaign->post_to_facebook) ? 'checked' : '' }}>
                            <span class="font-semibold text-gray-900 dark:text-white">📘 โพสลง Facebook Page</span>
                        </label>

                        <div x-show="postToFacebook && !useFortuneTokens" x-transition class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Facebook Page ID</label>
                                <input type="text" name="facebook_page_id"
                                       value="{{ old('facebook_page_id', $campaign->facebook_page_id) }}"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500"
                                       placeholder="123456789">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Page Access Token</label>
                                <input type="password" name="facebook_page_token"
                                       value="{{ old('facebook_page_token') }}"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500"
                                       placeholder="{{ $isEdit && $campaign->facebook_page_token ? '••••••• (มีค่าอยู่แล้ว)' : 'EAAx...' }}">
                            </div>
                        </div>
                        <div x-show="postToFacebook && useFortuneTokens" x-transition>
                            <p class="text-xs text-blue-600 dark:text-blue-400">
                                ℹ️ จะใช้ Token จากหน้าตั้งค่าดูดวงอัตโนมัติ
                            </p>
                        </div>
                    </div>

                    {{-- LINE --}}
                    <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <label class="flex items-center gap-3 cursor-pointer mb-4">
                            <input type="hidden" name="post_to_line" value="0">
                            <input type="checkbox" name="post_to_line" value="1"
                                   x-model="postToLine"
                                   class="w-5 h-5 text-green-600 rounded focus:ring-green-500"
                                   {{ old('post_to_line', $campaign->post_to_line) ? 'checked' : '' }}>
                            <span class="font-semibold text-gray-900 dark:text-white">💚 โพสลง LINE OA (Broadcast)</span>
                        </label>

                        <div x-show="postToLine && !useFortuneTokens" x-transition class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">LINE Channel Access Token</label>
                                <input type="password" name="line_channel_access_token"
                                       value="{{ old('line_channel_access_token') }}"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500"
                                       placeholder="{{ $isEdit && $campaign->line_channel_access_token ? '••••••• (มีค่าอยู่แล้ว)' : 'Token...' }}">
                            </div>
                        </div>
                        <div x-show="postToLine && useFortuneTokens" x-transition>
                            <p class="text-xs text-green-600 dark:text-green-400">
                                ℹ️ จะใช้ Token จากหน้าตั้งค่าดูดวงอัตโนมัติ
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 3: ตั้งค่า AI Provider --}}
            {{-- ============================================================ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    🤖 ตั้งค่า AI Provider
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- AI Text Provider --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            AI สร้างคำทำนาย
                        </label>
                        <select name="ai_text_provider"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <option value="auto" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'auto' ? 'selected' : '' }}>
                                🔄 อัตโนมัติ (ลอง key ทุกตัว)
                            </option>
                            <option value="openai" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'openai' ? 'selected' : '' }}>
                                OpenAI (GPT)
                            </option>
                            <option value="gemini" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'gemini' ? 'selected' : '' }}>
                                Google Gemini
                            </option>
                            <option value="typhoon" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'typhoon' ? 'selected' : '' }}>
                                Typhoon (ไทย)
                            </option>
                            <option value="deepseek" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'deepseek' ? 'selected' : '' }}>
                                DeepSeek
                            </option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            "อัตโนมัติ" จะ rotate key ตามที่ตั้งค่าในระบบ FortuneAI
                        </p>
                    </div>

                    {{-- AI Image Provider --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            AI สร้างรูปภาพ
                        </label>
                        <select name="ai_image_provider"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <option value="pollinations" {{ old('ai_image_provider', $campaign->ai_image_provider) === 'pollinations' ? 'selected' : '' }}>
                                🆓 Pollinations (ฟรี)
                            </option>
                            <option value="openai" {{ old('ai_image_provider', $campaign->ai_image_provider) === 'openai' ? 'selected' : '' }}>
                                OpenAI DALL-E
                            </option>
                            <option value="huggingface" {{ old('ai_image_provider', $campaign->ai_image_provider) === 'huggingface' ? 'selected' : '' }}>
                                🤗 Hugging Face (ฟรี)
                            </option>
                        </select>
                    </div>

                    {{-- Image Model --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Image Model
                        </label>
                        <input type="text" name="ai_image_model"
                               value="{{ old('ai_image_model', $campaign->ai_image_model ?? 'flux') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                               placeholder="flux">
                    </div>

                    {{-- Image Size --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ขนาดรูป
                        </label>
                        <select name="image_size"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <option value="1024x1024" {{ old('image_size', $campaign->image_size) === '1024x1024' ? 'selected' : '' }}>1024x1024 (สี่เหลี่ยม)</option>
                            <option value="1024x768" {{ old('image_size', $campaign->image_size) === '1024x768' ? 'selected' : '' }}>1024x768 (แนวนอน)</option>
                            <option value="768x1024" {{ old('image_size', $campaign->image_size) === '768x1024' ? 'selected' : '' }}>768x1024 (แนวตั้ง)</option>
                        </select>
                    </div>

                    {{-- Image Style --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            สไตล์รูปภาพ
                        </label>
                        <input type="text" name="image_style"
                               value="{{ old('image_style', $campaign->image_style) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                               placeholder="เช่น thai-zodiac, mystical, anime">
                    </div>
                </div>

                {{-- Options --}}
                <div class="mt-6 flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="include_image" value="0">
                        <input type="checkbox" name="include_image" value="1"
                               class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500"
                               {{ old('include_image', $campaign->include_image ?? true) ? 'checked' : '' }}>
                        <span class="text-gray-700 dark:text-gray-300">🖼 สร้างรูปภาพ AI</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="include_lucky_info" value="0">
                        <input type="checkbox" name="include_lucky_info" value="1"
                               class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500"
                               {{ old('include_lucky_info', $campaign->include_lucky_info ?? true) ? 'checked' : '' }}>
                        <span class="text-gray-700 dark:text-gray-300">🍀 แทรกสีมงคล/เลขมงคล/ทิศมงคล</span>
                    </label>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 4: เวลาและกำหนดการ --}}
            {{-- ============================================================ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    ⏰ เวลาและกำหนดการ
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- เวลาโพส --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            เวลาโพส
                        </label>
                        <input type="time" name="schedule_time"
                               value="{{ old('schedule_time', $campaign->schedule_time ?? '06:00') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    </div>

                    {{-- Timezone --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Timezone
                        </label>
                        <select name="timezone"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <option value="Asia/Bangkok" {{ old('timezone', $campaign->timezone) === 'Asia/Bangkok' ? 'selected' : '' }}>Asia/Bangkok (ICT +7)</option>
                            <option value="UTC" {{ old('timezone', $campaign->timezone) === 'UTC' ? 'selected' : '' }}>UTC</option>
                        </select>
                    </div>

                    {{-- Post Format --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            รูปแบบโพส
                        </label>
                        <select name="post_format"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <option value="combined" {{ old('post_format', $campaign->post_format) === 'combined' ? 'selected' : '' }}>
                                รวม 7 วันเป็น 1 โพส
                            </option>
                            <option value="single" {{ old('post_format', $campaign->post_format) === 'single' ? 'selected' : '' }}>
                                แยก 7 โพส (แต่ละวันเกิด)
                            </option>
                        </select>
                    </div>
                </div>

                {{-- วันที่ใช้งาน --}}
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันที่โพส (เว้นว่าง = ทุกวัน)
                    </label>
                    <div class="flex flex-wrap gap-3">
                        @php
                            $activeDays = old('active_days', $campaign->active_days ?? []);
                        @endphp
                        @foreach(\App\Models\FortuneHoroscopeCampaign::THAI_DAYS as $idx => $dayName)
                            <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <input type="checkbox" name="active_days[]" value="{{ $idx }}"
                                       class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500"
                                       {{ is_array($activeDays) && in_array($idx, $activeDays) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $dayName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- วันเกิดที่สร้าง --}}
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันเกิดที่สร้างคำทำนาย (เว้นว่าง = ทุกวันเกิด)
                    </label>
                    <div class="flex flex-wrap gap-3">
                        @php
                            $targetBirthDays = old('target_birth_days', $campaign->target_birth_days ?? []);
                        @endphp
                        @foreach(\App\Models\FortuneHoroscopeCampaign::THAI_DAYS as $idx => $dayName)
                            <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <input type="checkbox" name="target_birth_days[]" value="{{ $idx }}"
                                       class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500"
                                       {{ is_array($targetBirthDays) && in_array($idx, $targetBirthDays) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $dayName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 5: Prompt Templates --}}
            {{-- ============================================================ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    📄 Prompt Templates
                </h2>

                {{-- Text Prompt --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Prompt สร้างคำทำนาย (Text)
                    </label>
                    <textarea name="text_prompt_template" rows="8"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                              placeholder="สร้างคำทำนายดวงรายวัน...">{{ old('text_prompt_template', $campaign->text_prompt_template) }}</textarea>
                    <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-xs text-gray-500 dark:text-gray-400">
                        <p class="font-semibold mb-1">ตัวแปรที่ใช้ได้:</p>
                        <code class="text-purple-600 dark:text-purple-400">{target_date}</code> วันที่เป้าหมาย,
                        <code class="text-purple-600 dark:text-purple-400">{birth_day_name}</code> ชื่อวันเกิด,
                        <code class="text-purple-600 dark:text-purple-400">{main_planet}</code> ดาวประจำวัน,
                        <code class="text-purple-600 dark:text-purple-400">{element}</code> ธาตุ,
                        <code class="text-purple-600 dark:text-purple-400">{lucky_color}</code> สีมงคล,
                        <code class="text-purple-600 dark:text-purple-400">{friend_planets}</code> ดาวมิตร,
                        <code class="text-purple-600 dark:text-purple-400">{enemy_planets}</code> ดาวศัตรู,
                        <code class="text-purple-600 dark:text-purple-400">{planet_positions}</code> ตำแหน่งดาว
                    </div>
                </div>

                {{-- Image Prompt --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Prompt สร้างรูปภาพ (Image)
                    </label>
                    <textarea name="image_prompt_template" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                              placeholder="Thai astrology zodiac card...">{{ old('image_prompt_template', $campaign->image_prompt_template) }}</textarea>
                    <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-xs text-gray-500 dark:text-gray-400">
                        <p class="font-semibold mb-1">ตัวแปรที่ใช้ได้:</p>
                        <code class="text-purple-600 dark:text-purple-400">{birth_day_name}</code>,
                        <code class="text-purple-600 dark:text-purple-400">{main_planet}</code>,
                        <code class="text-purple-600 dark:text-purple-400">{element}</code>,
                        <code class="text-purple-600 dark:text-purple-400">{lucky_color}</code>,
                        <code class="text-purple-600 dark:text-purple-400">{image_style}</code>
                    </div>
                </div>

                {{-- Post Header/Footer --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Header โพส
                        </label>
                        <textarea name="post_header_template" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 text-sm"
                                  placeholder="🔮✨ ดวงรายวัน {target_date} ✨🔮">{{ old('post_header_template', $campaign->post_header_template) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Footer โพส
                        </label>
                        <textarea name="post_footer_template" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 text-sm"
                                  placeholder="#ดวงรายวัน #ดูดวง">{{ old('post_footer_template', $campaign->post_footer_template) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Submit Buttons --}}
            {{-- ============================================================ --}}
            <div class="flex flex-col sm:flex-row justify-end gap-4">
                <a href="{{ route('admin.fortune.horoscope.index') }}"
                   class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition text-center">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-8 py-3 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-lg transition shadow-lg font-semibold">
                    {{ $isEdit ? '💾 บันทึกการแก้ไข' : '✨ สร้างแคมเปญ' }}
                </button>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
function horoscopeCampaignForm() {
    return {
        postToFacebook: {{ old('post_to_facebook', $campaign->post_to_facebook) ? 'true' : 'false' }},
        postToLine: {{ old('post_to_line', $campaign->post_to_line) ? 'true' : 'false' }},
        useFortuneTokens: {{ old('use_fortune_settings_tokens', $campaign->use_fortune_settings_tokens ?? true) ? 'true' : 'false' }},
    };
}
</script>
@endpush
@endsection

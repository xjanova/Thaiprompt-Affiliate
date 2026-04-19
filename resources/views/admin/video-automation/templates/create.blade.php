@extends('layouts.admin-v3')

@section('title', 'สร้าง Template - Video Automation')

@section('styles')
<style>
    /* การ์ด Glassmorphism */
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ปุ่ม Gradient */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.3s ease;
    }
    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    /* Section Headers */
    .section-header {
        position: relative;
        padding-left: 1rem;
    }
    .section-header::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        border-radius: 2px;
    }
    .section-header.music::before { background: linear-gradient(180deg, #9333ea 0%, #a855f7 100%); }
    .section-header.image::before { background: linear-gradient(180deg, #06b6d4 0%, #22d3ee 100%); }
    .section-header.video::before { background: linear-gradient(180deg, #10b981 0%, #34d399 100%); }
    .section-header.publish::before { background: linear-gradient(180deg, #f59e0b 0%, #fbbf24 100%); }

    /* Input Focus Effect */
    .input-modern:focus {
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }

    /* Tab Navigation */
    .tab-btn {
        transition: all 0.3s ease;
    }
    .tab-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    /* Image Style Preview */
    .style-option {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .style-option:hover {
        transform: scale(1.02);
    }
    .style-option.selected {
        ring: 3px solid;
        ring-color: #667eea;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8" x-data="templateForm()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.templates.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    สร้าง Template ใหม่
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                กำหนดค่าเริ่มต้นสำหรับการสร้างวิดีโออัตโนมัติ
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.video-automation.templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

        {{-- Basic Info --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                ข้อมูลพื้นฐาน
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Name --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อ Template <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                           placeholder="เช่น เพลงป๊อปยุค 80s พร้อมภาพ Retro">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description"
                              rows="3"
                              class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none"
                              placeholder="อธิบายเกี่ยวกับ template นี้...">{{ old('description') }}</textarea>
                </div>

                {{-- Is Default --}}
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="is_default"
                               value="1"
                               {{ old('is_default') ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ตั้งเป็นค่าเริ่มต้น</span>
                </div>

                {{-- Is Active --}}
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               checked
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                </div>
            </div>
        </div>

        {{-- Music Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="section-header music mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                    </span>
                    ตั้งค่าเพลง (Suno AI)
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                    กำหนดแนวเพลงและอารมณ์สำหรับการสร้างเพลงอัตโนมัติ
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Music Genre --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        แนวเพลง <span class="text-red-500">*</span>
                    </label>
                    <select name="music_genre"
                            required
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="">เลือกแนวเพลง</option>
                        @foreach(\App\Models\VideoAutoTemplate::GENRES as $genre => $label)
                            <option value="{{ $genre }}" {{ old('music_genre') == $genre ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Music Mood --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        อารมณ์เพลง
                    </label>
                    <select name="music_mood"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="">เลือกอารมณ์</option>
                        @foreach(\App\Models\VideoAutoTemplate::MOODS as $mood => $label)
                            <option value="{{ $mood }}" {{ old('music_mood') == $mood ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Music Duration --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความยาวเพลง (วินาที)
                    </label>
                    <input type="number"
                           name="music_settings[duration]"
                           value="{{ old('music_settings.duration', 120) }}"
                           min="30"
                           max="300"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                </div>

                {{-- Music Prompt --}}
                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Prompt สำหรับสร้างเพลง
                    </label>
                    <textarea name="music_prompt"
                              rows="3"
                              class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none"
                              placeholder="อธิบายลักษณะเพลงที่ต้องการ เช่น 'เพลงป๊อปจังหวะสนุก มีเสียงกีตาร์ไฟฟ้า และกลองหนักแน่น'">{{ old('music_prompt') }}</textarea>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        ใช้ {genre}, {mood} เพื่อแทนที่ด้วยค่าที่เลือก
                    </p>
                </div>

                {{-- BPM Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        BPM ต่ำสุด
                    </label>
                    <input type="number"
                           name="music_settings[bpm_min]"
                           value="{{ old('music_settings.bpm_min', 80) }}"
                           min="40"
                           max="200"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        BPM สูงสุด
                    </label>
                    <input type="number"
                           name="music_settings[bpm_max]"
                           value="{{ old('music_settings.bpm_max', 140) }}"
                           min="40"
                           max="200"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                </div>

                {{-- Instrumental Only --}}
                <div class="flex items-center gap-3 self-end pb-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="music_settings[instrumental]"
                               value="1"
                               {{ old('music_settings.instrumental') ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ไม่มีเสียงร้อง (Instrumental)</span>
                </div>
            </div>
        </div>

        {{-- Image Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="section-header image mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    ตั้งค่าภาพ (Freepik AI)
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                    กำหนดสไตล์และจำนวนภาพสำหรับสร้างวิดีโอ
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Image Style --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สไตล์ภาพ <span class="text-red-500">*</span>
                    </label>
                    <select name="image_style"
                            required
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                        <option value="">เลือกสไตล์</option>
                        @foreach(\App\Models\VideoAutoTemplate::IMAGE_STYLES as $style => $label)
                            <option value="{{ $style }}" {{ old('image_style') == $style ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Image Count --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนภาพ
                    </label>
                    <input type="number"
                           name="image_settings[count]"
                           value="{{ old('image_settings.count', 10) }}"
                           min="5"
                           max="50"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">แนะนำ: ความยาวเพลง ÷ 5</p>
                </div>

                {{-- Image Resolution --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความละเอียดภาพ
                    </label>
                    <select name="image_settings[resolution]"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                        <option value="1920x1080" {{ old('image_settings.resolution', '1920x1080') == '1920x1080' ? 'selected' : '' }}>1920x1080 (Full HD)</option>
                        <option value="1280x720" {{ old('image_settings.resolution') == '1280x720' ? 'selected' : '' }}>1280x720 (HD)</option>
                        <option value="3840x2160" {{ old('image_settings.resolution') == '3840x2160' ? 'selected' : '' }}>3840x2160 (4K)</option>
                    </select>
                </div>

                {{-- Image Prompt --}}
                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Prompt สำหรับสร้างภาพ
                    </label>
                    <textarea name="image_prompt"
                              rows="3"
                              class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition resize-none"
                              placeholder="อธิบายลักษณะภาพที่ต้องการ เช่น 'วิวทะเลยามพระอาทิตย์ตก มีเมฆสีส้มและม่วง คลื่นซัดฝั่ง'">{{ old('image_prompt') }}</textarea>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        ใช้ {style}, {music_mood} เพื่อแทนที่ด้วยค่าที่เลือก
                    </p>
                </div>

                {{-- Additional Image Settings --}}
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="image_settings[consistent_style]"
                               value="1"
                               checked
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-300 dark:peer-focus:ring-cyan-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ภาพทั้งหมดใช้สไตล์เดียวกัน</span>
                </div>
            </div>
        </div>

        {{-- Video Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="section-header video mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    ตั้งค่าวิดีโอ
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                    กำหนดรูปแบบการรวมภาพเป็นวิดีโอ
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Video Resolution --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความละเอียดวิดีโอ
                    </label>
                    <select name="video_settings[resolution]"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        <option value="1080p" {{ old('video_settings.resolution', '1080p') == '1080p' ? 'selected' : '' }}>1080p (Full HD)</option>
                        <option value="720p" {{ old('video_settings.resolution') == '720p' ? 'selected' : '' }}>720p (HD)</option>
                        <option value="4k" {{ old('video_settings.resolution') == '4k' ? 'selected' : '' }}>4K (Ultra HD)</option>
                    </select>
                </div>

                {{-- FPS --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Frame Rate
                    </label>
                    <select name="video_settings[fps]"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        <option value="30" {{ old('video_settings.fps', '30') == '30' ? 'selected' : '' }}>30 FPS</option>
                        <option value="24" {{ old('video_settings.fps') == '24' ? 'selected' : '' }}>24 FPS (Cinematic)</option>
                        <option value="60" {{ old('video_settings.fps') == '60' ? 'selected' : '' }}>60 FPS (Smooth)</option>
                    </select>
                </div>

                {{-- Transition Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปแบบ Transition
                    </label>
                    <select name="video_settings[transition]"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        @foreach(\App\Models\VideoAutoTemplate::TRANSITIONS as $transition => $label)
                            <option value="{{ $transition }}" {{ old('video_settings.transition', 'crossfade') == $transition ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Transition Duration --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ระยะเวลา Transition (วินาที)
                    </label>
                    <input type="number"
                           name="video_settings[transition_duration]"
                           value="{{ old('video_settings.transition_duration', 1) }}"
                           min="0.5"
                           max="5"
                           step="0.5"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                </div>

                {{-- Image Duration --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ระยะเวลาแต่ละภาพ (วินาที)
                    </label>
                    <input type="number"
                           name="video_settings[image_duration]"
                           value="{{ old('video_settings.image_duration', 5) }}"
                           min="2"
                           max="30"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">หรือปล่อยให้คำนวณอัตโนมัติจากความยาวเพลง</p>
                </div>

                {{-- Ken Burns Effect --}}
                <div class="flex items-center gap-3 self-end pb-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="video_settings[ken_burns]"
                               value="1"
                               checked
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้ Ken Burns Effect</span>
                </div>
            </div>
        </div>

        {{-- Publish Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="section-header publish mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </span>
                    ตั้งค่าการเผยแพร่
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                    กำหนดค่าเริ่มต้นสำหรับการโพสต์ลง Social Media
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- YouTube Category --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        หมวดหมู่ YouTube
                    </label>
                    <select name="publish_settings[youtube_category]"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        <option value="10" {{ old('publish_settings.youtube_category', '10') == '10' ? 'selected' : '' }}>Music</option>
                        <option value="22" {{ old('publish_settings.youtube_category') == '22' ? 'selected' : '' }}>People & Blogs</option>
                        <option value="24" {{ old('publish_settings.youtube_category') == '24' ? 'selected' : '' }}>Entertainment</option>
                        <option value="1" {{ old('publish_settings.youtube_category') == '1' ? 'selected' : '' }}>Film & Animation</option>
                    </select>
                </div>

                {{-- Default Privacy --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความเป็นส่วนตัวเริ่มต้น
                    </label>
                    <select name="publish_settings[privacy]"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        <option value="public" {{ old('publish_settings.privacy', 'public') == 'public' ? 'selected' : '' }}>สาธารณะ (Public)</option>
                        <option value="unlisted" {{ old('publish_settings.privacy') == 'unlisted' ? 'selected' : '' }}>ไม่แสดง (Unlisted)</option>
                        <option value="private" {{ old('publish_settings.privacy') == 'private' ? 'selected' : '' }}>ส่วนตัว (Private)</option>
                    </select>
                </div>

                {{-- Title Template --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปแบบชื่อวิดีโอ
                    </label>
                    <input type="text"
                           name="publish_settings[title_template]"
                           value="{{ old('publish_settings.title_template', '{genre} Music - {date}') }}"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                           placeholder="{genre} Music - {date}">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        ตัวแปรที่ใช้ได้: {genre}, {mood}, {style}, {date}, {time}
                    </p>
                </div>

                {{-- Description Template --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปแบบคำอธิบาย
                    </label>
                    <textarea name="publish_settings[description_template]"
                              rows="4"
                              class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition resize-none"
                              placeholder="เพลง {genre} สร้างด้วย AI...">{{ old('publish_settings.description_template') }}</textarea>
                </div>

                {{-- Default Tags --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        แท็กเริ่มต้น
                    </label>
                    <input type="text"
                           name="publish_settings[default_tags]"
                           value="{{ old('publish_settings.default_tags', 'AI Music, Suno AI, Music Video') }}"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                           placeholder="tag1, tag2, tag3">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        คั่นด้วยเครื่องหมายจุลภาค (,)
                    </p>
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.video-automation.templates.index') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="btn-gradient-primary px-8 py-3 text-white rounded-xl font-semibold">
                สร้าง Template
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function templateForm() {
    return {
        // ใช้สำหรับ interactive features ในอนาคต
    }
}
</script>
@endsection

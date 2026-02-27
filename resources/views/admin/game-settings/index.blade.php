@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าเกม')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                ⚙️ ตั้งค่าเกม
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                จัดการการตั้งค่าเซิฟเวอร์เกม IP, Port, เพลง และคอนฟิกต่างๆ
            </p>
        </div>

        {{-- ปุ่ม Seed Music (ถ้ายังไม่มีกลุ่ม music) --}}
        @if(!isset($settings['snake_io_music']))
            <form action="{{ route('admin.games.game-settings.seed-music') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white font-semibold rounded-lg shadow-lg transform hover:scale-105 transition duration-200 flex items-center gap-2">
                    <span class="text-xl">🎵</span>
                    สร้าง Music Settings
                </button>
            </form>
        @endif
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-6 py-4 rounded-lg flex items-center">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg flex items-center">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Settings Form --}}
    <form action="{{ route('admin.games.game-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        @php
            // จัดกลุ่มและกำหนด icon/สี
            $groupConfig = [
                'snake_io_server' => ['icon' => '🌐', 'name' => 'Server Configuration', 'color' => 'from-blue-600 to-cyan-600'],
                'snake_io_world' => ['icon' => '🌍', 'name' => 'Game World Settings', 'color' => 'from-green-600 to-emerald-600'],
                'snake_io_movement' => ['icon' => '🏃', 'name' => 'Movement Settings', 'color' => 'from-orange-600 to-amber-600'],
                'snake_io_camera' => ['icon' => '📷', 'name' => 'Camera Settings', 'color' => 'from-purple-600 to-violet-600'],
                'snake_io_scoring' => ['icon' => '🏆', 'name' => 'Scoring System', 'color' => 'from-yellow-600 to-orange-600'],
                'snake_io_food' => ['icon' => '🍎', 'name' => 'Food Settings', 'color' => 'from-red-600 to-pink-600'],
                'snake_io_bots' => ['icon' => '🤖', 'name' => 'Bot Settings', 'color' => 'from-gray-600 to-slate-600'],
                'snake_io_powerups' => ['icon' => '✨', 'name' => 'Powerups - General', 'color' => 'from-indigo-600 to-purple-600'],
                'snake_io_powerup_magnet' => ['icon' => '🧲', 'name' => 'Powerup: Magnet', 'color' => 'from-pink-600 to-fuchsia-600'],
                'snake_io_powerup_speed' => ['icon' => '⚡', 'name' => 'Powerup: Speed Boost', 'color' => 'from-yellow-500 to-amber-500'],
                'snake_io_powerup_multiplier' => ['icon' => '✖️', 'name' => 'Powerup: Score Multiplier', 'color' => 'from-green-500 to-emerald-500'],
                'snake_io_powerup_zoom' => ['icon' => '🔍', 'name' => 'Powerup: Zoom Out', 'color' => 'from-cyan-500 to-blue-500'],
                'snake_io_music' => ['icon' => '🎵', 'name' => 'Music & Sound Effects', 'color' => 'from-rose-500 to-pink-600'],
                'general' => ['icon' => '⚙️', 'name' => 'General Game Settings', 'color' => 'from-gray-700 to-zinc-700'],
            ];
        @endphp

        @foreach($settings as $group => $groupSettings)
            @php
                $config = $groupConfig[$group] ?? ['icon' => '⚙️', 'name' => ucfirst($group), 'color' => 'from-indigo-600 to-purple-600'];
                // กลุ่ม music เปิดอัตโนมัติ
                $defaultOpen = $loop->first || $group === 'snake_io_music';
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-6" x-data="{ open: {{ $defaultOpen ? 'true' : 'false' }} }">
                {{-- Group Header (Collapsible) --}}
                <button
                    type="button"
                    @click="open = !open"
                    class="w-full bg-gradient-to-r {{ $config['color'] }} px-6 py-4 flex justify-between items-center hover:opacity-90 transition"
                >
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="text-2xl">{{ $config['icon'] }}</span>
                        <span>{{ $config['name'] }}</span>
                        <span class="text-sm font-normal opacity-75">({{ count($groupSettings) }} การตั้งค่า)</span>
                    </h2>
                    <svg
                        class="w-6 h-6 text-white transform transition-transform"
                        :class="{ 'rotate-180': open }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                {{-- Settings Fields --}}
                <div x-show="open" x-collapse class="p-6 space-y-6">
                    @foreach($groupSettings as $setting)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0 last:pb-0">
                            <label for="{{ $setting->key }}" class="block mb-3">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $setting->description ?? $setting->key }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2 font-mono">
                                    ({{ $setting->key }})
                                </span>
                            </label>

                            @if($setting->type === 'boolean')
                                {{-- Boolean Toggle --}}
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="{{ $setting->key }}"
                                        value="true"
                                        {{ $setting->value === 'true' ? 'checked' : '' }}
                                        class="sr-only peer"
                                    >
                                    <div class="w-14 h-7 bg-gray-300 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $setting->value === 'true' ? 'เปิด' : 'ปิด' }}
                                    </span>
                                </label>
                                <input type="hidden" name="{{ $setting->key }}" value="false">

                            @elseif($setting->type === 'json')
                                {{-- JSON Music Track Manager --}}
                                <div x-data="audioTrackManager('{{ $setting->key }}', {{ $setting->value ?? '[]' }})" class="space-y-4">
                                    {{-- รายการ tracks ปัจจุบัน --}}
                                    <div class="space-y-2">
                                        <template x-for="(track, index) in tracks" :key="index">
                                            <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 group">
                                                {{-- ไอคอนเพลง --}}
                                                <div class="flex-shrink-0 w-10 h-10 bg-rose-100 dark:bg-rose-900/30 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                                    </svg>
                                                </div>

                                                {{-- ชื่อเพลง --}}
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="track.name"></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="track.url"></p>
                                                </div>

                                                {{-- ปุ่มเล่นทดสอบ --}}
                                                <button type="button"
                                                        @click="playPreview(track.url)"
                                                        class="flex-shrink-0 p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                                                        title="ทดสอบเล่น">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </button>

                                                {{-- ปุ่มลบ --}}
                                                <button type="button"
                                                        @click="deleteTrack(index)"
                                                        class="flex-shrink-0 p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition opacity-0 group-hover:opacity-100"
                                                        title="ลบเพลงนี้">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>

                                        {{-- ไม่มี track --}}
                                        <div x-show="tracks.length === 0" class="text-center py-6 text-gray-400 dark:text-gray-500">
                                            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                            </svg>
                                            <p class="text-sm">ยังไม่มีเพลง กดปุ่มด้านล่างเพื่ออัพโหลด</p>
                                        </div>
                                    </div>

                                    {{-- ฟอร์มอัพโหลดเพลงใหม่ --}}
                                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4"
                                         :class="{ 'border-rose-400 bg-rose-50 dark:bg-rose-900/10': uploading }">
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            {{-- ชื่อเพลง --}}
                                            <input
                                                type="text"
                                                x-model="newTrackName"
                                                placeholder="ชื่อเพลง (เช่น Neon Dreams)"
                                                class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                                       focus:ring-2 focus:ring-rose-500 focus:border-transparent
                                                       text-sm transition"
                                            >

                                            {{-- เลือกไฟล์ --}}
                                            <label class="flex-shrink-0 cursor-pointer">
                                                <input
                                                    type="file"
                                                    accept=".mp3,.wav,.ogg,.m4a"
                                                    @change="selectFile($event)"
                                                    class="hidden"
                                                >
                                                <span class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                    <span x-text="selectedFileName || 'เลือกไฟล์เสียง'"></span>
                                                </span>
                                            </label>

                                            {{-- ปุ่มอัพโหลด --}}
                                            <button type="button"
                                                    @click="uploadTrack()"
                                                    :disabled="!selectedFile || !newTrackName || uploading"
                                                    class="flex-shrink-0 px-6 py-2.5 bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-lg font-medium text-sm
                                                           hover:from-rose-600 hover:to-pink-700 disabled:opacity-50 disabled:cursor-not-allowed
                                                           transition flex items-center gap-2">
                                                <template x-if="!uploading">
                                                    <span class="flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                        </svg>
                                                        อัพโหลด
                                                    </span>
                                                </template>
                                                <template x-if="uploading">
                                                    <span class="flex items-center gap-2">
                                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        กำลังอัพโหลด...
                                                    </span>
                                                </template>
                                            </button>
                                        </div>

                                        {{-- ข้อความสถานะ --}}
                                        <template x-if="uploadMessage">
                                            <p class="mt-2 text-sm" :class="uploadSuccess ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="uploadMessage"></p>
                                        </template>

                                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                            รองรับ: MP3, WAV, OGG, M4A (สูงสุด 10MB)
                                        </p>
                                    </div>

                                    {{-- Hidden input สำหรับ form submit (เก็บ JSON tracks) --}}
                                    <input type="hidden" :name="settingKey" :value="JSON.stringify(tracks)">

                                    {{-- Audio preview player (hidden) --}}
                                    <audio x-ref="audioPreview" class="hidden"></audio>
                                </div>

                            @elseif($setting->type === 'integer' || $setting->type === 'float')
                                {{-- Number Input (Integer or Float) --}}
                                <input
                                    type="number"
                                    id="{{ $setting->key }}"
                                    name="{{ $setting->key }}"
                                    value="{{ $setting->value }}"
                                    step="{{ $setting->type === 'float' ? '0.01' : '1' }}"
                                    min="0"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                                           bg-white dark:bg-gray-700
                                           text-gray-900 dark:text-gray-100
                                           focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                           transition duration-200 font-mono"
                                    placeholder="{{ $setting->type === 'float' ? 'กรอกทศนิยม (เช่น 0.15)' : 'กรอกตัวเลข' }}"
                                >

                            @else
                                {{-- String/Text Input --}}
                                <input
                                    type="text"
                                    id="{{ $setting->key }}"
                                    name="{{ $setting->key }}"
                                    value="{{ $setting->value }}"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                                           bg-white dark:bg-gray-700
                                           text-gray-900 dark:text-gray-100
                                           focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                           transition duration-200 font-mono"
                                    placeholder="กรอกข้อมูล"
                                >
                            @endif

                            {{-- Hints สำหรับค่าพิเศษ --}}
                            @if(str_contains($setting->key, 'lifetime') || str_contains($setting->key, 'duration'))
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                    💡 หน่วยเป็นมิลลิวินาที (1000 = 1 วินาที, 0 = ไม่จำกัด/ไม่หาย)
                                </p>
                            @elseif(str_contains($setting->key, 'spawn_rate') || str_contains($setting->key, 'spawn_chance'))
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                    💡 ค่าระหว่าง 0-1 (0.25 = 25%, 1 = 100%)
                                </p>
                            @elseif(str_contains($setting->key, 'multiplier') && $setting->type !== 'boolean')
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                    💡 ตัวคูณ (1 = ปกติ, 2 = 2เท่า, 3 = 3เท่า)
                                </p>
                            @elseif($setting->key === 'snake_io_server_ip')
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                    💡 ตัวอย่าง: 123.253.62.251 หรือ localhost
                                </p>
                            @elseif(str_contains($setting->key, 'port'))
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                    💡 ช่วง Port ที่ใช้ได้: 1-65535 (แนะนำ: 8080, 6001, 3000)
                                </p>
                            @elseif(str_contains($setting->key, 'speed'))
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                    💡 ความเร็ว (หน่วยต่อเฟรม, แนะนำ: 0.1-0.5)
                                </p>
                            @elseif(str_contains($setting->key, 'volume'))
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                    💡 ระดับเสียง 0.0 (เงียบ) ถึง 1.0 (ดังสุด)
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Submit Button --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.dashboard') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg transform hover:scale-105 transition duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    {{-- Info Boxes --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
        {{-- Info Box 1: General Info --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">
                        ℹ️ ข้อมูลทั่วไป
                    </h3>
                    <div class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
                        <p>• การเปลี่ยนแปลงจะมีผลทันทีหลังจากบันทึก</p>
                        <p>• ระบบใช้ Cache 1 ชั่วโมง เพื่อประสิทธิภาพ</p>
                        <p>• ทุกคนที่เล่นจะโหลดค่าเหล่านี้อัตโนมัติ</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Box 2: Music Info --}}
        <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-semibold text-rose-800 dark:text-rose-300 mb-2">
                        🎵 เกี่ยวกับเพลง & เสียง
                    </h3>
                    <div class="text-sm text-rose-700 dark:text-rose-400 space-y-1">
                        <p>• <strong>Title Tracks:</strong> เพลงที่เล่นในหน้า Title Screen</p>
                        <p>• <strong>Gameplay Tracks:</strong> เพลงที่เล่นขณะเล่นเกม</p>
                        <p>• รองรับไฟล์ MP3, WAV, OGG, M4A (สูงสุด 10MB)</p>
                        <p>• สามารถอัพโหลดได้หลายเพลง จะสุ่มเล่น</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Warning Box --}}
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mt-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 mb-2">
                    ⚠️ ข้อควรระวัง
                </h3>
                <div class="text-sm text-yellow-700 dark:text-yellow-400 space-y-1">
                    <p>• <strong>ระยะเวลา (Lifetime/Duration):</strong> หน่วยเป็นมิลลิวินาที (1 วินาที = 1000)</p>
                    <p>• <strong>ความเร็ว (Speed):</strong> ค่าสูงเกินไปจะทำให้เกมเล่นยาก</p>
                    <p>• <strong>อัตราการเกิด (Spawn Rate):</strong> ค่าสูงเกินไป (> 0.5) อาจทำให้เกมแลก</p>
                    <p>• <strong>การเปลี่ยน Server IP:</strong> ต้องแน่ใจว่าเซิฟเวอร์เปิดอยู่จริง</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alpine.js Component สำหรับจัดการ Audio Tracks --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('audioTrackManager', (settingKey, initialTracks) => ({
        settingKey: settingKey,
        tracks: Array.isArray(initialTracks) ? initialTracks : [],
        newTrackName: '',
        selectedFile: null,
        selectedFileName: '',
        uploading: false,
        uploadMessage: '',
        uploadSuccess: false,
        previewAudio: null,

        /**
         * เลือกไฟล์เสียง
         */
        selectFile(event) {
            const file = event.target.files[0];
            if (file) {
                this.selectedFile = file;
                this.selectedFileName = file.name;
                // ตั้งชื่อเพลงอัตโนมัติจากชื่อไฟล์ (ถ้ายังไม่ได้กรอก)
                if (!this.newTrackName) {
                    this.newTrackName = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
                }
            }
        },

        /**
         * อัพโหลด track ใหม่
         */
        async uploadTrack() {
            if (!this.selectedFile || !this.newTrackName) return;

            this.uploading = true;
            this.uploadMessage = '';

            try {
                const formData = new FormData();
                formData.append('audio_file', this.selectedFile);
                formData.append('setting_key', this.settingKey);
                formData.append('track_name', this.newTrackName);

                const response = await fetch('{{ route("admin.games.game-settings.upload-audio") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    this.tracks = data.tracks;
                    this.newTrackName = '';
                    this.selectedFile = null;
                    this.selectedFileName = '';
                    this.uploadMessage = '✅ ' + data.message;
                    this.uploadSuccess = true;
                } else {
                    this.uploadMessage = '❌ ' + data.message;
                    this.uploadSuccess = false;
                }
            } catch (error) {
                this.uploadMessage = '❌ เกิดข้อผิดพลาด: ' + error.message;
                this.uploadSuccess = false;
            } finally {
                this.uploading = false;
                // ลบข้อความหลัง 5 วินาที
                setTimeout(() => { this.uploadMessage = ''; }, 5000);
            }
        },

        /**
         * ลบ track
         */
        async deleteTrack(index) {
            if (!confirm('ต้องการลบเพลง "' + this.tracks[index].name + '" หรือไม่?')) return;

            try {
                const response = await fetch('{{ route("admin.games.game-settings.delete-audio") }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        setting_key: this.settingKey,
                        track_index: index,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.tracks = data.tracks;
                    this.uploadMessage = '✅ ' + data.message;
                    this.uploadSuccess = true;
                } else {
                    this.uploadMessage = '❌ ' + data.message;
                    this.uploadSuccess = false;
                }
            } catch (error) {
                this.uploadMessage = '❌ เกิดข้อผิดพลาด: ' + error.message;
                this.uploadSuccess = false;
            }

            // ลบข้อความหลัง 5 วินาที
            setTimeout(() => { this.uploadMessage = ''; }, 5000);
        },

        /**
         * เล่นทดสอบเสียง
         */
        playPreview(url) {
            // หยุดเพลงเก่า
            if (this.previewAudio) {
                this.previewAudio.pause();
                this.previewAudio = null;
            }

            this.previewAudio = new Audio(url);
            this.previewAudio.volume = 0.5;
            this.previewAudio.play().catch(err => {
                console.warn('ไม่สามารถเล่นเสียงได้:', err);
                alert('ไม่สามารถเล่นไฟล์เสียงนี้ได้ อาจยังไม่ได้อัพโหลดไฟล์จริง');
            });

            // หยุดอัตโนมัติหลัง 10 วินาที
            setTimeout(() => {
                if (this.previewAudio) {
                    this.previewAudio.pause();
                    this.previewAudio = null;
                }
            }, 10000);
        },
    }));
});
</script>
@endsection

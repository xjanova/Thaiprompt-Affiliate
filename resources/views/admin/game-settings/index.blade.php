@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าเกม')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            ⚙️ ตั้งค่าเกม
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            จัดการการตั้งค่าเซิฟเวอร์เกม IP, Port และคอนฟิกต่างๆ
        </p>
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
                'general' => ['icon' => '⚙️', 'name' => 'General Game Settings', 'color' => 'from-gray-700 to-zinc-700'],
            ];
        @endphp

        @foreach($settings as $group => $groupSettings)
            @php
                $config = $groupConfig[$group] ?? ['icon' => '⚙️', 'name' => ucfirst($group), 'color' => 'from-indigo-600 to-purple-600'];
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-6" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
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
                        <p>• สามารถตั้งค่าได้ทั้ง 58 ค่า แบ่งเป็น 13 หมวดหมู่</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Box 2: Best Practices --}}
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-2">
                        ✅ คำแนะนำ
                    </h3>
                    <div class="text-sm text-green-700 dark:text-green-400 space-y-1">
                        <p>• <strong>Server IP:</strong> ใช้ IP จริงสำหรับ Production</p>
                        <p>• <strong>Powerup Lifetime:</strong> ตั้งเป็น 0 = ไม่หายไป</p>
                        <p>• <strong>Spawn Rate:</strong> ค่าต่ำ = เกิดน้อย, สูง = เกิดบ่อย</p>
                        <p>• <strong>Bot Count:</strong> แนะนำไม่เกิน 50 เพื่อประสิทธิภาพ</p>
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
@endsection

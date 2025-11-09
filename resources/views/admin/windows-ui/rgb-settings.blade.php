@extends('layouts.admin')

@section('title', 'ตั้งค่า RGB Animation')

@push('styles')
<style>
    .color-picker-wrapper {
        position: relative;
    }
    .rgb-preview {
        background: linear-gradient(90deg, #FF0080, #00F0FF, #7F00FF, #FF3D00, #00FF80);
        background-size: 200% 200%;
        animation: rgb-gradient 3s ease infinite;
    }
    @keyframes rgb-gradient {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    .glow-effect {
        box-shadow: 0 0 20px rgba(255, 0, 128, 0.6), 0 0 40px rgba(0, 240, 255, 0.4);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="rgbSettingsManager()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-pink-600 to-red-600 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-3xl">
                        🌈
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">ตั้งค่า RGB Animation</h1>
                        <p class="text-white/90">จัดการ RGB Effects, สี, ความเร็ว และ Glow Effects ทั้งหมดในที่เดียว</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.windows-ui.index') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl transition-all">
                ← กลับ
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.windows-ui.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: RGB Settings -->
            <div class="lg:col-span-2 space-y-6">
                <!-- General RGB Animation -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span>🌈</span> General RGB Animation
                    </h2>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">เปิดใช้ RGB Animation</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">แสดง RGB Effects บนอินเตอร์เฟซทั่วไป</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="windows_rgb_enabled" value="1" x-model="settings.windows_rgb_enabled" class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความเร็ว Animation (วินาที)</label>
                            <div class="flex items-center gap-4">
                                <input type="range" name="windows_rgb_speed" min="1" max="10" x-model="settings.windows_rgb_speed" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400 min-w-[3rem] text-center" x-text="settings.windows_rgb_speed"></span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ช้า ←→ เร็ว</p>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Glow Effect</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">เพิ่มแสงเรืองรอบ RGB</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="windows_rgb_glow" value="1" x-model="settings.windows_rgb_glow" class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Millennium RGB Border -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span>✨</span> Millennium RGB Border
                    </h2>

                    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>💡 Note:</strong> Millennium RGB Border คือแถบสีสันที่วิ่งตามขอบ Taskbar ซึ่งแตกต่างจาก RGB Animation ทั่วไปของระบบ
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">เปิดใช้ Millennium RGB Border</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">แถบสีวิ่งตาม Taskbar</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="millennium_rgb_enabled" value="1" x-model="settings.millennium_rgb_enabled" class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความเร็ว Border (วินาที)</label>
                            <div class="flex items-center gap-4">
                                <input type="range" name="millennium_rgb_speed" min="1" max="10" x-model="settings.millennium_rgb_speed" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400 min-w-[3rem] text-center" x-text="settings.millennium_rgb_speed"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Color Palette -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span>🎨</span> Color Palette
                    </h2>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <template x-for="(color, index) in colors" :key="index">
                            <div class="relative">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2" x-text="'Color ' + (index + 1)"></label>
                                <div class="relative">
                                    <input type="color" x-model="colors[index]" class="w-full h-16 rounded-lg cursor-pointer border-2 border-gray-300 dark:border-slate-600">
                                    <button type="button" @click="removeColor(index)" x-show="colors.length > 1" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full text-xs flex items-center justify-center">
                                        ×
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addColor()" class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all">
                        <i class="fas fa-plus mr-2"></i>เพิ่มสี
                    </button>

                    <!-- Hidden inputs for colors -->
                    <template x-for="(color, index) in colors" :key="index">
                        <input type="hidden" :name="`rgb_colors[${index}]`" :value="color">
                    </template>

                    <!-- Preset Palettes -->
                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Presets</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="applyPreset('neon')" class="px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg text-sm transition-all">
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-1">
                                        <div class="w-4 h-4 rounded-full" style="background: #FF0080"></div>
                                        <div class="w-4 h-4 rounded-full" style="background: #00F0FF"></div>
                                        <div class="w-4 h-4 rounded-full" style="background: #7F00FF"></div>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">Neon</span>
                                </div>
                            </button>
                            <button type="button" @click="applyPreset('rainbow')" class="px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg text-sm transition-all">
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-1">
                                        <div class="w-4 h-4 rounded-full" style="background: #FF0000"></div>
                                        <div class="w-4 h-4 rounded-full" style="background: #00FF00"></div>
                                        <div class="w-4 h-4 rounded-full" style="background: #0000FF"></div>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">Rainbow</span>
                                </div>
                            </button>
                            <button type="button" @click="applyPreset('sunset')" class="px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg text-sm transition-all">
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-1">
                                        <div class="w-4 h-4 rounded-full" style="background: #FF6B35"></div>
                                        <div class="w-4 h-4 rounded-full" style="background: #F7931E"></div>
                                        <div class="w-4 h-4 rounded-full" style="background: #FDC830"></div>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">Sunset</span>
                                </div>
                            </button>
                            <button type="button" @click="applyPreset('ocean')" class="px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg text-sm transition-all">
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-1">
                                        <div class="w-4 h-4 rounded-full" style="background: #00B4DB"></div>
                                        <div class="w-4 h-4 rounded-full" style="background: #0083B0"></div>
                                        <div class="w-4 h-4 rounded-full" style="background: #005C97"></div>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">Ocean</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Preview & Tips -->
            <div class="space-y-6">
                <!-- Live Preview -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span>👁️</span> Live Preview
                    </h3>

                    <!-- RGB Animation Preview -->
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">General RGB:</p>
                        <div class="h-32 rounded-xl relative overflow-hidden" :class="settings.windows_rgb_enabled ? 'rgb-preview' : 'bg-gray-200 dark:bg-gray-700'" :style="'animation-duration: ' + settings.windows_rgb_speed + 's'" :class="{'glow-effect': settings.windows_rgb_glow && settings.windows_rgb_enabled}">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-white font-bold text-xl" x-show="settings.windows_rgb_enabled">🌈 RGB Active</span>
                                <span class="text-gray-500 dark:text-gray-400" x-show="!settings.windows_rgb_enabled">Disabled</span>
                            </div>
                        </div>
                    </div>

                    <!-- Millennium Border Preview -->
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Millennium Border:</p>
                        <div class="h-16 rounded-xl bg-gradient-to-r from-gray-800 to-gray-900 relative overflow-hidden">
                            <div x-show="settings.millennium_rgb_enabled" class="absolute inset-0 p-1 rounded-xl" :style="'animation: rgb-gradient ' + settings.millennium_rgb_speed + 's ease infinite; background: linear-gradient(90deg, ' + colors.join(', ') + '); background-size: 200% 200%;'">
                                <div class="w-full h-full bg-gray-800 rounded-lg flex items-center justify-center">
                                    <span class="text-white text-sm">Taskbar</span>
                                </div>
                            </div>
                            <div x-show="!settings.millennium_rgb_enabled" class="absolute inset-0 flex items-center justify-center">
                                <span class="text-gray-500 text-sm">Border Disabled</span>
                            </div>
                        </div>
                    </div>

                    <!-- Color Gradient Preview -->
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Color Gradient:</p>
                        <div class="h-12 rounded-lg" :style="'background: linear-gradient(90deg, ' + colors.join(', ') + ')'"></div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl p-6 border border-purple-200 dark:border-purple-800">
                    <h4 class="text-lg font-bold text-purple-900 dark:text-purple-300 mb-4">📊 Current Settings</h4>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-purple-700 dark:text-purple-300">RGB Animation:</span>
                            <span class="font-semibold" :class="settings.windows_rgb_enabled ? 'text-green-600' : 'text-gray-500'" x-text="settings.windows_rgb_enabled ? 'ON' : 'OFF'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-purple-700 dark:text-purple-300">Millennium Border:</span>
                            <span class="font-semibold" :class="settings.millennium_rgb_enabled ? 'text-green-600' : 'text-gray-500'" x-text="settings.millennium_rgb_enabled ? 'ON' : 'OFF'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-purple-700 dark:text-purple-300">Colors:</span>
                            <span class="font-semibold text-purple-900 dark:text-purple-300" x-text="colors.length"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-purple-700 dark:text-purple-300">Glow Effect:</span>
                            <span class="font-semibold" :class="settings.windows_rgb_glow ? 'text-green-600' : 'text-gray-500'" x-text="settings.windows_rgb_glow ? 'ON' : 'OFF'"></span>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
                    <h4 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-3">💡 เคล็ดลับ</h4>
                    <ul class="space-y-2 text-sm text-blue-700 dark:text-blue-300">
                        <li class="flex items-start gap-2">
                            <span>•</span>
                            <span>ใช้ Preset เพื่อเปลี่ยนชุดสีอย่างรวดเร็ว</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span>•</span>
                            <span>Glow Effect ช่วยเพิ่มความโดดเด่น</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span>•</span>
                            <span>ความเร็ว 3-5 วินาทีเหมาะสำหรับส่วนใหญ่</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span>•</span>
                            <span>สีที่ใช้มากเกินไปอาจทำให้ไม่สบายตา</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function rgbSettingsManager() {
    return {
        settings: {
            windows_rgb_enabled: {{ ($rgbSettings['enabled'] ?? true) ? 'true' : 'false' }},
            windows_rgb_speed: {{ $rgbSettings['speed'] ?? 3 }},
            windows_rgb_glow: {{ ($rgbSettings['glow'] ?? true) ? 'true' : 'false' }},
            millennium_rgb_enabled: {{ \App\Models\WindowsUiSetting::get('millennium_rgb_enabled', true) ? 'true' : 'false' }},
            millennium_rgb_speed: {{ \App\Models\WindowsUiSetting::get('millennium_rgb_speed', 5) }},
        },
        colors: @json($rgbSettings['colors'] ?? ['#FF0080', '#00F0FF', '#7F00FF', '#FF3D00']),

        addColor() {
            this.colors.push('#FF00FF');
        },

        removeColor(index) {
            if (this.colors.length > 1) {
                this.colors.splice(index, 1);
            }
        },

        applyPreset(preset) {
            const presets = {
                neon: ['#FF0080', '#00F0FF', '#7F00FF', '#FF3D00'],
                rainbow: ['#FF0000', '#FF7F00', '#FFFF00', '#00FF00', '#0000FF', '#4B0082', '#9400D3'],
                sunset: ['#FF6B35', '#F7931E', '#FDC830', '#F37335'],
                ocean: ['#00B4DB', '#0083B0', '#005C97', '#003D5B']
            };

            if (presets[preset]) {
                this.colors = presets[preset];
            }
        }
    }
}
</script>
@endpush
@endsection

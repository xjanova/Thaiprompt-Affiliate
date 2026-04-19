@extends('layouts.admin-v3')

@section('title', 'ID Card Designer')

@section('content')
{{--
/**
 * Admin ID Card Designer
 *
 * หน้าออกแบบ Virtual ID Card ด้วย:
 * - ตั้งค่าพื้นหลัง (สี, gradient, รูปภาพ)
 * - ลากจัดตำแหน่งองค์ประกอบ (Drag & Drop)
 * - Preview แบบ realtime
 *
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

<div class="min-h-screen bg-gray-100 dark:bg-gray-900" x-data="idCardDesigner()">
    {{-- Header --}}
    <div class="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.id-card.index') }}"
                       class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                            ID Card Designer
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ออกแบบบัตรสำหรับ <span class="font-semibold" x-text="currentRankName"></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Rank Selector --}}
                    <select x-model="selectedRankLevel"
                            @change="changeRank()"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl text-sm font-medium focus:ring-2 focus:ring-purple-500">
                        @foreach($ranks as $rank)
                            <option value="{{ $rank->level }}">{{ $rank->name_th ?? $rank->name }} (Level {{ $rank->level }})</option>
                        @endforeach
                    </select>

                    {{-- Save Button --}}
                    <button @click="saveSettings()"
                            :disabled="saving"
                            class="px-5 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50">
                        <span x-show="!saving">
                            <i class="fas fa-save mr-2"></i>
                            บันทึก
                        </span>
                        <span x-show="saving">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            กำลังบันทึก...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left Panel: Settings --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Background Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-palette text-purple-500"></i>
                        พื้นหลังบัตร
                    </h3>

                    {{-- Background Type --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทพื้นหลัง</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button @click="settings.background_type = 'gradient'"
                                    :class="settings.background_type === 'gradient' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                Gradient
                            </button>
                            <button @click="settings.background_type = 'color'"
                                    :class="settings.background_type === 'color' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                สีเดียว
                            </button>
                            <button @click="settings.background_type = 'image'"
                                    :class="settings.background_type === 'image' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                รูปภาพ
                            </button>
                        </div>
                    </div>

                    {{-- Gradient Settings --}}
                    <div x-show="settings.background_type === 'gradient'" class="space-y-3">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">สีเริ่มต้น (From)</label>
                            <div class="flex gap-2">
                                <input type="color" x-model="settings.background_gradient.from"
                                       class="w-12 h-10 rounded border-0 cursor-pointer">
                                <input type="text" x-model="settings.background_gradient.from"
                                       class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">สีกลาง (Via)</label>
                            <div class="flex gap-2">
                                <input type="color" x-model="settings.background_gradient.via"
                                       class="w-12 h-10 rounded border-0 cursor-pointer">
                                <input type="text" x-model="settings.background_gradient.via"
                                       class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">สีปลาย (To)</label>
                            <div class="flex gap-2">
                                <input type="color" x-model="settings.background_gradient.to"
                                       class="w-12 h-10 rounded border-0 cursor-pointer">
                                <input type="text" x-model="settings.background_gradient.to"
                                       class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">ทิศทาง</label>
                            <select x-model="settings.background_gradient.direction"
                                    class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                                <option value="br">ขวาล่าง ↘</option>
                                <option value="bl">ซ้ายล่าง ↙</option>
                                <option value="tr">ขวาบน ↗</option>
                                <option value="tl">ซ้ายบน ↖</option>
                                <option value="r">ขวา →</option>
                                <option value="l">ซ้าย ←</option>
                                <option value="b">ล่าง ↓</option>
                                <option value="t">บน ↑</option>
                            </select>
                        </div>
                    </div>

                    {{-- Single Color --}}
                    <div x-show="settings.background_type === 'color'" class="space-y-3">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">สีพื้นหลัง</label>
                            <div class="flex gap-2">
                                <input type="color" x-model="settings.background_color"
                                       class="w-12 h-10 rounded border-0 cursor-pointer">
                                <input type="text" x-model="settings.background_color"
                                       class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Image Background --}}
                    <div x-show="settings.background_type === 'image'" class="space-y-3">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">อัพโหลดภาพ</label>
                            <input type="file" @change="uploadImage($event)"
                                   accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200">
                        </div>
                        <div x-show="settings.background_image">
                            <img :src="settings.background_image" class="w-full h-24 object-cover rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Overlay ความเข้ม</label>
                            <input type="range" x-model="settings.overlay_opacity" min="0" max="80"
                                   class="w-full">
                            <span class="text-xs text-gray-500" x-text="settings.overlay_opacity + '%'"></span>
                        </div>
                    </div>
                </div>

                {{-- Element Visibility --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-eye text-blue-500"></i>
                        แสดง/ซ่อนข้อมูล
                    </h3>

                    <div class="space-y-3">
                        <template x-for="(element, key) in elements" :key="key">
                            <label class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="element.label"></span>
                                <div class="relative">
                                    <input type="checkbox" x-model="element.visible"
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Effects --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-magic text-pink-500"></i>
                        เอฟเฟกต์พิเศษ
                    </h3>

                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Shimmer Effect</span>
                            <input type="checkbox" x-model="settings.effects.shimmer"
                                   class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        </label>
                        <label class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Particles</span>
                            <input type="checkbox" x-model="settings.effects.particles"
                                   class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        </label>
                        <label class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Glow Effect</span>
                            <input type="checkbox" x-model="settings.effects.glow"
                                   class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        </label>
                        <label class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Holographic</span>
                            <input type="checkbox" x-model="settings.effects.holographic"
                                   class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        </label>
                    </div>
                </div>
            </div>

            {{-- Center: Preview --}}
            <div class="lg:col-span-2">
                <div class="sticky top-24">
                    {{-- Preview Header --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-desktop text-green-500"></i>
                                Preview แบบ Realtime
                            </h3>
                            <div class="flex items-center gap-2">
                                <button @click="previewScale = 1" :class="previewScale === 1 ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700'" class="px-3 py-1 rounded text-sm">100%</button>
                                <button @click="previewScale = 0.8" :class="previewScale === 0.8 ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700'" class="px-3 py-1 rounded text-sm">80%</button>
                                <button @click="previewScale = 0.6" :class="previewScale === 0.6 ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700'" class="px-3 py-1 rounded text-sm">60%</button>
                            </div>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <i class="fas fa-info-circle mr-1"></i>
                            ลากองค์ประกอบบนบัตรเพื่อจัดตำแหน่ง
                        </p>

                        {{-- ID Card Preview --}}
                        <div class="flex justify-center">
                            <div class="relative transition-transform duration-300"
                                 :style="{ transform: 'scale(' + previewScale + ')' }">

                                {{-- The Card --}}
                                <div id="preview-card"
                                     class="relative w-[450px] h-[280px] rounded-2xl overflow-hidden shadow-2xl"
                                     :class="{ 'animate-glow': settings.effects?.glow }"
                                     :style="getBackgroundStyle()">

                                    {{-- Shimmer Effect --}}
                                    <div x-show="settings.effects?.shimmer"
                                         class="absolute inset-0 pointer-events-none z-30">
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
                                    </div>

                                    {{-- Holographic Effect --}}
                                    <div x-show="settings.effects?.holographic"
                                         class="absolute inset-0 pointer-events-none z-30 opacity-30 animate-holographic"
                                         style="background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent); background-size: 200% 200%;"></div>

                                    {{-- Particles --}}
                                    <div x-show="settings.effects?.particles" class="absolute inset-0 pointer-events-none z-30">
                                        <template x-for="i in 5" :key="i">
                                            <div class="absolute w-1 h-1 bg-white rounded-full animate-sparkle"
                                                 :style="{ top: (Math.random() * 80 + 10) + '%', left: (Math.random() * 80 + 10) + '%', animationDelay: (i * 0.3) + 's' }"></div>
                                        </template>
                                    </div>

                                    {{-- Draggable Elements Container --}}
                                    <div class="absolute inset-0 p-6 z-20" id="elements-container">

                                        {{-- Logo --}}
                                        <div x-show="elements.logo.visible"
                                             class="absolute draggable cursor-move group"
                                             :style="{ left: elements.logo.x + 'px', top: elements.logo.y + 'px' }"
                                             data-element="logo"
                                             @mousedown="startDrag($event, 'logo')">
                                            <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center shadow-lg group-hover:ring-2 group-hover:ring-white/50">
                                                <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 object-contain"
                                                     onerror="this.src='https://ui-avatars.com/api/?name=TP&background=8B5CF6&color=fff&bold=true'">
                                            </div>
                                        </div>

                                        {{-- Rank Badge --}}
                                        <div x-show="elements.rank_badge.visible"
                                             class="absolute draggable cursor-move group text-center"
                                             :style="{ left: elements.rank_badge.x + 'px', top: elements.rank_badge.y + 'px' }"
                                             data-element="rank_badge"
                                             @mousedown="startDrag($event, 'rank_badge')">
                                            <div class="text-4xl mb-1 group-hover:scale-110 transition-transform" x-text="previewData.rank?.badge_icon || '🏆'"></div>
                                            <div class="text-white text-xs font-bold tracking-wider uppercase" x-text="previewData.rank?.name || 'RANK'"></div>
                                        </div>

                                        {{-- Profile Picture --}}
                                        <div x-show="elements.profile.visible"
                                             class="absolute draggable cursor-move group"
                                             :style="{ left: elements.profile.x + 'px', top: elements.profile.y + 'px' }"
                                             data-element="profile"
                                             @mousedown="startDrag($event, 'profile')">
                                            <div class="w-20 h-20 rounded-xl overflow-hidden shadow-xl group-hover:ring-2 group-hover:ring-white/50">
                                                <img :src="previewData.user?.profile_picture_url"
                                                     class="w-full h-full object-cover"
                                                     :alt="previewData.user?.name">
                                            </div>
                                        </div>

                                        {{-- Name --}}
                                        <div x-show="elements.name.visible"
                                             class="absolute draggable cursor-move group"
                                             :style="{ left: elements.name.x + 'px', top: elements.name.y + 'px' }"
                                             data-element="name"
                                             @mousedown="startDrag($event, 'name')">
                                            <div class="text-white font-bold text-xl tracking-wide drop-shadow-lg group-hover:bg-white/10 px-2 rounded"
                                                 x-text="previewData.user?.name || 'ชื่อสมาชิก'"></div>
                                        </div>

                                        {{-- Member Number --}}
                                        <div x-show="elements.member_number.visible"
                                             class="absolute draggable cursor-move group"
                                             :style="{ left: elements.member_number.x + 'px', top: elements.member_number.y + 'px' }"
                                             data-element="member_number"
                                             @mousedown="startDrag($event, 'member_number')">
                                            <div class="text-white/60 text-xs font-mono tracking-widest group-hover:bg-white/10 px-2 rounded"
                                                 x-text="'ID: ' + (previewData.user?.member_number || 'TP000001')"></div>
                                        </div>

                                        {{-- QR Code --}}
                                        <div x-show="elements.qr.visible"
                                             class="absolute draggable cursor-move group"
                                             :style="{ left: elements.qr.x + 'px', top: elements.qr.y + 'px' }"
                                             data-element="qr"
                                             @mousedown="startDrag($event, 'qr')">
                                            <div class="bg-white rounded-lg p-1.5 shadow-lg group-hover:ring-2 group-hover:ring-white/50">
                                                <div class="w-14 h-14 bg-gray-100 flex items-center justify-center">
                                                    <i class="fas fa-qrcode text-gray-400 text-2xl"></i>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Member Since --}}
                                        <div x-show="elements.member_since.visible"
                                             class="absolute draggable cursor-move group text-right"
                                             :style="{ left: elements.member_since.x + 'px', top: elements.member_since.y + 'px' }"
                                             data-element="member_since"
                                             @mousedown="startDrag($event, 'member_since')">
                                            <div class="text-white/60 text-xs group-hover:bg-white/10 px-2 rounded">เป็นสมาชิกตั้งแต่</div>
                                            <div class="text-white/90 text-sm font-semibold" x-text="previewData.user?.created_at || 'Jan 2024'"></div>
                                        </div>

                                        {{-- Stars --}}
                                        <div x-show="elements.stars.visible"
                                             class="absolute draggable cursor-move group"
                                             :style="{ left: elements.stars.x + 'px', top: elements.stars.y + 'px' }"
                                             data-element="stars"
                                             @mousedown="startDrag($event, 'stars')">
                                            <div class="flex text-yellow-400 text-xs group-hover:bg-white/10 px-2 rounded">
                                                <template x-for="i in Math.min(previewData.rank?.stars || 1, 5)" :key="i">
                                                    <i class="fas fa-star"></i>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Shadow --}}
                                <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-[90%] h-8 bg-black/20 blur-xl rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Position Info --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">ตำแหน่งองค์ประกอบ (px)</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                            <template x-for="(element, key) in elements" :key="key">
                                <div x-show="element.visible" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2">
                                    <div class="font-medium text-gray-600 dark:text-gray-400" x-text="element.label"></div>
                                    <div class="text-gray-500">
                                        X: <span x-text="Math.round(element.x)"></span>,
                                        Y: <span x-text="Math.round(element.y)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @keyframes sparkle {
        0%, 100% { opacity: 0; transform: scale(0); }
        50% { opacity: 1; transform: scale(1); }
    }

    @keyframes holographic {
        0%, 100% { opacity: 0.2; background-position: 0% 50%; }
        50% { opacity: 0.4; background-position: 100% 50%; }
    }

    @keyframes glow {
        0%, 100% { box-shadow: 0 0 20px rgba(236, 72, 153, 0.3), 0 0 40px rgba(139, 92, 246, 0.2); }
        50% { box-shadow: 0 0 30px rgba(236, 72, 153, 0.5), 0 0 60px rgba(139, 92, 246, 0.3); }
    }

    .animate-shimmer {
        animation: shimmer 3s infinite;
    }

    .animate-sparkle {
        animation: sparkle 2s ease-in-out infinite;
    }

    .animate-holographic {
        animation: holographic 4s ease-in-out infinite;
    }

    .animate-glow {
        animation: glow 3s ease-in-out infinite;
    }

    .draggable:hover {
        z-index: 50;
    }
</style>
@endsection

@push('scripts')
<script>
function idCardDesigner() {
    return {
        selectedRankLevel: {{ $rankLevel }},
        currentRankName: '{{ $currentRank?->name_th ?? $currentRank?->name ?? "Default" }}',
        saving: false,
        previewScale: 1,
        dragging: null,
        dragOffset: { x: 0, y: 0 },

        // Preview data
        previewData: {
            user: {
                name: '{{ $sampleUser?->name ?? "ชื่อสมาชิก" }}',
                member_number: '{{ $sampleUser?->member_number ?? "TP000001" }}',
                profile_picture_url: '{{ $sampleUser?->profile_picture_url ?? "https://ui-avatars.com/api/?name=User&background=random" }}',
                created_at: '{{ $sampleUser?->created_at?->format("M Y") ?? "Jan 2024" }}',
            },
            rank: {
                name: '{{ $currentRank?->name ?? "Bronze" }}',
                name_th: '{{ $currentRank?->name_th ?? "สำริด" }}',
                badge_icon: '{{ $currentRank?->badge_icon ?? "🥉" }}',
                stars: {{ $currentRank?->stars ?? 1 }},
                color: '{{ $currentRank?->color ?? "#CD7F32" }}',
            }
        },

        // Settings
        settings: {
            rank_level: {{ $setting->rank_level ?? $rankLevel }},
            name: '{{ $setting->name ?? "ID Card Setting" }}',
            background_type: '{{ $setting->background_type ?? "gradient" }}',
            background_color: '{{ $setting->background_color ?? "#8B5CF6" }}',
            background_gradient: @json($setting->background_gradient ?? ['from' => '#8B5CF6', 'via' => '#EC4899', 'to' => '#3B82F6', 'direction' => 'br']),
            background_image: '{{ $setting->background_image ?? "" }}',
            overlay_opacity: {{ $setting->overlay_opacity ?? 0 }},
            effects: @json($setting->effects ?? ['shimmer' => false, 'particles' => false, 'glow' => false, 'holographic' => false]),
        },

        // Element positions
        elements: {
            logo: { x: {{ $setting->logo_position['x'] ?? 24 }}, y: {{ $setting->logo_position['y'] ?? 24 }}, visible: {{ json_encode($setting->logo_position['visible'] ?? true) }}, label: 'Logo' },
            rank_badge: { x: {{ $setting->rank_badge_position['x'] ?? 380 }}, y: {{ $setting->rank_badge_position['y'] ?? 24 }}, visible: {{ json_encode($setting->rank_badge_position['visible'] ?? true) }}, label: 'Rank Badge' },
            profile: { x: {{ $setting->profile_position['x'] ?? 24 }}, y: {{ $setting->profile_position['y'] ?? 176 }}, visible: {{ json_encode($setting->profile_position['visible'] ?? true) }}, label: 'รูปโปรไฟล์' },
            name: { x: {{ $setting->name_position['x'] ?? 112 }}, y: {{ $setting->name_position['y'] ?? 184 }}, visible: {{ json_encode($setting->name_position['visible'] ?? true) }}, label: 'ชื่อ' },
            member_number: { x: {{ $setting->member_number_position['x'] ?? 112 }}, y: {{ $setting->member_number_position['y'] ?? 216 }}, visible: {{ json_encode($setting->member_number_position['visible'] ?? true) }}, label: 'รหัสสมาชิก' },
            qr: { x: {{ $setting->qr_position['x'] ?? 370 }}, y: {{ $setting->qr_position['y'] ?? 200 }}, visible: {{ json_encode($setting->qr_position['visible'] ?? true) }}, label: 'QR Code' },
            member_since: { x: {{ $setting->member_since_position['x'] ?? 340 }}, y: {{ $setting->member_since_position['y'] ?? 168 }}, visible: {{ json_encode($setting->member_since_position['visible'] ?? true) }}, label: 'วันสมัคร' },
            stars: { x: {{ $setting->stars_position['x'] ?? 112 }}, y: {{ $setting->stars_position['y'] ?? 240 }}, visible: {{ json_encode($setting->stars_position['visible'] ?? true) }}, label: 'ดาว' },
        },

        init() {
            // Setup drag listeners
            document.addEventListener('mousemove', this.onDrag.bind(this));
            document.addEventListener('mouseup', this.stopDrag.bind(this));
        },

        getBackgroundStyle() {
            switch (this.settings.background_type) {
                case 'color':
                    return { backgroundColor: this.settings.background_color };
                case 'image':
                    let style = `background-image: url('${this.settings.background_image}'); background-size: cover; background-position: center;`;
                    if (this.settings.overlay_opacity > 0) {
                        style = `background: linear-gradient(rgba(0,0,0,${this.settings.overlay_opacity/100}), rgba(0,0,0,${this.settings.overlay_opacity/100})), url('${this.settings.background_image}'); background-size: cover; background-position: center;`;
                    }
                    return { cssText: style };
                case 'gradient':
                default:
                    const g = this.settings.background_gradient;
                    const dirMap = {
                        'br': 'to bottom right', 'bl': 'to bottom left',
                        'tr': 'to top right', 'tl': 'to top left',
                        'r': 'to right', 'l': 'to left',
                        'b': 'to bottom', 't': 'to top'
                    };
                    const dir = dirMap[g.direction] || 'to bottom right';
                    const colors = [g.from, g.via, g.to].filter(c => c);
                    return { background: `linear-gradient(${dir}, ${colors.join(', ')})` };
            }
        },

        startDrag(event, elementKey) {
            this.dragging = elementKey;
            const element = this.elements[elementKey];
            this.dragOffset = {
                x: event.clientX - element.x,
                y: event.clientY - element.y
            };
            event.preventDefault();
        },

        onDrag(event) {
            if (!this.dragging) return;

            const container = document.getElementById('preview-card');
            const rect = container.getBoundingClientRect();

            // Calculate new position relative to container
            let newX = (event.clientX - rect.left) / this.previewScale - 24; // Adjust for padding
            let newY = (event.clientY - rect.top) / this.previewScale - 24;

            // Constrain to container bounds
            newX = Math.max(0, Math.min(newX, 400));
            newY = Math.max(0, Math.min(newY, 240));

            this.elements[this.dragging].x = newX;
            this.elements[this.dragging].y = newY;
        },

        stopDrag() {
            this.dragging = null;
        },

        async uploadImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('{{ route("admin.id-card.upload-background") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    this.settings.background_image = data.url;
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error(error);
                alert('เกิดข้อผิดพลาดในการอัพโหลด');
            }
        },

        async saveSettings() {
            this.saving = true;

            const payload = {
                rank_level: this.selectedRankLevel,
                name: `ID Card Level ${this.selectedRankLevel}`,
                name_th: `บัตรประจำตัว Level ${this.selectedRankLevel}`,
                background_type: this.settings.background_type,
                background_color: this.settings.background_color,
                background_gradient: this.settings.background_gradient,
                background_image: this.settings.background_image,
                overlay_opacity: this.settings.overlay_opacity,
                effects: this.settings.effects,
                logo_position: { x: this.elements.logo.x, y: this.elements.logo.y, visible: this.elements.logo.visible },
                profile_position: { x: this.elements.profile.x, y: this.elements.profile.y, visible: this.elements.profile.visible },
                name_position: { x: this.elements.name.x, y: this.elements.name.y, visible: this.elements.name.visible },
                rank_badge_position: { x: this.elements.rank_badge.x, y: this.elements.rank_badge.y, visible: this.elements.rank_badge.visible },
                member_number_position: { x: this.elements.member_number.x, y: this.elements.member_number.y, visible: this.elements.member_number.visible },
                qr_position: { x: this.elements.qr.x, y: this.elements.qr.y, visible: this.elements.qr.visible },
                member_since_position: { x: this.elements.member_since.x, y: this.elements.member_since.y, visible: this.elements.member_since.visible },
                stars_position: { x: this.elements.stars.x, y: this.elements.stars.y, visible: this.elements.stars.visible },
                is_active: true,
            };

            try {
                const response = await fetch('{{ route("admin.id-card.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.success) {
                    alert('บันทึกสำเร็จ!');
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error(error);
                alert('เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        },

        changeRank() {
            window.location.href = '{{ route("admin.id-card.designer") }}?rank_level=' + this.selectedRankLevel;
        }
    }
}
</script>
@endpush

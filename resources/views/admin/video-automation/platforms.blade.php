@extends('layouts.admin')

@section('title', 'จัดการ Platforms - Video Automation')

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

    /* Platform Card */
    .platform-card {
        transition: all 0.3s ease;
    }
    .platform-card:hover {
        transform: translateY(-4px);
    }

    /* Status Indicator */
    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    .status-dot.connected {
        background: #10b981;
    }
    .status-dot.disconnected {
        background: #ef4444;
    }
    .status-dot.expired {
        background: #f59e0b;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8" x-data="platformsPage()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.dashboard') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    จัดการ Platforms
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                เชื่อมต่อและจัดการบัญชี Social Media สำหรับการเผยแพร่วิดีโอ
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.video-automation.documentation') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                คู่มือ
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-green-700 dark:text-green-400">{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-red-700 dark:text-red-400">{{ session('error') }}</span>
        </div>
    </div>
    @endif

    {{-- Connected Platforms --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Platforms ที่เชื่อมต่อแล้ว
        </h2>

        @if($platforms->where('status', 'connected')->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($platforms->where('status', 'connected') as $platform)
            <div class="platform-card glass-card rounded-xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background-color: {{ $platformTypes[$platform->platform_type]['color'] ?? '#6B7280' }}20">
                            <i class="{{ $platformTypes[$platform->platform_type]['icon'] ?? 'fas fa-globe' }} text-2xl"
                               style="color: {{ $platformTypes[$platform->platform_type]['color'] ?? '#6B7280' }}"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ $platform->name }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $platformTypes[$platform->platform_type]['name'] ?? $platform->platform_type }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="status-dot connected"></span>
                        <span class="text-xs text-green-600 dark:text-green-400">เชื่อมต่อแล้ว</span>
                    </div>
                </div>

                @if($platform->channel_name)
                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Channel</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $platform->channel_name }}</p>
                    @if($platform->channel_url)
                    <a href="{{ $platform->channel_url }}" target="_blank"
                       class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                        ดู Channel
                    </a>
                    @endif
                </div>
                @endif

                <div class="grid grid-cols-3 gap-4 mb-4 text-center">
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($platform->total_posts) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">โพสต์ทั้งหมด</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($platform->successful_posts) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">สำเร็จ</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($platform->failed_posts) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ล้มเหลว</p>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">โพสต์อัตโนมัติ</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               class="sr-only peer"
                               :checked="{{ $platform->auto_publish ? 'true' : 'false' }}"
                               @change="toggleAutoPublish({{ $platform->id }}, $event.target.checked)">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="flex gap-2">
                    <button @click="editPlatform({{ $platform->id }})"
                            class="flex-1 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <i class="fas fa-cog mr-1"></i> ตั้งค่า
                    </button>
                    <button @click="disconnectPlatform({{ $platform->id }})"
                            class="px-3 py-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                        <i class="fas fa-unlink"></i>
                    </button>
                </div>

                @if($platform->last_connected_at)
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 text-center">
                    เชื่อมต่อล่าสุด: {{ $platform->last_connected_at->diffForHumans() }}
                </p>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="glass-card rounded-xl p-8 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มี Platform ที่เชื่อมต่อ</h3>
            <p class="text-gray-600 dark:text-gray-400">เชื่อมต่อบัญชี Social Media ด้านล่างเพื่อเริ่มเผยแพร่วิดีโอ</p>
        </div>
        @endif
    </div>

    {{-- Available Platforms --}}
    <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            เชื่อมต่อ Platform ใหม่
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($platformTypes as $type => $info)
            @php
                $existingPlatform = $platforms->where('platform_type', $type)->where('status', 'connected')->first();
            @endphp
            <div class="platform-card glass-card rounded-xl p-6"
                 x-data="{ expanded: false }">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center"
                         style="background-color: {{ $info['color'] }}20">
                        <i class="{{ $info['icon'] }} text-3xl" style="color: {{ $info['color'] }}"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $info['name'] }}
                        </h3>
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            @if($info['supports_video'] ?? false)
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">Video</span>
                            @endif
                            @if($info['supports_shorts'] ?? false)
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">Shorts</span>
                            @endif
                            @if($info['supports_reels'] ?? false)
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">Reels</span>
                            @endif
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    @if(($info['max_video_duration'] ?? 0) >= 3600)
                        รองรับวิดีโอยาวสูงสุด {{ floor($info['max_video_duration'] / 3600) }} ชั่วโมง
                    @else
                        รองรับวิดีโอยาวสูงสุด {{ floor(($info['max_video_duration'] ?? 60) / 60) }} นาที
                    @endif
                </p>

                @if($existingPlatform)
                <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg text-green-700 dark:text-green-400 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    เชื่อมต่อแล้ว
                </div>
                @else
                    @if($type === 'youtube')
                    <a href="{{ route('admin.video-automation.youtube.connect') }}"
                       class="block w-full text-center px-4 py-3 text-white rounded-lg transition"
                       style="background-color: {{ $info['color'] }}; hover:opacity-90">
                        <i class="fas fa-link mr-2"></i> เชื่อมต่อ {{ $info['name'] }}
                    </a>
                    @else
                    <button @click="expanded = !expanded"
                            class="w-full px-4 py-3 text-white rounded-lg transition"
                            style="background-color: {{ $info['color'] }}">
                        <i class="fas fa-link mr-2"></i> เชื่อมต่อ {{ $info['name'] }}
                    </button>

                    <div x-show="expanded" x-collapse class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            กรอกข้อมูล API สำหรับเชื่อมต่อ {{ $info['name'] }}
                        </p>
                        <form @submit.prevent="connectPlatform('{{ $type }}')" x-ref="form_{{ $type }}">
                            <input type="hidden" name="platform_type" value="{{ $type }}">

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        ชื่อบัญชี
                                    </label>
                                    <input type="text" name="name"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="เช่น My Channel">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Access Token
                                    </label>
                                    <input type="password" name="access_token"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="ใส่ Access Token">
                                </div>

                                @if(in_array($type, ['facebook', 'instagram']))
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Page/Account ID
                                    </label>
                                    <input type="text" name="channel_id"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="ใส่ Page หรือ Account ID">
                                </div>
                                @endif
                            </div>

                            <button type="submit"
                                    class="w-full mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                                    :disabled="connecting"
                                    :class="{ 'opacity-50 cursor-not-allowed': connecting }">
                                <span x-show="!connecting">บันทึก</span>
                                <span x-show="connecting">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> กำลังเชื่อมต่อ...
                                </span>
                            </button>
                        </form>
                    </div>
                    @endif
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Disconnected/Expired Platforms --}}
    @if($platforms->whereIn('status', ['disconnected', 'expired', 'error'])->count() > 0)
    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Platforms ที่มีปัญหา
        </h2>

        <div class="space-y-4">
            @foreach($platforms->whereIn('status', ['disconnected', 'expired', 'error']) as $platform)
            <div class="glass-card rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                         style="background-color: {{ $platformTypes[$platform->platform_type]['color'] ?? '#6B7280' }}20">
                        <i class="{{ $platformTypes[$platform->platform_type]['icon'] ?? 'fas fa-globe' }}"
                           style="color: {{ $platformTypes[$platform->platform_type]['color'] ?? '#6B7280' }}"></i>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $platform->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @if($platform->status === 'expired')
                                <span class="text-yellow-600 dark:text-yellow-400">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Token หมดอายุ
                                </span>
                            @elseif($platform->status === 'error')
                                <span class="text-red-600 dark:text-red-400">
                                    <i class="fas fa-times-circle mr-1"></i> {{ $platform->last_error ?? 'เกิดข้อผิดพลาด' }}
                                </span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-unlink mr-1"></i> ยังไม่เชื่อมต่อ
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($platform->platform_type === 'youtube')
                    <a href="{{ route('admin.video-automation.youtube.connect') }}"
                       class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-sync-alt mr-1"></i> เชื่อมต่อใหม่
                    </a>
                    @else
                    <button @click="reconnectPlatform({{ $platform->id }})"
                            class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-sync-alt mr-1"></i> เชื่อมต่อใหม่
                    </button>
                    @endif
                    <button @click="deletePlatform({{ $platform->id }})"
                            class="px-4 py-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function platformsPage() {
    return {
        connecting: false,

        /**
         * เชื่อมต่อ Platform ใหม่
         *
         * @param {string} platformType
         */
        async connectPlatform(platformType) {
            const form = this.$refs['form_' + platformType];
            if (!form) return;

            const formData = new FormData(form);
            formData.append('platform_type', platformType);
            formData.append('name', formData.get('name') || platformType);

            this.connecting = true;

            try {
                const response = await fetch('{{ route("admin.video-automation.platforms.save") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(Object.fromEntries(formData)),
                });

                const result = await response.json();

                if (result.success) {
                    this.showNotification('success', result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showNotification('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
            } finally {
                this.connecting = false;
            }
        },

        /**
         * เปิด/ปิด Auto Publish
         *
         * @param {number} platformId
         * @param {boolean} enabled
         */
        async toggleAutoPublish(platformId, enabled) {
            try {
                const response = await fetch('{{ route("admin.video-automation.platforms.save") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: platformId,
                        auto_publish: enabled,
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    this.showNotification('success', enabled ? 'เปิดโพสต์อัตโนมัติแล้ว' : 'ปิดโพสต์อัตโนมัติแล้ว');
                } else {
                    this.showNotification('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาด');
            }
        },

        /**
         * แก้ไข Platform
         *
         * @param {number} platformId
         */
        editPlatform(platformId) {
            // TODO: เปิด modal แก้ไข
            alert('ฟังก์ชันแก้ไขอยู่ระหว่างพัฒนา');
        },

        /**
         * ยกเลิกการเชื่อมต่อ Platform
         *
         * @param {number} platformId
         */
        async disconnectPlatform(platformId) {
            if (!confirm('ยืนยันการยกเลิกการเชื่อมต่อ Platform นี้?')) {
                return;
            }

            try {
                const response = await fetch(`{{ url('admin/video-automation/platforms') }}/${platformId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const result = await response.json();

                if (result.success) {
                    this.showNotification('success', result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showNotification('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการลบ');
            }
        },

        /**
         * ลบ Platform
         *
         * @param {number} platformId
         */
        async deletePlatform(platformId) {
            if (!confirm('ยืนยันการลบ Platform นี้? ข้อมูลจะถูกลบถาวร')) {
                return;
            }

            await this.disconnectPlatform(platformId);
        },

        /**
         * เชื่อมต่อใหม่ Platform
         *
         * @param {number} platformId
         */
        reconnectPlatform(platformId) {
            // TODO: เปิด modal เชื่อมต่อใหม่
            alert('ฟังก์ชันเชื่อมต่อใหม่อยู่ระหว่างพัฒนา');
        },

        /**
         * แสดง Notification
         *
         * @param {string} type
         * @param {string} message
         */
        showNotification(type, message) {
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in`;
            notification.innerHTML = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    };
}
</script>
@endsection

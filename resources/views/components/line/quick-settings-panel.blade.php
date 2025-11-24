{{--
    LINE Quick Settings Panel - V3 Component

    ปุ่มลอยสำหรับจัดการการตั้งค่า LINE OA แบบรวดเร็ว
    - เปิด/ปิดระบบ LINE OA
    - เปิด/ปิด Features ต่างๆ
    - ดู Status เรียลไทม์
    - Quick Actions

    วิธีใช้งาน:
    <x-line.quick-settings-panel />

    @props:
    - settings: LineOaSetting instance (optional, จะโหลดเองถ้าไม่ส่งมา)
--}}

@php
    // ดึงการตั้งค่าปัจจุบัน
    $currentSettings = $settings ?? \App\Models\LineOaSetting::getActive();

    // ถ้ายังไม่มีการตั้งค่า ให้สร้างค่า default
    if (!$currentSettings) {
        $currentSettings = new \App\Models\LineOaSetting([
            'is_active' => false,
            'enable_line_messaging' => false,
            'require_line_registration' => false,
        ]);
    }
@endphp

<div x-data="lineQuickSettings()"
     x-init="init()"
     @keydown.escape.window="isOpen = false"
     class="fixed bottom-20 right-6 z-40">

    {{-- Floating Action Button --}}
    <button @click="togglePanel()"
            x-show="!isOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-0"
            x-transition:enter-end="opacity-100 scale-100"
            type="button"
            class="group relative flex items-center justify-center w-16 h-16 rounded-2xl glass-fusion backdrop-blur-xl border-2 shadow-2xl transform hover:scale-110 transition-all duration-300 hover:rotate-12"
            :class="systemStatus.is_active
                ? 'border-green-400/50 hover:border-green-400 shadow-green-500/50'
                : 'border-gray-400/50 hover:border-gray-400 shadow-gray-500/50'"
            title="LINE Quick Settings">

        {{-- Icon --}}
        <div class="relative">
            {{-- LINE Icon --}}
            <svg class="w-8 h-8 transition-all duration-300"
                 :class="systemStatus.is_active ? 'text-green-400' : 'text-gray-400'"
                 viewBox="0 0 24 24"
                 fill="currentColor">
                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
            </svg>

            {{-- Status Indicator Dot --}}
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                      :class="systemStatus.is_active ? 'bg-green-400' : 'bg-gray-400'"></span>
                <span class="relative inline-flex rounded-full h-3 w-3"
                      :class="systemStatus.is_active ? 'bg-green-500' : 'bg-gray-500'"></span>
            </span>
        </div>

        {{-- Tooltip --}}
        <div class="absolute bottom-full right-0 mb-2 px-3 py-1.5 glass-fusion backdrop-blur-md rounded-lg border border-white/30 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap">
            <p class="text-xs font-medium text-white">LINE Quick Settings</p>
            <p class="text-[10px] text-white/70" x-text="systemStatus.is_active ? '🟢 Active' : '⚫ Inactive'"></p>
        </div>
    </button>

    {{-- Slide-in Settings Panel --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-full opacity-0"
         @click.away="isOpen = false"
         class="fixed top-0 right-0 h-full w-full md:w-[450px] glass-fusion backdrop-blur-sm border-l-2 border-white/20 shadow-2xl overflow-y-auto"
         style="display: none;">

        {{-- Panel Header --}}
        <div class="sticky top-0 z-10 glass-fusion border-b border-white/20 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                         :class="systemStatus.is_active
                             ? 'bg-gradient-to-br from-green-500 to-emerald-600'
                             : 'bg-gradient-to-br from-gray-500 to-gray-600'">
                        <i class="fas fa-cog text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Quick Settings</h3>
                        <p class="text-xs text-white/70">LINE Official Account</p>
                    </div>
                </div>
                <button @click="isOpen = false"
                        class="w-10 h-10 rounded-xl glass-fusion backdrop-blur-md hover:bg-white/20 transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-times text-white"></i>
                </button>
            </div>

            {{-- Status Badge --}}
            <div class="flex items-center gap-2 px-4 py-2 rounded-xl"
                 :class="systemStatus.is_active
                     ? 'bg-green-500/20 border border-green-500/30'
                     : 'bg-gray-500/20 border border-gray-500/30'">
                <div class="w-2 h-2 rounded-full animate-pulse"
                     :class="systemStatus.is_active ? 'bg-green-400' : 'bg-gray-400'"></div>
                <span class="text-sm font-semibold"
                      :class="systemStatus.is_active ? 'text-green-300' : 'text-gray-300'"
                      x-text="systemStatus.is_active ? 'System Active' : 'System Inactive'"></span>
            </div>
        </div>

        {{-- Panel Content --}}
        <div class="p-6 space-y-6">

            {{-- Master Toggle --}}
            <div class="glass-fusion backdrop-blur-md rounded-2xl p-6 border border-white/20">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-power-off text-white"></i>
                            <h4 class="text-base font-bold text-white">Master Switch</h4>
                        </div>
                        <p class="text-sm text-white/70 leading-relaxed">
                            เปิด/ปิดระบบ LINE OA ทั้งหมด
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                        <input type="checkbox"
                               x-model="systemStatus.is_active"
                               @change="updateSetting('is_active', $event.target.checked)"
                               class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-emerald-600"></div>
                    </label>
                </div>
            </div>

            {{-- Features Toggles --}}
            <div class="space-y-3">
                <h4 class="text-sm font-bold text-white/90 uppercase tracking-wider px-1">Features</h4>

                {{-- LINE Messaging --}}
                <div class="glass-fusion backdrop-blur-md rounded-xl p-4 border border-white/10 hover:border-white/30 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <i class="fas fa-comment-dots text-blue-400"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">LINE Messaging</p>
                                <p class="text-xs text-white/60">ส่งข้อความผ่าน LINE</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   x-model="systemStatus.enable_line_messaging"
                                   @change="updateSetting('enable_line_messaging', $event.target.checked)"
                                   :disabled="!systemStatus.is_active"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-blue-600 peer-disabled:opacity-50 peer-disabled:cursor-not-allowed"></div>
                        </label>
                    </div>
                </div>

                {{-- LINE Registration Requirement --}}
                <div class="glass-fusion backdrop-blur-md rounded-xl p-4 border border-white/10 hover:border-white/30 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                <i class="fas fa-user-check text-purple-400"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">Require LINE Registration</p>
                                <p class="text-xs text-white/60">บังคับลงทะเบียนผ่าน LINE</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   x-model="systemStatus.require_line_registration"
                                   @change="updateSetting('require_line_registration', $event.target.checked)"
                                   :disabled="!systemStatus.is_active"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-purple-600 peer-disabled:opacity-50 peer-disabled:cursor-not-allowed"></div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="space-y-3">
                <h4 class="text-sm font-bold text-white/90 uppercase tracking-wider px-1">Quick Actions</h4>

                <div class="grid grid-cols-2 gap-3">
                    {{-- Test Connection --}}
                    <button @click="testConnection()"
                            :disabled="loading"
                            class="flex flex-col items-center gap-2 p-4 rounded-xl glass-fusion backdrop-blur-md border border-white/10 hover:border-white/30 hover:bg-white/5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                            <i class="fas fa-plug text-white"></i>
                        </div>
                        <span class="text-xs font-medium text-white">Test Connection</span>
                    </button>

                    {{-- View Logs --}}
                    <a href="{{ route('admin.line-oa.logs') }}"
                       class="flex flex-col items-center gap-2 p-4 rounded-xl glass-fusion backdrop-blur-md border border-white/10 hover:border-white/30 hover:bg-white/5 transition-all duration-200">
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                        <span class="text-xs font-medium text-white">View Logs</span>
                    </a>

                    {{-- Check Stats --}}
                    <a href="{{ route('admin.line-oa.index') }}"
                       class="flex flex-col items-center gap-2 p-4 rounded-xl glass-fusion backdrop-blur-md border border-white/10 hover:border-white/30 hover:bg-white/5 transition-all duration-200">
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center">
                            <i class="fas fa-chart-bar text-white"></i>
                        </div>
                        <span class="text-xs font-medium text-white">Check Stats</span>
                    </a>

                    {{-- Full Settings --}}
                    <a href="{{ route('admin.line-oa.index') }}"
                       class="flex flex-col items-center gap-2 p-4 rounded-xl glass-fusion backdrop-blur-md border border-white/10 hover:border-white/30 hover:bg-white/5 transition-all duration-200">
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                            <i class="fas fa-cogs text-white"></i>
                        </div>
                        <span class="text-xs font-medium text-white">Full Settings</span>
                    </a>
                </div>
            </div>

            {{-- System Info --}}
            <div class="glass-fusion backdrop-blur-md rounded-xl p-4 border border-white/10">
                <h4 class="text-sm font-bold text-white/90 mb-3">System Info</h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-white/60">Channel ID:</span>
                        <span class="text-white font-mono" x-text="systemInfo.channel_id || 'Not set'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">Webhook:</span>
                        <span class="text-white" :class="systemInfo.webhook_verified ? 'text-green-400' : 'text-red-400'">
                            <i :class="systemInfo.webhook_verified ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                            <span x-text="systemInfo.webhook_verified ? 'Verified' : 'Not verified'"></span>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">Last Updated:</span>
                        <span class="text-white/80" x-text="formatDate(systemInfo.updated_at)"></span>
                    </div>
                </div>
            </div>

            {{-- Loading Overlay --}}
            <div x-show="loading"
                 x-transition
                 class="absolute inset-0 glass-fusion backdrop-blur-xl flex items-center justify-center">
                <div class="text-center">
                    <div class="w-16 h-16 border-4 border-white/20 border-t-white rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-white font-medium">กำลังอัพเดท...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Backdrop --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isOpen = false"
         class="fixed inset-0 bg-black/30 z-30"
         style="display: none;">
    </div>
</div>

@push('scripts')
<script>
/**
 * LINE Quick Settings Alpine.js Component
 *
 * จัดการ UI และ API calls สำหรับ Quick Settings Panel
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('lineQuickSettings', () => ({
        // สถานะ
        isOpen: false,
        loading: false,

        // ข้อมูลระบบ
        systemStatus: {
            is_active: {{ $currentSettings->is_active ? 'true' : 'false' }},
            enable_line_messaging: {{ $currentSettings->enable_line_messaging ? 'true' : 'false' }},
            require_line_registration: {{ $currentSettings->require_line_registration ? 'true' : 'false' }},
        },

        systemInfo: {
            channel_id: '{{ $currentSettings->login_channel_id ?? '' }}',
            webhook_verified: false,
            updated_at: '{{ $currentSettings->updated_at ?? now() }}',
        },

        /**
         * เริ่มต้น component
         */
        init() {
            // โหลดข้อมูลเริ่มต้น
            this.loadSystemInfo();

            // ตั้งค่า keyboard shortcuts
            this.setupKeyboardShortcuts();
        },

        /**
         * เปิด/ปิด panel
         */
        togglePanel() {
            this.isOpen = !this.isOpen;

            if (this.isOpen) {
                // โหลดข้อมูลล่าสุดเมื่อเปิด panel
                this.loadSystemInfo();
            }
        },

        /**
         * โหลดข้อมูลระบบ
         */
        async loadSystemInfo() {
            try {
                const response = await fetch('{{ route('admin.line-oa.index') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (response.ok) {
                    // อัพเดทข้อมูลถ้าต้องการ
                    console.log('✅ System info loaded');
                }
            } catch (error) {
                console.error('❌ Failed to load system info:', error);
            }
        },

        /**
         * อัพเดทการตั้งค่า
         */
        async updateSetting(key, value) {
            this.loading = true;

            try {
                const response = await fetch('{{ route('admin.line-oa.quick-update') }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        [key]: value
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // แจ้งเตือนสำเร็จ
                    this.showNotification('success', data.message || 'อัพเดทสำเร็จ');

                    // อัพเดทข้อมูลในหน้า
                    if (data.settings) {
                        this.systemStatus = {
                            is_active: data.settings.is_active,
                            enable_line_messaging: data.settings.enable_line_messaging,
                            require_line_registration: data.settings.require_line_registration,
                        };
                    }
                } else {
                    throw new Error(data.message || 'Failed to update');
                }
            } catch (error) {
                console.error('❌ Failed to update setting:', error);

                // Revert การเปลี่ยนแปลง
                this.systemStatus[key] = !value;

                // แจ้งเตือน error
                this.showNotification('error', 'ไม่สามารถอัพเดทได้ กรุณาลองใหม่');
            } finally {
                this.loading = false;
            }
        },

        /**
         * ทดสอบการเชื่อมต่อ
         */
        async testConnection() {
            this.loading = true;

            try {
                const response = await fetch('{{ route('admin.line-oa.test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showNotification('success', 'เชื่อมต่อสำเร็จ!');
                    this.systemInfo.webhook_verified = true;
                } else {
                    throw new Error(data.message || 'Connection failed');
                }
            } catch (error) {
                console.error('❌ Connection test failed:', error);
                this.showNotification('error', 'การเชื่อมต่อล้มเหลว');
                this.systemInfo.webhook_verified = false;
            } finally {
                this.loading = false;
            }
        },

        /**
         * แสดง notification
         */
        showNotification(type, message) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type, message }
            }));
        },

        /**
         * จัดรูปแบบวันที่
         */
        formatDate(dateString) {
            if (!dateString) return 'N/A';

            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // วินาที

            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff / 60)} min ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)} hr ago`;
            if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`;

            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        /**
         * ตั้งค่า keyboard shortcuts
         */
        setupKeyboardShortcuts() {
            // Ctrl+Shift+L เพื่อเปิด/ปิด panel
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                    e.preventDefault();
                    this.togglePanel();
                }
            });
        }
    }));
});
</script>
@endpush

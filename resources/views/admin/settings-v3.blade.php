{{--
/**
 * Admin Settings V3 - หน้าการตั้งค่าระบบพร้อม V3 Components
 *
 * @uses layouts/admin-v3.blade.php - Layout หลัก
 * @uses arrow-x/forms/*.blade.php - Form Components
 *
 * @data จาก Admin\SettingsController:
 * - $settings - การตั้งค่าทั้งหมดแบ่งกลุ่ม
 * - $availablePermissions - Permissions ที่มี
 *
 * @tip View นี้ใช้ V3 Coding Guidelines 100%
 * @tip รองรับ dark mode อัตโนมัติ
 * @tip Responsive ทุก breakpoint
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'การตั้งค่าระบบ')
@section('page-title', 'การตั้งค่า')

@section('content')
<div class="space-y-6" x-data="settingsManager()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                <i class="fas fa-cogs mr-2"></i>
                การตั้งค่าระบบ
            </h1>
            <p class="text-white/70 text-sm mt-1">จัดการการตั้งค่าทั่วไปของระบบ</p>
        </div>
        <div class="flex gap-3">
            <button @click="resetForm" class="btn-secondary">
                <i class="fas fa-undo mr-2"></i>
                รีเซ็ต
            </button>
            <button @click="saveSettings" class="btn-primary" :disabled="saving">
                <i class="fas fa-save mr-2"></i>
                <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า'"></span>
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
        <div class="flex border-b border-white/20 overflow-x-auto">
            <template x-for="tab in tabs" :key="tab.id">
                <button
                    @click="currentTab = tab.id"
                    :class="{
                        'bg-white/20 border-b-2 border-blue-400': currentTab === tab.id,
                        'hover:bg-white/10': currentTab !== tab.id
                    }"
                    class="px-6 py-4 text-white font-medium transition whitespace-nowrap flex items-center gap-2"
                >
                    <i :class="tab.icon"></i>
                    <span x-text="tab.label"></span>
                </button>
            </template>
        </div>

        <div class="p-6">
            {{-- General Settings Tab --}}
            <div x-show="currentTab === 'general'" x-transition>
                <div class="space-y-6">
                    <h3 class="text-xl font-bold text-white mb-4">
                        <i class="fas fa-sliders-h mr-2"></i>
                        การตั้งค่าทั่วไป
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- App Name --}}
                        <div>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-heading mr-1"></i>
                                ชื่อแอปพลิเคชัน
                            </label>
                            <input
                                type="text"
                                x-model="form.app_name"
                                class="input-glass w-full"
                                placeholder="TP-Affiliate"
                            >
                        </div>

                        {{-- Commission Rate --}}
                        <div>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-percentage mr-1"></i>
                                อัตราคอมมิชชั่น (%)
                            </label>
                            <input
                                type="number"
                                x-model="form.commission_rate"
                                class="input-glass w-full"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="10.00"
                            >
                        </div>

                        {{-- Multi-Level Enabled --}}
                        <div class="md:col-span-2">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="form.multi_level_enabled"
                                    class="checkbox-glass"
                                >
                                <span class="text-white/90 font-medium">
                                    <i class="fas fa-network-wired mr-1"></i>
                                    เปิดใช้งานระบบ Multi-Level Marketing
                                </span>
                            </label>
                        </div>

                        {{-- Commission Depth --}}
                        <div x-show="form.multi_level_enabled" x-transition>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-layer-group mr-1"></i>
                                ความลึกของคอมมิชชั่น (Levels)
                            </label>
                            <input
                                type="number"
                                x-model="form.commission_depth"
                                class="input-glass w-full"
                                min="1"
                                max="100"
                                placeholder="5"
                            >
                        </div>
                    </div>
                </div>
            </div>

            {{-- API Settings Tab --}}
            <div x-show="currentTab === 'api'" x-transition>
                <div class="space-y-6">
                    <h3 class="text-xl font-bold text-white mb-4">
                        <i class="fas fa-plug mr-2"></i>
                        การตั้งค่า API
                    </h3>

                    {{-- Google Translate --}}
                    <div class="card-glass p-6">
                        <h4 class="text-lg font-semibold text-white mb-4">
                            <i class="fab fa-google mr-2"></i>
                            Google Translate API
                        </h4>

                        <div class="space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="form.google_translate_enabled"
                                    class="checkbox-glass"
                                >
                                <span class="text-white/90">เปิดใช้งาน Google Translate API</span>
                            </label>

                            <div x-show="form.google_translate_enabled" x-transition class="space-y-4">
                                <div>
                                    <label class="block text-white/90 mb-2">API Key</label>
                                    <input
                                        type="text"
                                        x-model="form.google_translate_api_key"
                                        class="input-glass w-full"
                                        placeholder="AIza..."
                                    >
                                </div>
                                <div>
                                    <label class="block text-white/90 mb-2">Project ID</label>
                                    <input
                                        type="text"
                                        x-model="form.google_translate_project_id"
                                        class="input-glass w-full"
                                        placeholder="my-project-123"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TinyMCE --}}
                    <div class="card-glass p-6">
                        <h4 class="text-lg font-semibold text-white mb-4">
                            <i class="fas fa-edit mr-2"></i>
                            TinyMCE Editor
                        </h4>

                        <div>
                            <label class="block text-white/90 mb-2">API Key</label>
                            <input
                                type="text"
                                x-model="form.tinymce_api_key"
                                class="input-glass w-full"
                                placeholder="tiny-api-key..."
                            >
                        </div>
                    </div>
                </div>
            </div>

            {{-- Security Settings Tab --}}
            <div x-show="currentTab === 'security'" x-transition>
                <div class="space-y-6">
                    <h3 class="text-xl font-bold text-white mb-4">
                        <i class="fas fa-shield-alt mr-2"></i>
                        การตั้งค่าความปลอดภัย
                    </h3>

                    {{-- Cloudflare Turnstile --}}
                    <div class="card-glass p-6">
                        <h4 class="text-lg font-semibold text-white mb-4">
                            <i class="fab fa-cloudflare mr-2"></i>
                            Cloudflare Turnstile (CAPTCHA)
                        </h4>

                        <div class="space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="form.turnstile_enabled"
                                    class="checkbox-glass"
                                >
                                <span class="text-white/90">เปิดใช้งาน Turnstile</span>
                            </label>

                            <div x-show="form.turnstile_enabled" x-transition class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-white/90 mb-2">Site Key</label>
                                        <input
                                            type="text"
                                            x-model="form.turnstile_site_key"
                                            class="input-glass w-full"
                                            placeholder="0x4A..."
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-white/90 mb-2">Secret Key</label>
                                        <input
                                            type="password"
                                            x-model="form.turnstile_secret_key"
                                            class="input-glass w-full"
                                            placeholder="0x4B..."
                                        >
                                    </div>
                                </div>

                                <div class="flex gap-4 flex-wrap">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="form.turnstile_login" class="checkbox-glass">
                                        <span class="text-white/90">Login</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="form.turnstile_register" class="checkbox-glass">
                                        <span class="text-white/90">Register</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function settingsManager() {
    return {
        currentTab: 'general',
        saving: false,
        tabs: [
            { id: 'general', label: 'ทั่วไป', icon: 'fas fa-sliders-h' },
            { id: 'api', label: 'API', icon: 'fas fa-plug' },
            { id: 'security', label: 'ความปลอดภัย', icon: 'fas fa-shield-alt' }
        ],
        form: {
            app_name: '{{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}',
            commission_rate: {{ \App\Models\Setting::get('commission_rate', 10) }},
            multi_level_enabled: {{ \App\Models\Setting::get('multi_level_enabled', true) ? 'true' : 'false' }},
            commission_depth: {{ \App\Models\Setting::get('commission_depth', 5) }},
            google_translate_enabled: {{ \App\Models\Setting::get('google_translate_enabled', false) ? 'true' : 'false' }},
            google_translate_api_key: '{{ \App\Models\Setting::get('google_translate_api_key', '') }}',
            google_translate_project_id: '{{ \App\Models\Setting::get('google_translate_project_id', '') }}',
            tinymce_api_key: '{{ \App\Models\Setting::get('tinymce_api_key', '') }}',
            turnstile_enabled: {{ \App\Models\Setting::get('turnstile_enabled', false) ? 'true' : 'false' }},
            turnstile_site_key: '{{ \App\Models\Setting::get('turnstile_site_key', '') }}',
            turnstile_secret_key: '{{ \App\Models\Setting::get('turnstile_secret_key', '') }}',
            turnstile_login: {{ \App\Models\Setting::get('turnstile_login', false) ? 'true' : 'false' }},
            turnstile_register: {{ \App\Models\Setting::get('turnstile_register', false) ? 'true' : 'false' }}
        },

        resetForm() {
            location.reload();
        },

        async saveSettings() {
            this.saving = true;

            try {
                const response = await fetch('{{ route('admin.settings.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok) {
                    // Show success message
                    alert('✅ บันทึกการตั้งค่าสำเร็จ!');
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                alert('❌ เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush

<style>
.input-glass {
    @apply bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg px-4 py-2 text-white placeholder-white/50 transition;
}

.input-glass:focus {
    @apply outline-none border-blue-400 bg-white/20;
}

.checkbox-glass {
    @apply w-5 h-5 rounded bg-white/10 border-2 border-white/30 cursor-pointer;
}

.card-glass {
    @apply bg-white/10 backdrop-blur-sm rounded-xl border border-white/20;
}

.btn-primary {
    @apply px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg font-medium hover:from-blue-600 hover:to-purple-700 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-6 py-3 bg-white/10 backdrop-blur-sm text-white rounded-lg font-medium hover:bg-white/20 transition border border-white/20;
}
</style>

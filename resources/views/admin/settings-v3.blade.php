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

    {{-- General Settings Card --}}
    <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
        <div class="p-6">
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
                </div>

                {{-- Quick Links --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    {{-- MLM Settings --}}
                    <a href="{{ route('admin.mlm.settings.index') }}" class="card-glass p-4 hover:bg-white/20 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/30 flex items-center justify-center">
                                <i class="fas fa-sitemap text-purple-300"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-medium group-hover:text-purple-300 transition">การตั้งค่า MLM</h4>
                                <p class="text-white/50 text-xs">คอมมิชชั่น, ระดับ, แผนการตลาด</p>
                            </div>
                            <i class="fas fa-chevron-right text-white/30 ml-auto"></i>
                        </div>
                    </a>

                    {{-- Security Settings (Turnstile) --}}
                    <a href="{{ route('admin.security.index') }}" class="card-glass p-4 hover:bg-white/20 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-red-500/30 flex items-center justify-center">
                                <i class="fas fa-shield-alt text-red-300"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-medium group-hover:text-red-300 transition">ความปลอดภัย</h4>
                                <p class="text-white/50 text-xs">Turnstile, IP Ban, Rate Limit</p>
                            </div>
                            <i class="fas fa-chevron-right text-white/30 ml-auto"></i>
                        </div>
                    </a>

                    {{-- Cloudflare Settings --}}
                    <a href="{{ route('admin.cloudflare.index') }}" class="card-glass p-4 hover:bg-white/20 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-500/30 flex items-center justify-center">
                                <i class="fab fa-cloudflare text-orange-300"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-medium group-hover:text-orange-300 transition">Cloudflare CDN</h4>
                                <p class="text-white/50 text-xs">Cache, Optimization, API</p>
                            </div>
                            <i class="fas fa-chevron-right text-white/30 ml-auto"></i>
                        </div>
                    </a>
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
        saving: false,
        form: {
            app_name: '{{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}'
        },

        resetForm() {
            location.reload();
        },

        async saveSettings() {
            this.saving = true;

            try {
                const response = await fetch('{{ route('admin.settings.update') }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    alert('✅ ' + (data.message || 'บันทึกการตั้งค่าสำเร็จ!'));
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                alert('❌ เกิดข้อผิดพลาดในการบันทึก: ' + error.message);
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

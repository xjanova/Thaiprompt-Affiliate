{{--
/**
 * Admin Profile V3 - หน้าแก้ไขโปรไฟล์ส่วนตัวพร้อม V3 Components
 *
 * @uses layouts/admin-v3.blade.php - Layout หลัก
 * @uses arrow-x/forms/*.blade.php - Form Components
 *
 * @data: $user - ข้อมูลผู้ใช้ปัจจุบัน
 *
 * @tip View นี้ใช้ V3 Coding Guidelines 100%
 * @tip รองรับ dark mode อัตโนมัติ
 * @tip Responsive ทุก breakpoint
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'แก้ไขโปรไฟล์')
@section('page-title', 'โปรไฟล์')

@section('content')
<div class="space-y-6" x-data="profileManager()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                <i class="fas fa-user-circle mr-2"></i>
                โปรไฟล์ของฉัน
            </h1>
            <p class="text-white/70 text-sm mt-1">จัดการข้อมูลส่วนตัวและความปลอดภัย</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Sidebar - Profile Card --}}
        <div class="lg:col-span-1">
            <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl p-6">
                {{-- Avatar --}}
                <div class="text-center mb-6">
                    <div class="relative inline-block">
                        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-5xl font-bold shadow-2xl">
                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                        <button
                            @click="changeAvatar"
                            class="absolute bottom-0 right-0 w-10 h-10 bg-blue-500 hover:bg-blue-600 rounded-full flex items-center justify-center text-white shadow-lg transition"
                        >
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <h3 class="text-xl font-bold text-white mt-4">{{ auth()->user()->name ?? 'Admin' }}</h3>
                    <p class="text-white/70 text-sm">{{ auth()->user()->email }}</p>
                    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-green-400 to-emerald-500 text-white text-xs font-bold rounded-full shadow-lg">
                        <i class="fas fa-shield-alt"></i>
                        Admin
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-white/90">
                        <span class="text-sm">
                            <i class="fas fa-calendar mr-2"></i>
                            สมัครเมื่อ
                        </span>
                        <span class="text-sm font-medium">
                            {{ auth()->user()->created_at ? auth()->user()->created_at->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-white/90">
                        <span class="text-sm">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            เข้าสู่ระบบล่าสุด
                        </span>
                        <span class="text-sm font-medium">
                            {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content - Profile Forms --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Personal Information --}}
            <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
                <div class="px-6 py-4 border-b border-white/20 bg-gradient-to-r from-blue-500/20 to-purple-600/20">
                    <h3 class="text-xl font-bold text-white drop-shadow flex items-center gap-2">
                        <i class="fas fa-id-card"></i>
                        ข้อมูลส่วนตัว
                    </h3>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-user mr-1"></i>
                                ชื่อ
                            </label>
                            <input
                                type="text"
                                x-model="form.name"
                                class="input-glass w-full"
                                placeholder="กรอกชื่อ"
                            >
                        </div>

                        <div>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-envelope mr-1"></i>
                                อีเมล
                            </label>
                            <input
                                type="email"
                                x-model="form.email"
                                class="input-glass w-full"
                                placeholder="กรอกอีเมล"
                            >
                        </div>

                        <div>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-phone mr-1"></i>
                                เบอร์โทร
                            </label>
                            <input
                                type="tel"
                                x-model="form.phone"
                                class="input-glass w-full"
                                placeholder="กรอกเบอร์โทร"
                            >
                        </div>

                        <div>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-briefcase mr-1"></i>
                                ตำแหน่ง
                            </label>
                            <input
                                type="text"
                                x-model="form.position"
                                class="input-glass w-full"
                                placeholder="กรอกตำแหน่ง"
                            >
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button @click="resetPersonalForm" class="btn-secondary">
                            <i class="fas fa-undo mr-2"></i>
                            รีเซ็ต
                        </button>
                        <button @click="savePersonalInfo" class="btn-primary" :disabled="saving">
                            <i class="fas fa-save mr-2"></i>
                            <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Change Password --}}
            <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
                <div class="px-6 py-4 border-b border-white/20 bg-gradient-to-r from-red-500/20 to-pink-600/20">
                    <h3 class="text-xl font-bold text-white drop-shadow flex items-center gap-2">
                        <i class="fas fa-lock"></i>
                        เปลี่ยนรหัสผ่าน
                    </h3>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-white/90 font-medium mb-2">
                            <i class="fas fa-key mr-1"></i>
                            รหัสผ่านปัจจุบัน
                        </label>
                        <input
                            type="password"
                            x-model="passwordForm.current_password"
                            class="input-glass w-full"
                            placeholder="กรอกรหัสผ่านปัจจุบัน"
                        >
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                รหัสผ่านใหม่
                            </label>
                            <input
                                type="password"
                                x-model="passwordForm.new_password"
                                class="input-glass w-full"
                                placeholder="กรอกรหัสผ่านใหม่"
                            >
                        </div>

                        <div>
                            <label class="block text-white/90 font-medium mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                ยืนยันรหัสผ่านใหม่
                            </label>
                            <input
                                type="password"
                                x-model="passwordForm.new_password_confirmation"
                                class="input-glass w-full"
                                placeholder="กรอกรหัสผ่านใหม่อีกครั้ง"
                            >
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button @click="resetPasswordForm" class="btn-secondary">
                            <i class="fas fa-undo mr-2"></i>
                            รีเซ็ต
                        </button>
                        <button @click="changePassword" class="btn-danger" :disabled="savingPassword">
                            <i class="fas fa-lock mr-2"></i>
                            <span x-text="savingPassword ? 'กำลังเปลี่ยน...' : 'เปลี่ยนรหัสผ่าน'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function profileManager() {
    return {
        saving: false,
        savingPassword: false,
        form: {
            name: '{{ auth()->user()->name ?? '' }}',
            email: '{{ auth()->user()->email ?? '' }}',
            phone: '{{ auth()->user()->phone ?? '' }}',
            position: '{{ auth()->user()->position ?? '' }}'
        },
        passwordForm: {
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
        },

        changeAvatar() {
            alert('🎨 ฟีเจอร์เปลี่ยน Avatar กำลังพัฒนา...');
        },

        resetPersonalForm() {
            location.reload();
        },

        async savePersonalInfo() {
            this.saving = true;

            try {
                const response = await fetch('{{ route('admin.profile.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok) {
                    alert('✅ บันทึกข้อมูลส่วนตัวสำเร็จ!');
                    location.reload();
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving profile:', error);
                alert('❌ เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        },

        resetPasswordForm() {
            this.passwordForm = {
                current_password: '',
                new_password: '',
                new_password_confirmation: ''
            };
        },

        async changePassword() {
            // Validate
            if (!this.passwordForm.current_password || !this.passwordForm.new_password) {
                alert('⚠️ กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            if (this.passwordForm.new_password !== this.passwordForm.new_password_confirmation) {
                alert('⚠️ รหัสผ่านใหม่ไม่ตรงกัน');
                return;
            }

            if (this.passwordForm.new_password.length < 8) {
                alert('⚠️ รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
                return;
            }

            this.savingPassword = true;

            try {
                const response = await fetch('{{ route('admin.profile.change-password') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.passwordForm)
                });

                const data = await response.json();

                if (response.ok) {
                    alert('✅ เปลี่ยนรหัสผ่านสำเร็จ!');
                    this.resetPasswordForm();
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error changing password:', error);
                alert('❌ เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน');
            } finally {
                this.savingPassword = false;
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

.btn-primary {
    @apply px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg font-medium hover:from-blue-600 hover:to-purple-700 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-6 py-3 bg-white/10 backdrop-blur-sm text-white rounded-lg font-medium hover:bg-white/20 transition border border-white/20;
}

.btn-danger {
    @apply px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-lg font-medium hover:from-red-600 hover:to-pink-700 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>

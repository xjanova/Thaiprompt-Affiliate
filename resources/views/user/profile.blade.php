@extends('layouts.user')

@section('title', 'โปรไฟล์')

@push('scripts')
@if(config('turnstile.enabled') && config('turnstile.points.password_change'))
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@endpush

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900">โปรไฟล์ของฉัน</h1>
        <p class="text-sm text-gray-600 mt-1">จัดการข้อมูลส่วนตัวของคุณ</p>
    </div>

    <!-- Profile Information -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center gap-6 mb-6">
            <div class="w-24 h-24 rounded-full bg-gradient-primary flex items-center justify-center text-white text-4xl font-bold">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>
                <span class="inline-block mt-2 px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-medium">
                    {{ ucfirst($user->role) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ</label>
                <input type="text" value="{{ $user->name }}" readonly
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                <input type="email" value="{{ $user->email }}" readonly
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">บทบาท</label>
                <input type="text" value="{{ ucfirst($user->role) }}" readonly
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ภาษาที่ต้องการ</label>
                <input type="text" value="{{ $user->preferred_language === 'th' ? 'ไทย' : 'English' }}" readonly
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">สมัครสมาชิกเมื่อ</label>
                <input type="text" value="{{ $user->created_at->format('d/m/Y H:i') }}" readonly
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>

            @if($user->affiliate)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">รหัสแนะนำ</label>
                <input type="text" value="{{ $user->affiliate->referral_code }}" readonly
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>
            @endif
        </div>

        <div class="mt-6 pt-6 border-t">
            <p class="text-sm text-gray-600">
                ต้องการแก้ไขข้อมูล? กรุณาติดต่อผู้ดูแลระบบ
            </p>
        </div>
    </div>

    <!-- Change Password -->
    <div id="change-password" class="bg-white rounded-xl shadow-md p-6" x-data="{ showPasswordForm: false }">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">เปลี่ยนรหัสผ่าน</h2>
                <p class="text-sm text-gray-600 mt-1">อัปเดตรหัสผ่านของคุณเพื่อความปลอดภัย</p>
            </div>
            <button @click="showPasswordForm = !showPasswordForm"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <span x-show="!showPasswordForm">เปลี่ยนรหัสผ่าน</span>
                <span x-show="showPasswordForm">ยกเลิก</span>
            </button>
        </div>

        <div x-show="showPasswordForm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             style="display: none;">
            <form method="POST" action="{{ route('user.profile.update-password') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข</p>
                    @error('new_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="new_password_confirmation" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                @if(config('turnstile.enabled') && config('turnstile.points.password_change'))
                <div class="flex justify-center pt-2">
                    <div class="cf-turnstile"
                         data-sitekey="{{ config('turnstile.site_key') }}"
                         data-theme="{{ config('turnstile.theme') }}"
                         data-size="{{ config('turnstile.size') }}">
                    </div>
                </div>
                @endif

                <div class="flex gap-3 pt-4">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        บันทึกรหัสผ่านใหม่
                    </button>
                    <button type="button" @click="showPasswordForm = false"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Statistics -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">สถิติบัญชี</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-indigo-600">{{ $user->commissions()->count() }}</p>
                <p class="text-sm text-gray-600 mt-1">คอมมิชชั่นทั้งหมด</p>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-green-600">
                    ฿{{ number_format($user->commissions()->where('status', 'paid')->sum('amount'), 0) }}
                </p>
                <p class="text-sm text-gray-600 mt-1">จ่ายแล้ว</p>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-yellow-600">
                    {{ $user->commissions()->where('status', 'pending')->count() }}
                </p>
                <p class="text-sm text-gray-600 mt-1">รอดำเนินการ</p>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-purple-600">
                    {{ $user->affiliate ? $user->affiliate->children()->count() : 0 }}
                </p>
                <p class="text-sm text-gray-600 mt-1">ผู้แนะนำ</p>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin-v3')

@section('title', 'เพิ่ม Tenant ใหม่')

@section('content')
<div class="container-fluid px-4 py-6" x-data="tenantCreate()">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.ai-core.tenants.index') }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-teal-600 dark:from-green-400 dark:to-teal-400 bg-clip-text text-transparent">
                ➕ เพิ่ม Tenant ใหม่
            </h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400 ml-13">
            เพิ่ม Tenant ใหม่เข้าสู่ระบบ AI Core
        </p>
    </div>

    <form method="POST" action="{{ route('admin.ai-core.tenants.store') }}" class="space-y-6">
        @csrf

        {{-- Basic Information --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">ℹ️</span>
                ข้อมูลพื้นฐาน
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Tenant Key --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        🔑 Tenant Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="tenant_key"
                           value="{{ old('tenant_key') }}"
                           required
                           maxlength="100"
                           placeholder="company_name"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('tenant_key') border-red-500 @enderror">
                    @error('tenant_key')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        รหัสเฉพาะของ Tenant (ต้องไม่ซ้ำ, ใช้ snake_case)
                    </p>
                </div>

                {{-- Tenant Name --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📝 ชื่อ Tenant <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="tenant_name"
                           value="{{ old('tenant_name') }}"
                           required
                           maxlength="200"
                           placeholder="บริษัท ABC จำกัด"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('tenant_name') border-red-500 @enderror">
                    @error('tenant_name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- User ID --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        👤 ผู้ใช้ (Owner)
                    </label>
                    <select name="user_id"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('user_id') border-red-500 @enderror">
                        <option value="">-- ไม่ระบุ --</option>
                        @foreach(\App\Models\User::orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ⚡ สถานะ <span class="text-red-500">*</span>
                    </label>
                    <select name="status"
                            required
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📄 คำอธิบาย
                    </label>
                    <textarea name="description"
                              rows="3"
                              placeholder="รายละเอียดเกี่ยวกับ Tenant..."
                              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Subscription Configuration --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">💎</span>
                Subscription Plan
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Subscription Tier --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        🏷️ แผน <span class="text-red-500">*</span>
                    </label>
                    <select name="subscription_tier"
                            required
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('subscription_tier') border-red-500 @enderror">
                        <option value="free" {{ old('subscription_tier', 'free') == 'free' ? 'selected' : '' }}>Free</option>
                        <option value="basic" {{ old('subscription_tier') == 'basic' ? 'selected' : '' }}>Basic</option>
                        <option value="pro" {{ old('subscription_tier') == 'pro' ? 'selected' : '' }}>Pro</option>
                        <option value="enterprise" {{ old('subscription_tier') == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                    </select>
                    @error('subscription_tier')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Subscription Start --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📅 วันที่เริ่ม
                    </label>
                    <input type="datetime-local"
                           name="subscription_starts_at"
                           value="{{ old('subscription_starts_at', now()->format('Y-m-d\TH:i')) }}"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('subscription_starts_at') border-red-500 @enderror">
                    @error('subscription_starts_at')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Subscription End --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ⏰ วันหมดอายุ
                    </label>
                    <input type="datetime-local"
                           name="subscription_ends_at"
                           value="{{ old('subscription_ends_at') }}"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('subscription_ends_at') border-red-500 @enderror">
                    @error('subscription_ends_at')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        เว้นว่างไว้ = ไม่มีวันหมดอายุ
                    </p>
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">📞</span>
                ข้อมูลติดต่อ
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Contact Name --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        👤 ชื่อผู้ติดต่อ
                    </label>
                    <input type="text"
                           name="contact_name"
                           value="{{ old('contact_name') }}"
                           maxlength="200"
                           placeholder="นาย ABC"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('contact_name') border-red-500 @enderror">
                    @error('contact_name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contact Email --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📧 อีเมล
                    </label>
                    <input type="email"
                           name="contact_email"
                           value="{{ old('contact_email') }}"
                           maxlength="200"
                           placeholder="contact@example.com"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('contact_email') border-red-500 @enderror">
                    @error('contact_email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contact Phone --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📱 เบอร์โทร
                    </label>
                    <input type="tel"
                           name="contact_phone"
                           value="{{ old('contact_phone') }}"
                           maxlength="20"
                           placeholder="08X-XXX-XXXX"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('contact_phone') border-red-500 @enderror">
                    @error('contact_phone')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">⚙️</span>
                การตั้งค่า
            </h2>

            <div class="space-y-4">
                {{-- Is Enabled --}}
                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <input type="hidden" name="is_enabled" value="0">
                    <input type="checkbox"
                           name="is_enabled"
                           value="1"
                           {{ old('is_enabled', 1) ? 'checked' : '' }}
                           class="w-5 h-5 text-green-600 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            ✅ เปิดใช้งาน Tenant ทันที
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Tenant จะพร้อมใช้งานทันทีหลังสร้างเสร็จ
                        </p>
                    </div>
                </div>

                {{-- Allow Trial --}}
                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <input type="hidden" name="allow_trial" value="0">
                    <input type="checkbox"
                           name="allow_trial"
                           value="1"
                           {{ old('allow_trial') ? 'checked' : '' }}
                           class="w-5 h-5 text-green-600 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            🎁 อนุญาต Trial Period
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Tenant สามารถทดลองใช้ Features แบบ Trial ได้
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-4">
            <button type="submit"
                    class="flex-1 lg:flex-none px-8 py-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                💾 บันทึก Tenant
            </button>
            <a href="{{ route('admin.ai-core.tenants.index') }}"
               class="flex-1 lg:flex-none px-8 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-200 text-center">
                ❌ ยกเลิก
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function tenantCreate() {
    return {
        // Can add reactive data here if needed
    }
}
</script>
@endpush
@endsection

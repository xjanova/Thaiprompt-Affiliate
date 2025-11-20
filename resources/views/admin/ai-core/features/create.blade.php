@extends('layouts.admin-v3')

@section('title', 'เพิ่ม AI Feature ใหม่')

@section('content')
<div class="container-fluid px-4 py-6" x-data="featureCreate()">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.ai-core.features.index') }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent">
                ➕ เพิ่ม AI Feature ใหม่
            </h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400 ml-13">
            เพิ่ม Feature ใหม่เข้าสู่ระบบ AI Core
        </p>
    </div>

    <form method="POST" action="{{ route('admin.ai-core.features.store') }}" class="space-y-6">
        @csrf

        {{-- Basic Information --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">ℹ️</span>
                ข้อมูลพื้นฐาน
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Feature Key --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        🔑 Feature Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="feature_key"
                           value="{{ old('feature_key') }}"
                           required
                           maxlength="100"
                           placeholder="ai_bot_marketplace"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('feature_key') border-red-500 @enderror">
                    @error('feature_key')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        รหัสเฉพาะของ Feature (ต้องไม่ซ้ำ, ใช้ snake_case)
                    </p>
                </div>

                {{-- Feature Name --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📝 ชื่อ Feature <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="feature_name"
                           value="{{ old('feature_name') }}"
                           required
                           maxlength="200"
                           placeholder="ตลาด AI Bots และระบบให้เช่า"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('feature_name') border-red-500 @enderror">
                    @error('feature_name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Icon --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        🎨 ไอคอน
                    </label>
                    <input type="text"
                           name="icon"
                           value="{{ old('icon', '🤖') }}"
                           maxlength="10"
                           placeholder="🤖"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('icon') border-red-500 @enderror">
                    @error('icon')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Emoji ที่ใช้แสดง Feature (เช่น 🤖, 🎨, 🔗)
                    </p>
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📂 หมวดหมู่ <span class="text-red-500">*</span>
                    </label>
                    <select name="category"
                            required
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('category') border-red-500 @enderror">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <option value="marketplace" {{ old('category') == 'marketplace' ? 'selected' : '' }}>Marketplace</option>
                        <option value="generation" {{ old('category') == 'generation' ? 'selected' : '' }}>Generation</option>
                        <option value="integration" {{ old('category') == 'integration' ? 'selected' : '' }}>Integration</option>
                        <option value="automation" {{ old('category') == 'automation' ? 'selected' : '' }}>Automation</option>
                        <option value="analytics" {{ old('category') == 'analytics' ? 'selected' : '' }}>Analytics</option>
                        <option value="trading" {{ old('category') == 'trading' ? 'selected' : '' }}>Trading</option>
                        <option value="platform" {{ old('category') == 'platform' ? 'selected' : '' }}>Platform</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📄 คำอธิบาย
                    </label>
                    <textarea name="description"
                              rows="4"
                              placeholder="อธิบายรายละเอียดของ Feature..."
                              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror>
                </div>
            </div>
        </div>

        {{-- Quota Configuration --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">📊</span>
                การจัดการ Quota
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Quota Type --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📈 ประเภท Quota <span class="text-red-500">*</span>
                    </label>
                    <select name="quota_type"
                            x-model="quotaType"
                            required
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('quota_type') border-red-500 @enderror">
                        <option value="none">ไม่จำกัด (None)</option>
                        <option value="count" {{ old('quota_type') == 'count' ? 'selected' : '' }}>จำนวนครั้ง (Count)</option>
                        <option value="usage" {{ old('quota_type') == 'usage' ? 'selected' : '' }}>การใช้งาน (Usage)</option>
                        <option value="duration" {{ old('quota_type') == 'duration' ? 'selected' : '' }}>ระยะเวลา (Duration)</option>
                        <option value="token" {{ old('quota_type') == 'token' ? 'selected' : '' }}>โทเค็น (Token)</option>
                    </select>
                    @error('quota_type')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Default Quota Limit --}}
                <div x-show="quotaType !== 'none'">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        🎯 Quota เริ่มต้น
                    </label>
                    <input type="number"
                           name="default_quota_limit"
                           value="{{ old('default_quota_limit') }}"
                           min="0"
                           placeholder="100"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('default_quota_limit') border-red-500 @enderror">
                    @error('default_quota_limit')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Max Quota Allowed --}}
                <div x-show="quotaType !== 'none'">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ⚡ Quota สูงสุด
                    </label>
                    <input type="number"
                           name="max_quota_allowed"
                           value="{{ old('max_quota_allowed') }}"
                           min="0"
                           placeholder="1000"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('max_quota_allowed') border-red-500 @enderror">
                    @error('max_quota_allowed')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        จำนวน Quota สูงสุดที่อนุญาต (ใส่ 0 = ไม่จำกัด)
                    </p>
                </div>

                {{-- Allow Overage --}}
                <div x-show="quotaType !== 'none'" class="flex items-center gap-3">
                    <input type="hidden" name="allow_overage" value="0">
                    <input type="checkbox"
                           name="allow_overage"
                           value="1"
                           {{ old('allow_overage') ? 'checked' : '' }}
                           class="w-5 h-5 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        อนุญาตให้ใช้เกิน Quota (Overage)
                    </label>
                </div>
            </div>
        </div>

        {{-- Pricing Configuration --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">💰</span>
                การตั้งราคา
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Pricing Tier --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        🏷️ ระดับราคา <span class="text-red-500">*</span>
                    </label>
                    <select name="pricing_tier"
                            x-model="pricingTier"
                            required
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('pricing_tier') border-red-500 @enderror">
                        <option value="free" {{ old('pricing_tier') == 'free' ? 'selected' : '' }}>ฟรี (Free)</option>
                        <option value="basic" {{ old('pricing_tier') == 'basic' ? 'selected' : '' }}>พื้นฐาน (Basic)</option>
                        <option value="pro" {{ old('pricing_tier') == 'pro' ? 'selected' : '' }}>โปร (Pro)</option>
                        <option value="enterprise" {{ old('pricing_tier') == 'enterprise' ? 'selected' : '' }}>องค์กร (Enterprise)</option>
                    </select>
                    @error('pricing_tier')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Base Price --}}
                <div x-show="pricingTier !== 'free'">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        💵 ราคาพื้นฐาน (บาท)
                    </label>
                    <input type="number"
                           name="base_price"
                           value="{{ old('base_price', 0) }}"
                           min="0"
                           step="0.01"
                           placeholder="0.00"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('base_price') border-red-500 @enderror">
                    @error('base_price')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Usage Price --}}
                <div x-show="pricingTier !== 'free'">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📊 ราคาต่อการใช้งาน (บาท)
                    </label>
                    <input type="number"
                           name="usage_price"
                           value="{{ old('usage_price', 0) }}"
                           min="0"
                           step="0.01"
                           placeholder="0.00"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition-all @error('usage_price') border-red-500 @enderror">
                    @error('usage_price')
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
                           class="w-5 h-5 text-blue-600 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            ✅ เปิดใช้งาน Feature ทันที
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Feature จะพร้อมใช้งานทันทีหลังสร้างเสร็จ
                        </p>
                    </div>
                </div>

                {{-- Is Visible --}}
                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <input type="hidden" name="is_visible" value="0">
                    <input type="checkbox"
                           name="is_visible"
                           value="1"
                           {{ old('is_visible', 1) ? 'checked' : '' }}
                           class="w-5 h-5 text-blue-600 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            👁️ แสดง Feature ในรายการ
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            แสดง Feature นี้ในรายการสำหรับ Tenants
                        </p>
                    </div>
                </div>

                {{-- Requires Approval --}}
                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <input type="hidden" name="requires_approval" value="0">
                    <input type="checkbox"
                           name="requires_approval"
                           value="1"
                           {{ old('requires_approval') ? 'checked' : '' }}
                           class="w-5 h-5 text-blue-600 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            🔒 ต้องการอนุมัติก่อนใช้งาน
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Tenant ต้องได้รับอนุมัติก่อนจะเข้าถึง Feature นี้
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-4">
            <button type="submit"
                    class="flex-1 lg:flex-none px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                💾 บันทึก Feature
            </button>
            <a href="{{ route('admin.ai-core.features.index') }}"
               class="flex-1 lg:flex-none px-8 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-200 text-center">
                ❌ ยกเลิก
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function featureCreate() {
    return {
        quotaType: '{{ old('quota_type', 'none') }}',
        pricingTier: '{{ old('pricing_tier', 'free') }}'
    }
}
</script>
@endpush
@endsection

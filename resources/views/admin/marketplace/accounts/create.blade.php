@extends('layouts.admin-v3')

@section('title', 'เพิ่มบัญชี Marketplace')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-500 via-teal-500 to-cyan-600 dark:from-green-700 dark:via-teal-700 dark:to-cyan-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-link"></i>
            </div>
            <div class="absolute text-white/10 text-6xl bottom-10 right-40" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
                <i class="fas fa-key"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.marketplace.accounts.index') }}"
                   class="glass-fusion p-3 rounded-xl hover:bg-white/20 transition-all">
                    <i class="fas fa-arrow-left text-xl text-white"></i>
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-plus text-3xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white drop-shadow-lg">เพิ่มบัญชี Marketplace</h1>
                    <p class="text-green-100 mt-1">เชื่อมต่อบัญชี Lazada, Shopee, TikTok Shop</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.marketplace.accounts.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-info-circle text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Platform --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-store mr-1 text-orange-500"></i>
                                Platform <span class="text-red-500">*</span>
                            </label>
                            <select name="platform_id" required id="platform_select"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all">
                                <option value="">เลือก Platform</option>
                                @foreach($platforms ?? [] as $platform)
                                    <option value="{{ $platform->id }}" data-slug="{{ $platform->slug }}">
                                        {{ $platform->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('platform_id')
                                <p class="text-red-500 text-sm mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Account Name --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tag mr-1 text-blue-500"></i>
                                ชื่อบัญชี <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="account_name" value="{{ old('account_name') }}" required
                                   placeholder="เช่น Lazada Main Account"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all">
                            @error('account_name')
                                <p class="text-red-500 text-sm mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Shop ID --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-id-badge mr-1 text-purple-500"></i>
                                Shop ID
                            </label>
                            <input type="text" name="shop_id" value="{{ old('shop_id') }}"
                                   placeholder="ID ร้านค้าจาก Platform"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all">
                        </div>

                        {{-- Shop Name --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-store mr-1 text-pink-500"></i>
                                ชื่อร้านค้า
                            </label>
                            <input type="text" name="shop_name" value="{{ old('shop_name') }}"
                                   placeholder="ชื่อร้านค้า"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all">
                        </div>

                        {{-- User --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-user mr-1 text-indigo-500"></i>
                                ผู้ใช้เจ้าของบัญชี
                            </label>
                            <select name="user_id"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all">
                                <option value="">ไม่ระบุ (Admin)</option>
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Commission Rate --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-percentage mr-1 text-emerald-500"></i>
                                อัตราคอมมิชชั่น (%)
                            </label>
                            <input type="number" name="commission_rate" value="{{ old('commission_rate', 5) }}"
                                   min="0" max="100" step="0.01"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all">
                        </div>
                    </div>
                </div>

                {{-- API Credentials --}}
                <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-key text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">API Credentials</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 ml-13">ข้อมูล API จาก Developer Portal ของแต่ละ Platform</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- App Key --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-key mr-1 text-yellow-500"></i>
                                App Key <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="app_key" value="{{ old('app_key') }}" required
                                   placeholder="API App Key"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all font-mono text-sm">
                            @error('app_key')
                                <p class="text-red-500 text-sm mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- App Secret --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-lock mr-1 text-red-500"></i>
                                App Secret <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="app_secret" value="{{ old('app_secret') }}" required
                                   placeholder="API App Secret"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all font-mono text-sm">
                            @error('app_secret')
                                <p class="text-red-500 text-sm mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Access Token --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-ticket-alt mr-1 text-green-500"></i>
                                Access Token
                            </label>
                            <input type="text" name="access_token" value="{{ old('access_token') }}"
                                   placeholder="OAuth Access Token (ถ้ามี)"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all font-mono text-sm">
                        </div>

                        {{-- Refresh Token --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-sync-alt mr-1 text-blue-500"></i>
                                Refresh Token
                            </label>
                            <input type="text" name="refresh_token" value="{{ old('refresh_token') }}"
                                   placeholder="OAuth Refresh Token (ถ้ามี)"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all font-mono text-sm">
                        </div>
                    </div>

                    {{-- Shopee Additional Fields --}}
                    <div id="shopee-fields" class="hidden pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-store text-orange-500"></i>
                            Shopee-Specific Fields
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Partner ID (Shopee)
                                </label>
                                <input type="text" name="partner_id" value="{{ old('partner_id') }}"
                                       placeholder="Shopee Partner ID"
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all font-mono text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Partner Key (Shopee)
                                </label>
                                <input type="password" name="partner_key" value="{{ old('partner_key') }}"
                                       placeholder="Shopee Partner Key"
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all font-mono text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sync Settings --}}
                <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-sync text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">ตั้งค่า Sync</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Auto Sync Products --}}
                        <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <input type="checkbox" name="auto_sync_products" value="1" id="auto_sync_products"
                                   {{ old('auto_sync_products', true) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <label for="auto_sync_products" class="text-gray-700 dark:text-gray-300 font-medium">
                                <i class="fas fa-boxes mr-1 text-purple-500"></i>
                                Sync สินค้าอัตโนมัติ
                            </label>
                        </div>

                        {{-- Auto Sync Orders --}}
                        <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <input type="checkbox" name="auto_sync_orders" value="1" id="auto_sync_orders"
                                   {{ old('auto_sync_orders', true) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <label for="auto_sync_orders" class="text-gray-700 dark:text-gray-300 font-medium">
                                <i class="fas fa-shopping-cart mr-1 text-orange-500"></i>
                                Sync ออเดอร์อัตโนมัติ
                            </label>
                        </div>

                        {{-- Sync Frequency --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-clock mr-1 text-blue-500"></i>
                                ความถี่ Sync
                            </label>
                            <select name="sync_frequency"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all">
                                <option value="hourly" {{ old('sync_frequency') == 'hourly' ? 'selected' : '' }}>ทุกชั่วโมง</option>
                                <option value="daily" {{ old('sync_frequency', 'daily') == 'daily' ? 'selected' : '' }}>ทุกวัน</option>
                                <option value="weekly" {{ old('sync_frequency') == 'weekly' ? 'selected' : '' }}>ทุกสัปดาห์</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Platform Guide --}}
                <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-book text-blue-500"></i>
                        คู่มือ Platform
                    </h3>

                    <div class="space-y-4">
                        {{-- Lazada --}}
                        <div class="group p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-100 dark:border-blue-800 hover:shadow-lg transition-all">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-lg">
                                    <i class="fas fa-shopping-bag text-white"></i>
                                </div>
                                <h4 class="font-bold text-blue-700 dark:text-blue-400">Lazada</h4>
                            </div>
                            <p class="text-sm text-blue-600 dark:text-blue-300">
                                ลงทะเบียนที่ <a href="https://open.lazada.com" target="_blank" class="underline hover:text-blue-800 dark:hover:text-blue-200 font-medium">Lazada Open Platform</a>
                            </p>
                        </div>

                        {{-- Shopee --}}
                        <div class="group p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-100 dark:border-orange-800 hover:shadow-lg transition-all">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center shadow-lg">
                                    <i class="fas fa-store text-white"></i>
                                </div>
                                <h4 class="font-bold text-orange-700 dark:text-orange-400">Shopee</h4>
                            </div>
                            <p class="text-sm text-orange-600 dark:text-orange-300">
                                ลงทะเบียนที่ <a href="https://open.shopee.com" target="_blank" class="underline hover:text-orange-800 dark:hover:text-orange-200 font-medium">Shopee Open Platform</a>
                            </p>
                        </div>

                        {{-- TikTok Shop --}}
                        <div class="group p-4 bg-gradient-to-r from-gray-50 to-pink-50 dark:from-gray-800 dark:to-pink-900/20 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 bg-gradient-to-br from-gray-800 to-black rounded-lg flex items-center justify-center shadow-lg">
                                    <i class="fab fa-tiktok text-white"></i>
                                </div>
                                <h4 class="font-bold text-gray-700 dark:text-gray-300">TikTok Shop</h4>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ลงทะเบียนที่ <a href="https://partner.tiktokshop.com" target="_blank" class="underline hover:text-gray-800 dark:hover:text-gray-200 font-medium">TikTok Shop Partner</a>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50 space-y-4">
                    <button type="submit"
                            class="w-full px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        บันทึกบัญชี
                    </button>

                    <a href="{{ route('admin.marketplace.accounts.index') }}"
                       class="block w-full px-6 py-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all text-center font-medium">
                        <i class="fas fa-times mr-2"></i>
                        ยกเลิก
                    </a>
                </div>

                {{-- Help Tips --}}
                <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-lightbulb text-yellow-500"></i>
                        Tips
                    </h3>
                    <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                            <span>ตรวจสอบว่า API Key/Secret ถูกต้องก่อนบันทึก</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                            <span>เปิดใช้งาน Auto Sync เพื่อให้ข้อมูลอัปเดตอัตโนมัติ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                            <span>หลังบันทึกจะสามารถทดสอบการเชื่อมต่อได้</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
    }
    .dark .glass-card {
        background: rgba(31, 41, 55, 0.8);
    }
</style>
@endpush

@push('scripts')
<script>
    // Show/hide Shopee fields based on platform selection
    document.getElementById('platform_select').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const slug = selected.getAttribute('data-slug');
        const shopeeFields = document.getElementById('shopee-fields');

        if (slug === 'shopee') {
            shopeeFields.classList.remove('hidden');
        } else {
            shopeeFields.classList.add('hidden');
        }
    });
</script>
@endpush
@endsection

@extends('layouts.admin-v3')

@section('title', 'เพิ่มบัญชี Marketplace')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.marketplace.accounts.index') }}"
           class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">เพิ่มบัญชี Marketplace</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">เชื่อมต่อบัญชี Lazada, Shopee, TikTok Shop</p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.marketplace.accounts.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="glass-card p-6 rounded-xl space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Platform <span class="text-red-500">*</span>
                            </label>
                            <select name="platform_id" required
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    x-data="{ platform: '' }"
                                    x-model="platform"
                                    @change="$dispatch('platform-changed', { platform: platform })">
                                <option value="">เลือก Platform</option>
                                @foreach($platforms as $platform)
                                    <option value="{{ $platform->id }}" data-slug="{{ $platform->slug }}">
                                        {{ $platform->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('platform_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ชื่อบัญชี <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="account_name" value="{{ old('account_name') }}" required
                                   placeholder="เช่น Lazada Main Account"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            @error('account_name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Shop ID
                            </label>
                            <input type="text" name="shop_id" value="{{ old('shop_id') }}"
                                   placeholder="ID ร้านค้าจาก Platform"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ชื่อร้านค้า
                            </label>
                            <input type="text" name="shop_name" value="{{ old('shop_name') }}"
                                   placeholder="ชื่อร้านค้า"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ผู้ใช้เจ้าของบัญชี
                            </label>
                            <select name="user_id"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">ไม่ระบุ (Admin)</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                อัตราคอมมิชชั่น (%)
                            </label>
                            <input type="number" name="commission_rate" value="{{ old('commission_rate', 5) }}"
                                   min="0" max="100" step="0.01"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                {{-- API Credentials --}}
                <div class="glass-card p-6 rounded-xl space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">API Credentials</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ข้อมูล API จาก Developer Portal ของแต่ละ Platform</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                App Key <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="app_key" value="{{ old('app_key') }}" required
                                   placeholder="API App Key"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                            @error('app_key')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                App Secret <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="app_secret" value="{{ old('app_secret') }}" required
                                   placeholder="API App Secret"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                            @error('app_secret')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Access Token
                            </label>
                            <input type="text" name="access_token" value="{{ old('access_token') }}"
                                   placeholder="OAuth Access Token (ถ้ามี)"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Refresh Token
                            </label>
                            <input type="text" name="refresh_token" value="{{ old('refresh_token') }}"
                                   placeholder="OAuth Refresh Token (ถ้ามี)"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        </div>
                    </div>

                    {{-- Shopee Additional Fields --}}
                    <div id="shopee-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Partner ID (Shopee)
                            </label>
                            <input type="text" name="partner_id" value="{{ old('partner_id') }}"
                                   placeholder="Shopee Partner ID"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Partner Key (Shopee)
                            </label>
                            <input type="password" name="partner_key" value="{{ old('partner_key') }}"
                                   placeholder="Shopee Partner Key"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        </div>
                    </div>
                </div>

                {{-- Sync Settings --}}
                <div class="glass-card p-6 rounded-xl space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตั้งค่า Sync</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="auto_sync_products" value="1" id="auto_sync_products"
                                   {{ old('auto_sync_products', true) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="auto_sync_products" class="text-gray-700 dark:text-gray-300">
                                Sync สินค้าอัตโนมัติ
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="auto_sync_orders" value="1" id="auto_sync_orders"
                                   {{ old('auto_sync_orders', true) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="auto_sync_orders" class="text-gray-700 dark:text-gray-300">
                                Sync ออเดอร์อัตโนมัติ
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ความถี่ Sync
                            </label>
                            <select name="sync_frequency"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
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
                <div class="glass-card p-6 rounded-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">คู่มือ Platform</h3>

                    <div class="space-y-4">
                        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <h4 class="font-medium text-orange-700 dark:text-orange-400">Lazada</h4>
                            <p class="text-sm text-orange-600 dark:text-orange-300 mt-1">
                                ลงทะเบียนที่ <a href="https://open.lazada.com" target="_blank" class="underline">Lazada Open Platform</a>
                            </p>
                        </div>

                        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <h4 class="font-medium text-red-700 dark:text-red-400">Shopee</h4>
                            <p class="text-sm text-red-600 dark:text-red-300 mt-1">
                                ลงทะเบียนที่ <a href="https://open.shopee.com" target="_blank" class="underline">Shopee Open Platform</a>
                            </p>
                        </div>

                        <div class="p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                            <h4 class="font-medium text-pink-700 dark:text-pink-400">TikTok Shop</h4>
                            <p class="text-sm text-pink-600 dark:text-pink-300 mt-1">
                                ลงทะเบียนที่ <a href="https://partner.tiktokshop.com" target="_blank" class="underline">TikTok Shop Partner</a>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="glass-card p-6 rounded-xl space-y-4">
                    <button type="submit"
                            class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:shadow-lg transition font-medium">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกบัญชี
                    </button>

                    <a href="{{ route('admin.marketplace.accounts.index') }}"
                       class="block w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-center">
                        ยกเลิก
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Show/hide Shopee fields based on platform selection
    document.querySelector('select[name="platform_id"]').addEventListener('change', function() {
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

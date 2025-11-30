@extends('layouts.admin-v3')

@section('title', 'แก้ไขบัญชี - ' . $account->account_name)

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.marketplace.accounts.show', $account) }}"
           class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขบัญชี Marketplace</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $account->account_name }}</p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.marketplace.accounts.update', $account) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="glass-card p-6 rounded-xl space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Platform</label>
                            <input type="text" readonly
                                   value="{{ $account->platform->name ?? '-' }}"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ชื่อบัญชี <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="account_name" value="{{ old('account_name', $account->account_name) }}" required
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shop ID</label>
                            <input type="text" name="shop_id" value="{{ old('shop_id', $account->shop_id) }}"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อร้านค้า</label>
                            <input type="text" name="shop_name" value="{{ old('shop_name', $account->shop_name) }}"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ผู้ใช้เจ้าของบัญชี</label>
                            <select name="user_id"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">ไม่ระบุ (Admin)</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ $account->user_id == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อัตราคอมมิชชั่น (%)</label>
                            <input type="number" name="commission_rate" value="{{ old('commission_rate', $account->commission_rate) }}"
                                   min="0" max="100" step="0.01"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                            <select name="status"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="active" {{ $account->status == 'active' ? 'selected' : '' }}>ใช้งาน</option>
                                <option value="inactive" {{ $account->status == 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                                <option value="pending" {{ $account->status == 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
                                <option value="suspended" {{ $account->status == 'suspended' ? 'selected' : '' }}>ระงับ</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- API Credentials --}}
                <div class="glass-card p-6 rounded-xl space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">API Credentials</h3>
                    <p class="text-sm text-amber-600 dark:text-amber-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        เว้นว่างไว้หากไม่ต้องการเปลี่ยนแปลง
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">App Key</label>
                            <input type="text" name="app_key" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">App Secret</label>
                            <input type="password" name="app_secret" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Access Token</label>
                            <input type="text" name="access_token" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Refresh Token</label>
                            <input type="text" name="refresh_token" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        </div>
                    </div>

                    @if($account->platform && $account->platform->slug == 'shopee')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Partner ID (Shopee)</label>
                                <input type="text" name="partner_id"
                                       value="{{ $account->additional_credentials['partner_id'] ?? '' }}"
                                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Partner Key (Shopee)</label>
                                <input type="password" name="partner_key" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Sync Settings --}}
                <div class="glass-card p-6 rounded-xl space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตั้งค่า Sync</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="auto_sync_products" value="1" id="auto_sync_products"
                                   {{ $account->auto_sync_products ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="auto_sync_products" class="text-gray-700 dark:text-gray-300">
                                Sync สินค้าอัตโนมัติ
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="auto_sync_orders" value="1" id="auto_sync_orders"
                                   {{ $account->auto_sync_orders ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="auto_sync_orders" class="text-gray-700 dark:text-gray-300">
                                Sync ออเดอร์อัตโนมัติ
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความถี่ Sync</label>
                            <select name="sync_frequency"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="hourly" {{ $account->sync_frequency == 'hourly' ? 'selected' : '' }}>ทุกชั่วโมง</option>
                                <option value="daily" {{ $account->sync_frequency == 'daily' ? 'selected' : '' }}>ทุกวัน</option>
                                <option value="weekly" {{ $account->sync_frequency == 'weekly' ? 'selected' : '' }}>ทุกสัปดาห์</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Actions --}}
                <div class="glass-card p-6 rounded-xl space-y-4">
                    <button type="submit"
                            class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:shadow-lg transition font-medium">
                        <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>

                    <a href="{{ route('admin.marketplace.accounts.show', $account) }}"
                       class="block w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-center">
                        ยกเลิก
                    </a>
                </div>

                {{-- Info --}}
                <div class="glass-card p-6 rounded-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลระบบ</h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</p>
                            <p class="text-gray-900 dark:text-white">{{ $account->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">แก้ไขล่าสุด</p>
                            <p class="text-gray-900 dark:text-white">{{ $account->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Sync ล่าสุด</p>
                            <p class="text-gray-900 dark:text-white">{{ $account->last_sync_at ? $account->last_sync_at->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@extends('layouts.admin-v3')

@section('title', 'แก้ไขบัญชี - ' . $account->account_name)

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .float-animation {
        animation: float 6s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-yellow-400/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>

        {{-- Floating Icon --}}
        <div class="absolute right-8 top-1/2 -translate-y-1/2 hidden lg:block">
            <div class="w-32 h-32 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center float-animation">
                <i class="fas fa-edit text-5xl text-white/80"></i>
            </div>
        </div>

        <div class="relative">
            {{-- Back Button --}}
            <a href="{{ route('admin.marketplace.accounts.show', $account) }}"
               class="inline-flex items-center gap-2 text-white/80 hover:text-white transition mb-4">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปรายละเอียด</span>
            </a>

            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-xl font-bold shadow-lg">
                    {{ strtoupper(substr($account->account_name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">แก้ไขบัญชี Marketplace</h1>
                    <p class="text-white/80 mt-1">{{ $account->account_name }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.marketplace.accounts.update', $account) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info Card --}}
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-500"></i>
                            ข้อมูลพื้นฐาน
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platform</label>
                                <div class="relative">
                                    <input type="text" readonly
                                           value="{{ $account->platform->name ?? '-' }}"
                                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white cursor-not-allowed">
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">ไม่สามารถเปลี่ยน Platform ได้</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ชื่อบัญชี <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="account_name" value="{{ old('account_name', $account->account_name) }}" required
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                                       placeholder="ชื่อที่จะแสดงในระบบ">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shop ID</label>
                                <input type="text" name="shop_id" value="{{ old('shop_id', $account->shop_id) }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition font-mono"
                                       placeholder="ID ร้านค้าจาก Platform">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อร้านค้า</label>
                                <input type="text" name="shop_name" value="{{ old('shop_name', $account->shop_name) }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                                       placeholder="ชื่อร้านค้าบน Platform">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ผู้ใช้เจ้าของบัญชี</label>
                                <select name="user_id"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition">
                                    <option value="">ไม่ระบุ (Admin)</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $account->user_id == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    อัตราคอมมิชชั่น (%)
                                </label>
                                <div class="relative">
                                    <input type="number" name="commission_rate" value="{{ old('commission_rate', $account->commission_rate) }}"
                                           min="0" max="100" step="0.01"
                                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                                           placeholder="0.00">
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <span class="text-gray-400">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    @php
                                        $statuses = [
                                            'active' => ['label' => 'ใช้งาน', 'color' => 'emerald', 'icon' => 'fa-check-circle'],
                                            'inactive' => ['label' => 'ไม่ใช้งาน', 'color' => 'gray', 'icon' => 'fa-pause-circle'],
                                            'pending' => ['label' => 'รอตรวจสอบ', 'color' => 'amber', 'icon' => 'fa-clock'],
                                            'suspended' => ['label' => 'ระงับ', 'color' => 'red', 'icon' => 'fa-ban'],
                                        ];
                                    @endphp
                                    @foreach($statuses as $value => $config)
                                        <label class="relative cursor-pointer">
                                            <input type="radio" name="status" value="{{ $value }}"
                                                   {{ $account->status == $value ? 'checked' : '' }}
                                                   class="peer sr-only">
                                            <div class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 peer-checked:border-{{ $config['color'] }}-500 peer-checked:bg-{{ $config['color'] }}-50 dark:peer-checked:bg-{{ $config['color'] }}-900/30 transition-all hover:border-gray-300 dark:hover:border-gray-600">
                                                <div class="flex flex-col items-center gap-2 text-center">
                                                    <i class="fas {{ $config['icon'] }} text-xl text-{{ $config['color'] }}-500"></i>
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $config['label'] }}</span>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- API Credentials Card --}}
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-key text-purple-500"></i>
                            API Credentials
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/30 rounded-xl mb-6">
                            <i class="fas fa-info-circle text-amber-500 text-lg"></i>
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                เว้นว่างไว้หากไม่ต้องการเปลี่ยนแปลงค่าปัจจุบัน
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">App Key</label>
                                <input type="text" name="app_key" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition font-mono text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">App Secret</label>
                                <input type="password" name="app_secret" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition font-mono text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Access Token</label>
                                <input type="text" name="access_token" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition font-mono text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Refresh Token</label>
                                <input type="text" name="refresh_token" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition font-mono text-sm">
                            </div>
                        </div>

                        @if($account->platform && $account->platform->slug == 'shopee')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center gap-2">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fe/Shopee.svg/1200px-Shopee.svg.png"
                                                 class="w-5 h-5" alt="Shopee">
                                            Partner ID (Shopee)
                                        </span>
                                    </label>
                                    <input type="text" name="partner_id"
                                           value="{{ $account->additional_credentials['partner_id'] ?? '' }}"
                                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition font-mono text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Partner Key (Shopee)</label>
                                    <input type="password" name="partner_key" placeholder="เว้นว่างหากไม่เปลี่ยน"
                                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition font-mono text-sm">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Sync Settings Card --}}
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-cyan-500/10 to-blue-500/10 dark:from-cyan-500/20 dark:to-blue-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-sync text-cyan-500"></i>
                            ตั้งค่า Sync
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <div class="relative">
                                    <input type="checkbox" name="auto_sync_products" value="1" id="auto_sync_products"
                                           {{ $account->auto_sync_products ? 'checked' : '' }}
                                           class="peer sr-only">
                                    <div class="w-12 h-7 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow-md peer-checked:translate-x-5 transition-transform"></div>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300">Sync สินค้าอัตโนมัติ</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">ดึงข้อมูลสินค้าจาก Platform โดยอัตโนมัติ</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <div class="relative">
                                    <input type="checkbox" name="auto_sync_orders" value="1" id="auto_sync_orders"
                                           {{ $account->auto_sync_orders ? 'checked' : '' }}
                                           class="peer sr-only">
                                    <div class="w-12 h-7 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow-md peer-checked:translate-x-5 transition-transform"></div>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300">Sync ออเดอร์อัตโนมัติ</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">ดึงข้อมูลออเดอร์จาก Platform โดยอัตโนมัติ</p>
                                </div>
                            </label>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความถี่ Sync</label>
                                <div class="grid grid-cols-3 gap-3">
                                    @php
                                        $frequencies = [
                                            'hourly' => ['label' => 'ทุกชั่วโมง', 'icon' => 'fa-clock', 'desc' => 'แนะนำ'],
                                            'daily' => ['label' => 'ทุกวัน', 'icon' => 'fa-calendar-day', 'desc' => ''],
                                            'weekly' => ['label' => 'ทุกสัปดาห์', 'icon' => 'fa-calendar-week', 'desc' => ''],
                                        ];
                                    @endphp
                                    @foreach($frequencies as $value => $config)
                                        <label class="relative cursor-pointer">
                                            <input type="radio" name="sync_frequency" value="{{ $value }}"
                                                   {{ $account->sync_frequency == $value ? 'checked' : '' }}
                                                   class="peer sr-only">
                                            <div class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 peer-checked:border-cyan-500 peer-checked:bg-cyan-50 dark:peer-checked:bg-cyan-900/30 transition-all hover:border-gray-300 dark:hover:border-gray-600">
                                                <div class="flex flex-col items-center gap-2 text-center">
                                                    <i class="fas {{ $config['icon'] }} text-xl text-cyan-500"></i>
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $config['label'] }}</span>
                                                    @if($config['desc'])
                                                        <span class="text-xs text-emerald-500">{{ $config['desc'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Actions Card --}}
                <div class="glass-card rounded-2xl overflow-hidden sticky top-6">
                    <div class="bg-gradient-to-r from-amber-500/10 to-orange-500/10 dark:from-amber-500/20 dark:to-orange-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การดำเนินการ</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <button type="submit"
                                class="w-full px-4 py-4 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 text-white rounded-xl hover:shadow-lg hover:shadow-orange-500/25 transition-all font-medium flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            <span>บันทึกการเปลี่ยนแปลง</span>
                        </button>

                        <a href="{{ route('admin.marketplace.accounts.show', $account) }}"
                           class="block w-full px-4 py-4 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-center">
                            ยกเลิก
                        </a>
                    </div>
                </div>

                {{-- System Info Card --}}
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-500/10 to-slate-500/10 dark:from-gray-500/20 dark:to-slate-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-server text-gray-500"></i>
                            ข้อมูลระบบ
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">สร้างเมื่อ</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $account->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">แก้ไขล่าสุด</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $account->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Sync ล่าสุด</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $account->last_sync_at ? $account->last_sync_at->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

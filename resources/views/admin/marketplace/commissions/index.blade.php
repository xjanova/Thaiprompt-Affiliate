@extends('layouts.admin-v3')

@section('title', 'คอมมิชชั่น Marketplace')

@section('content')
@php
    // 🚨 ด่านตรวจ "ลาซาด้าเคลียร์เงินให้เราหรือยัง" — ใช้ตัวเดียวกับที่ Controller ใช้ตอนกดปุ่ม
    //    หน้าจอกับหลังบ้านจึงตัดสินตรงกันเสมอ (ปุ่มที่กดไม่ได้ = หลังบ้านก็ปฏิเสธ)
    $settlementGuard = new \App\Services\Marketplace\AffiliateSettlementGuard;
@endphp
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-600 dark:from-emerald-700 dark:via-teal-700 dark:to-cyan-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-coins"></i>
            </div>
            <div class="absolute text-white/10 text-6xl bottom-10 right-40" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-3">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-coins text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">คอมมิชชั่น Marketplace Affiliate</h1>
                    <p class="text-emerald-100 text-lg mt-1">จัดการคอมมิชชั่นจาก Lazada, Shopee, TikTok Shop</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-list text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['total_commissions'] ?? 0) }}</p>
                <p class="text-sm opacity-90">รายการทั้งหมด</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-clock text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['pending_commissions'] ?? 0) }}</p>
                <p class="text-sm opacity-90">รออนุมัติ</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-400 to-cyan-600 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-check text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['approved_commissions'] ?? 0) }}</p>
                <p class="text-sm opacity-90">อนุมัติแล้ว</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-check-double text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['paid_commissions'] ?? 0) }}</p>
                <p class="text-sm opacity-90">จ่ายแล้ว</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-hourglass-half text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-2xl font-bold mb-1">฿{{ number_format($stats['total_pending_amount'] ?? 0, 0) }}</p>
                <p class="text-sm opacity-90">รอจ่าย</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-wallet text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-2xl font-bold mb-1">฿{{ number_format($stats['total_paid_amount'] ?? 0, 0) }}</p>
                <p class="text-sm opacity-90">จ่ายไปแล้ว</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-emerald-600 dark:text-emerald-400"></i>
            ตัวกรองข้อมูล
        </h3>

        <form method="GET" action="{{ route('admin.marketplace.commissions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหาชื่อผู้ใช้..."
                           class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            <div>
                <select name="platform" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all">
                    <option value="">ทุก Platform</option>
                    @foreach($platforms ?? [] as $platform)
                        <option value="{{ $platform->id }}" {{ request('platform') == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รออนุมัติ</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>

            <div>
                <select name="type" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all">
                    <option value="">ทุกประเภท</option>
                    <option value="direct" {{ request('type') == 'direct' ? 'selected' : '' }}>Direct</option>
                    <option value="mlm" {{ request('type') == 'mlm' ? 'selected' : '' }}>MLM</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.marketplace.commissions.index') }}" class="px-4 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-xl transition-all">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Bulk Actions --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-4 border border-white/20 dark:border-gray-700/50">
        <div class="flex items-center gap-4 flex-wrap">
            <span class="text-gray-700 dark:text-gray-300 font-medium">
                <i class="fas fa-hand-pointer mr-1 text-emerald-600"></i>
                Bulk Actions:
            </span>
            <button type="button" onclick="bulkApprove()"
                    class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-md disabled:opacity-50"
                    id="bulk-approve-btn" disabled>
                <i class="fas fa-check mr-1"></i> อนุมัติที่เลือก
            </button>
            <button type="button" onclick="bulkPay()"
                    class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all shadow-md disabled:opacity-50"
                    id="bulk-pay-btn" disabled>
                <i class="fas fa-money-bill mr-1"></i> จ่ายที่เลือก
            </button>
            <span id="selected-count" class="text-sm text-gray-500 dark:text-gray-400 ml-auto">
                เลือก 0 รายการ
            </span>
        </div>

        {{-- 🚨 คำเตือนกฎการจ่ายเงิน — ให้แอดมินเห็นก่อนกดทุกครั้ง --}}
        <div class="mt-4 flex items-start gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/60">
            <i class="fas fa-shield-halved text-amber-500 mt-0.5"></i>
            <div class="text-sm text-amber-800 dark:text-amber-200 leading-relaxed">
                <p class="font-semibold">กฎการจ่ายค่าคอม Lazada</p>
                <p>
                    จ่ายได้เฉพาะรายการที่ขึ้นว่า
                    <span class="font-semibold">“ลาซาด้ายืนยันแล้ว”</span> เท่านั้น —
                    รายการที่ขึ้นว่า <span class="font-semibold">“รอลาซาด้ายืนยัน”</span>
                    แปลว่าลาซาด้ายังไม่โอนค่าคอมงวดนั้นมาให้เรา ปุ่มจึงถูกปิดไว้
                    และจะถูกข้ามอัตโนมัติเมื่อกดแบบกลุ่ม
                </p>
            </div>
        </div>
    </div>

    {{-- Commissions Table --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-list text-emerald-600 dark:text-emerald-400"></i>
                    รายการคอมมิชชั่น
                </h3>
                <span class="px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-xl text-sm font-semibold">
                    {{ number_format(($commissions ?? collect())->total()) }} รายการ
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left">
                            <input type="checkbox" id="select-all"
                                   class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-user mr-1 text-blue-500"></i> ผู้รับ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-receipt mr-1 text-orange-500"></i> ออเดอร์
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-tag mr-1 text-purple-500"></i> ประเภท
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-coins mr-1 text-yellow-500"></i> จำนวน
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-info-circle mr-1 text-indigo-500"></i> สถานะ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase whitespace-nowrap">
                            <i class="fas fa-shield-halved mr-1 text-amber-500"></i> ลาซาด้ายืนยัน
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-calendar mr-1 text-gray-500"></i> วันที่
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-cogs mr-1 text-cyan-500"></i> จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($commissions ?? [] as $commission)
                        @php
                            // ผลด่านตรวจของแถวนี้ (settled | warn | blocked | na)
                            $settle = $settlementGuard->settlementBadge($commission);
                            $settleTone = [
                                'settled' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                'warn' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                'blocked' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                'na' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            ][$settle['state']] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
                            $settleIcon = [
                                'settled' => 'fa-circle-check',
                                'warn' => 'fa-triangle-exclamation',
                                'blocked' => 'fa-lock',
                                'na' => 'fa-minus',
                            ][$settle['state']] ?? 'fa-minus';
                        @endphp
                        <tr class="hover:bg-gradient-to-r hover:from-emerald-50/50 hover:to-transparent dark:hover:from-gray-700/50 transition-all duration-200 {{ $settle['blocked'] ? 'opacity-70' : '' }}">
                            <td class="px-6 py-4">
                                {{-- แถวที่ด่านตรวจไม่ผ่าน = เลือกไม่ได้ กันไปโผล่ในคำสั่งแบบกลุ่ม --}}
                                <input type="checkbox"
                                       class="commission-checkbox w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:opacity-40"
                                       value="{{ $commission->id }}"
                                       data-status="{{ $commission->status }}"
                                       data-blocked="{{ $settle['blocked'] ? '1' : '0' }}"
                                       @if($settle['blocked']) disabled title="{{ $settle['tooltip'] }}" @endif>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md">
                                        {{ strtoupper(substr($commission->user->name ?? 'N', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $commission->user->name ?? '-' }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $commission->user->email ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.marketplace.orders.show', $commission->order_id) }}"
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium hover:underline">
                                    {{ $commission->order->external_order_id ?? '-' }}
                                </a>
                                @if($commission->order && $commission->order->platform)
                                    @php
                                        $pColors = [
                                            'lazada' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'shopee' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                            'tiktok' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
                                        ];
                                    @endphp
                                    <span class="ml-2 px-2 py-0.5 rounded-lg text-xs font-semibold {{ $pColors[$commission->order->platform->slug ?? ''] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $commission->order->platform->name }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $typeConfig = [
                                        'direct' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400'],
                                        'mlm' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-400'],
                                    ];
                                    $tConfig = $typeConfig[$commission->commission_type] ?? $typeConfig['direct'];
                                @endphp
                                <span class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ $tConfig['bg'] }} {{ $tConfig['text'] }}">
                                    {{ $commission->commission_type }}
                                    @if($commission->mlm_level)
                                        <span class="ml-1 px-1.5 py-0.5 bg-white/50 rounded-lg">L{{ $commission->mlm_level }}</span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-lg font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                                    ฿{{ number_format($commission->commission_amount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusConfig = [
                                        'pending' => ['gradient' => 'from-yellow-400 to-orange-500', 'icon' => 'fa-clock', 'label' => 'รออนุมัติ'],
                                        'approved' => ['gradient' => 'from-blue-400 to-blue-600', 'icon' => 'fa-check', 'label' => 'อนุมัติแล้ว'],
                                        'paid' => ['gradient' => 'from-green-400 to-emerald-600', 'icon' => 'fa-check-double', 'label' => 'จ่ายแล้ว'],
                                        'rejected' => ['gradient' => 'from-red-400 to-red-600', 'icon' => 'fa-times', 'label' => 'ปฏิเสธ'],
                                    ];
                                    $sConfig = $statusConfig[$commission->status] ?? $statusConfig['pending'];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r {{ $sConfig['gradient'] }} text-white shadow-md">
                                    <i class="fas {{ $sConfig['icon'] }}"></i>
                                    {{ $sConfig['label'] }}
                                </span>
                            </td>
                            {{-- สถานะการเคลียร์เงินจากลาซาด้า — ตัวชี้ขาดว่าจ่ายได้หรือยัง --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $settleTone }}"
                                      title="{{ $settle['tooltip'] }}">
                                    <i class="fas {{ $settleIcon }}"></i>
                                    {{ $settle['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $commission->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    @if($commission->status === 'pending')
                                        @if($settle['blocked'])
                                            {{-- ด่านตรวจไม่ผ่าน = ปุ่มต้องกดไม่ได้ (หลังบ้านก็ปฏิเสธเช่นกัน) --}}
                                            <button type="button" disabled
                                                    class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 flex items-center justify-center cursor-not-allowed"
                                                    title="อนุมัติไม่ได้: {{ $settle['tooltip'] }}">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @else
                                            <button type="button"
                                                    onclick="approveCommission({{ $commission->id }})"
                                                    class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-200 dark:hover:bg-blue-800 transition-all hover:scale-110"
                                                    title="อนุมัติ">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
                                    @endif
                                    @if($commission->status === 'approved')
                                        @if($settle['blocked'])
                                            <button type="button" disabled
                                                    class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 flex items-center justify-center cursor-not-allowed"
                                                    title="จ่ายไม่ได้: {{ $settle['tooltip'] }}">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @else
                                            <button type="button"
                                                    onclick="payCommission({{ $commission->id }})"
                                                    class="w-9 h-9 rounded-xl bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 flex items-center justify-center hover:bg-green-200 dark:hover:bg-green-800 transition-all hover:scale-110"
                                                    title="จ่ายเงิน">
                                                <i class="fas fa-money-bill"></i>
                                            </button>
                                        @endif
                                    @endif
                                    <a href="{{ route('admin.marketplace.commissions.show', $commission) }}"
                                       class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-110"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-full flex items-center justify-center mb-6">
                                        <i class="fas fa-coins text-5xl text-emerald-500"></i>
                                    </div>
                                    <h4 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-3">ยังไม่มีคอมมิชชั่น</h4>
                                    <p class="text-gray-500 dark:text-gray-400">คอมมิชชั่นจะปรากฏเมื่อมีออเดอร์สำเร็จ</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(($commissions ?? collect())->hasPages())
            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                {{ $commissions->links() }}
            </div>
        @endif
    </div>
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
    // 🚨 แถวที่ด่านตรวจไม่ผ่านจะถูก disabled ไว้ตั้งแต่ฝั่ง Blade
    //    ทุกที่ที่อ่านรายการที่เลือก ต้องใช้ :not(:disabled) เสมอ
    //    (input ที่ disabled ยังถูกตั้ง .checked = true ได้ด้วย JS → ถ้าไม่กรอง จะหลุดไปกับคำสั่งกลุ่ม)

    // Select All Checkbox
    document.getElementById('select-all').addEventListener('change', function() {
        document.querySelectorAll('.commission-checkbox:not(:disabled)').forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkButtons();
    });

    // Individual Checkboxes
    document.querySelectorAll('.commission-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkButtons);
    });

    function updateBulkButtons() {
        const checked = document.querySelectorAll('.commission-checkbox:checked:not(:disabled)');
        const pendingCount = Array.from(checked).filter(cb => cb.dataset.status === 'pending').length;
        const approvedCount = Array.from(checked).filter(cb => cb.dataset.status === 'approved').length;

        document.getElementById('bulk-approve-btn').disabled = pendingCount === 0;
        document.getElementById('bulk-pay-btn').disabled = approvedCount === 0;
        document.getElementById('selected-count').textContent = `เลือก ${checked.length} รายการ`;
    }

    function getSelectedIds(status = null) {
        const checkboxes = document.querySelectorAll('.commission-checkbox:checked:not(:disabled)');
        if (status) {
            return Array.from(checkboxes)
                .filter(cb => cb.dataset.status === status)
                .map(cb => cb.value);
        }
        return Array.from(checkboxes).map(cb => cb.value);
    }

    function approveCommission(id) {
        if (!confirm('ต้องการอนุมัติคอมมิชชั่นนี้?')) return;
        processAction(`{{ url('admin/marketplace/commissions') }}/${id}/approve`);
    }

    function payCommission(id) {
        if (!confirm('ต้องการจ่ายคอมมิชชั่นนี้?')) return;
        processAction(`{{ url('admin/marketplace/commissions') }}/${id}/pay`);
    }

    function bulkApprove() {
        const ids = getSelectedIds('pending');
        if (ids.length === 0) {
            showToast('error', 'กรุณาเลือกรายการที่รออนุมัติ');
            return;
        }
        if (!confirm(`ต้องการอนุมัติ ${ids.length} รายการ?`)) return;

        fetch(`{{ route('admin.marketplace.commissions.bulk-approve') }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids })
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.success ? 'success' : 'error', data.message);
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('error', 'เกิดข้อผิดพลาด: ' + error.message));
    }

    function bulkPay() {
        const ids = getSelectedIds('approved');
        if (ids.length === 0) {
            showToast('error', 'กรุณาเลือกรายการที่อนุมัติแล้ว');
            return;
        }
        if (!confirm(`ต้องการจ่าย ${ids.length} รายการ?`)) return;

        fetch(`{{ route('admin.marketplace.commissions.bulk-pay') }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids })
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.success ? 'success' : 'error', data.message);
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('error', 'เกิดข้อผิดพลาด: ' + error.message));
    }

    function processAction(url) {
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.success ? 'success' : 'error', data.message);
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('error', 'เกิดข้อผิดพลาด: ' + error.message));
    }

    function showToast(type, message) {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'from-green-500 to-emerald-600' : 'from-red-500 to-red-600';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        toast.className = `fixed top-4 right-4 z-50 px-6 py-4 bg-gradient-to-r ${bgColor} text-white rounded-xl shadow-2xl transform transition-all duration-300 flex items-center gap-3`;
        toast.innerHTML = `<i class="fas ${icon} text-xl"></i><span>${message}</span>`;

        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>
@endpush
@endsection

@extends('layouts.admin-v3')

@section('title', 'กระเป๋าเงินแพลตฟอร์ม')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-emerald-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-wallet text-emerald-400"></i>
                        กระเป๋าเงินแพลตฟอร์ม
                    </h1>
                    <p class="text-white/60">จัดการกระเป๋าเงินพิเศษของ Platform - Fee, VAT, MLM Pool และอื่นๆ</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.platform-revenue.index') }}"
                       class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white hover:bg-white/20 transition">
                        <i class="fas fa-chart-pie mr-2"></i>Dashboard
                    </a>
                    <a href="{{ route('admin.platform-revenue.transactions') }}"
                       class="px-4 py-2 bg-emerald-500 rounded-xl text-white hover:bg-emerald-600 transition">
                        <i class="fas fa-list mr-2"></i>Transactions
                    </a>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 backdrop-blur-lg rounded-2xl p-6 border border-emerald-400/30">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-emerald-500/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-wallet text-emerald-400"></i>
                    </div>
                    <span class="text-white/60 text-sm">ยอดรวมทั้งหมด</span>
                </div>
                <div class="text-3xl font-bold text-white">฿{{ number_format($totals['total_balance'], 2) }}</div>
            </div>

            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-green-500/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-arrow-up text-green-400"></i>
                    </div>
                    <span class="text-white/60 text-sm">รายได้วันนี้</span>
                </div>
                <div class="text-2xl font-bold text-green-400">+฿{{ number_format($totals['today_income'], 2) }}</div>
            </div>

            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-red-500/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-arrow-down text-red-400"></i>
                    </div>
                    <span class="text-white/60 text-sm">จ่ายออกวันนี้</span>
                </div>
                <div class="text-2xl font-bold text-red-400">-฿{{ number_format($totals['today_expense'], 2) }}</div>
            </div>

            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-blue-500/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-blue-400"></i>
                    </div>
                    <span class="text-white/60 text-sm">รายได้เดือนนี้</span>
                </div>
                <div class="text-2xl font-bold text-blue-400">+฿{{ number_format($totals['month_income'], 2) }}</div>
            </div>

            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-orange-500/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-minus text-orange-400"></i>
                    </div>
                    <span class="text-white/60 text-sm">จ่ายออกเดือนนี้</span>
                </div>
                <div class="text-2xl font-bold text-orange-400">-฿{{ number_format($totals['month_expense'], 2) }}</div>
            </div>
        </div>

        {{-- Wallet Cards --}}
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-piggy-bank text-emerald-400"></i>
                รายการกระเป๋าเงินทั้งหมด
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($wallets as $wallet)
            @php
                // กำหนดสีตาม wallet type
                $colors = [
                    'fee' => ['bg' => 'from-blue-400 to-cyan-500', 'border' => 'blue', 'icon' => 'fa-coins'],
                    'vat' => ['bg' => 'from-green-400 to-emerald-500', 'border' => 'green', 'icon' => 'fa-receipt'],
                    'mlm_pool' => ['bg' => 'from-purple-400 to-pink-500', 'border' => 'purple', 'icon' => 'fa-users'],
                    'admin_shop' => ['bg' => 'from-yellow-400 to-orange-500', 'border' => 'yellow', 'icon' => 'fa-store'],
                    'admin_services' => ['bg' => 'from-indigo-400 to-violet-500', 'border' => 'indigo', 'icon' => 'fa-concierge-bell'],
                    'refund_pool' => ['bg' => 'from-red-400 to-rose-500', 'border' => 'red', 'icon' => 'fa-undo'],
                ];
                $color = $colors[$wallet->slug] ?? ['bg' => 'from-gray-400 to-gray-500', 'border' => 'gray', 'icon' => 'fa-wallet'];
            @endphp
            <a href="{{ route('admin.platform-revenue.wallets.show', $wallet) }}"
               class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-{{ $color['border'] }}-400/50 transition-all duration-300 group hover:scale-[1.02]">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br {{ $color['bg'] }} rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas {{ $color['icon'] }} text-white text-2xl"></i>
                    </div>
                    <i class="fas fa-arrow-right text-white/30 group-hover:text-white/60 group-hover:translate-x-1 transition-all"></i>
                </div>

                {{-- Wallet Name & Balance --}}
                <div class="mb-4">
                    <div class="text-white/60 text-sm mb-1">{{ $wallet->name }}</div>
                    <div class="text-3xl font-bold text-white">฿{{ number_format($wallet->balance, 2) }}</div>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/10">
                    <div>
                        <div class="text-white/40 text-xs mb-1">รายได้วันนี้</div>
                        <div class="text-green-400 font-semibold">+฿{{ number_format($wallet->today_income, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-white/40 text-xs mb-1">จ่ายออกวันนี้</div>
                        <div class="text-red-400 font-semibold">-฿{{ number_format($wallet->today_expense, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-white/40 text-xs mb-1">รายได้เดือนนี้</div>
                        <div class="text-blue-400 font-semibold">+฿{{ number_format($wallet->month_income, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-white/40 text-xs mb-1">จ่ายออกเดือนนี้</div>
                        <div class="text-orange-400 font-semibold">-฿{{ number_format($wallet->month_expense, 2) }}</div>
                    </div>
                </div>

                {{-- Description --}}
                @if($wallet->description)
                <div class="mt-4 pt-4 border-t border-white/10">
                    <p class="text-white/50 text-sm">{{ $wallet->description }}</p>
                </div>
                @endif
            </a>
            @endforeach
        </div>

        {{-- Help Section --}}
        <div class="mt-8 bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-400"></i>
                คำอธิบายกระเป๋าเงินแต่ละประเภท
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-coins text-blue-400"></i>
                        <span class="text-white font-semibold">Platform Fee</span>
                    </div>
                    <p class="text-white/60 text-sm">ค่าธรรมเนียมที่แพลตฟอร์มเก็บจากทุกธุรกรรมการซื้อขาย</p>
                </div>
                <div class="p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-receipt text-green-400"></i>
                        <span class="text-white font-semibold">VAT</span>
                    </div>
                    <p class="text-white/60 text-sm">ภาษีมูลค่าเพิ่มที่เก็บจากธุรกรรม สำหรับนำส่งสรรพากร</p>
                </div>
                <div class="p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-users text-purple-400"></i>
                        <span class="text-white font-semibold">MLM Pool</span>
                    </div>
                    <p class="text-white/60 text-sm">กองทุนสำหรับจ่าย Commission ให้สมาชิก MLM</p>
                </div>
                <div class="p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-store text-yellow-400"></i>
                        <span class="text-white font-semibold">Admin Shop</span>
                    </div>
                    <p class="text-white/60 text-sm">รายได้จากสินค้าที่แพลตฟอร์มขายเอง</p>
                </div>
                <div class="p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-concierge-bell text-indigo-400"></i>
                        <span class="text-white font-semibold">Admin Services</span>
                    </div>
                    <p class="text-white/60 text-sm">รายได้จากบริการต่างๆ เช่น โฆษณา, Premium features</p>
                </div>
                <div class="p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-undo text-red-400"></i>
                        <span class="text-white font-semibold">Refund Pool</span>
                    </div>
                    <p class="text-white/60 text-sm">กองทุนสำรองสำหรับการคืนเงินลูกค้า</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

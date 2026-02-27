@extends('layouts.user-arrow-x')

@section('title', 'กระเป๋าเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Indigo-Purple-Pink for Wallet) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-800 dark:via-purple-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 mb-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-wallet text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">กระเป๋าเงินของฉัน</h1>
                        <p class="text-purple-100 text-lg mt-1">จัดการการเงินของคุณ</p>
                    </div>
                </div>

                {{-- Desktop Wallet Address --}}
                <div class="hidden md:block">
                    <p class="text-sm text-indigo-100 mb-1">Wallet Address</p>
                    <div class="flex items-center gap-2">
                        <code class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm font-mono text-white">{{ $wallet->wallet_address }}</code>
                        <button onclick="copyWalletAddress()" class="p-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg transition">
                            <i class="fas fa-copy text-white"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Balance Display --}}
            <div class="bg-white/20 dark:bg-white/10 backdrop-blur-xl border border-white/30 rounded-2xl p-6">
                <p class="text-indigo-100 text-sm mb-2">ยอดเงินคงเหลือ</p>
                <p class="text-5xl md:text-6xl font-bold text-white drop-shadow-lg">฿{{ number_format($wallet->balance, 2) }}</p>
                <p class="text-indigo-100 text-sm mt-2">{{ $wallet->currency }}</p>
            </div>

            {{-- Mobile Wallet Address --}}
            <div class="md:hidden mt-4">
                <p class="text-sm text-indigo-100 mb-1">Wallet Address</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 px-3 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-xs font-mono text-white">{{ $wallet->wallet_address }}</code>
                    <button onclick="copyWalletAddress()" class="p-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg transition">
                        <i class="fas fa-copy text-white"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('user.wallet.deposit') }}" class="group relative overflow-hidden rounded-2xl shadow-xl p-6 text-center transition-all duration-300 transform hover:scale-105 bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-800">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-arrow-down text-7xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-3">💵</div>
                <p class="text-white font-bold text-lg">ฝากเงิน</p>
                <p class="text-xs text-green-100 mt-1">Deposit</p>
            </div>
        </a>

        <a href="{{ route('user.wallet.withdraw') }}" class="group relative overflow-hidden rounded-2xl shadow-xl p-6 text-center transition-all duration-300 transform hover:scale-105 bg-gradient-to-br from-red-500 to-rose-600 dark:from-red-600 dark:to-rose-800">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-arrow-up text-7xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-3">💸</div>
                <p class="text-white font-bold text-lg">ถอนเงิน</p>
                <p class="text-xs text-red-100 mt-1">Withdraw</p>
            </div>
        </a>

        <a href="{{ route('user.wallet.transfer') }}" class="group relative overflow-hidden rounded-2xl shadow-xl p-6 text-center transition-all duration-300 transform hover:scale-105 bg-gradient-to-br from-blue-500 to-cyan-600 dark:from-blue-600 dark:to-cyan-800">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-exchange-alt text-7xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-3">📤</div>
                <p class="text-white font-bold text-lg">โอนเงิน</p>
                <p class="text-xs text-blue-100 mt-1">Transfer</p>
            </div>
        </a>

        <a href="{{ route('user.wallet.transactions') }}" class="group relative overflow-hidden rounded-2xl shadow-xl p-6 text-center transition-all duration-300 transform hover:scale-105 bg-gradient-to-br from-purple-500 to-indigo-600 dark:from-purple-600 dark:to-indigo-800">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-history text-7xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-3">📝</div>
                <p class="text-white font-bold text-lg">ประวัติ</p>
                <p class="text-xs text-purple-100 mt-1">History</p>
            </div>
        </a>
    </div>

    {{-- QR Code Actions --}}
    <div class="grid grid-cols-2 gap-4">
        <a href="{{ route('user.wallet.qr-code') }}" class="group relative overflow-hidden rounded-2xl shadow-xl p-6 text-center transition-all duration-300 transform hover:scale-105 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 dark:from-indigo-600 dark:via-purple-600 dark:to-pink-600">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-qrcode text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-3">
                    <i class="fas fa-qrcode text-white"></i>
                </div>
                <p class="text-white font-bold text-lg">QR Code ของฉัน</p>
                <p class="text-white/80 text-sm mt-1">แสดง QR เพื่อรับเงิน</p>
            </div>
        </a>

        <a href="{{ route('user.wallet.qr-transfer') }}" class="group relative overflow-hidden rounded-2xl shadow-xl p-6 text-center transition-all duration-300 transform hover:scale-105 bg-gradient-to-br from-cyan-500 via-blue-500 to-indigo-500 dark:from-cyan-600 dark:via-blue-600 dark:to-indigo-600">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-camera text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-3">
                    <i class="fas fa-camera text-white"></i>
                </div>
                <p class="text-white font-bold text-lg">สแกนจ่าย</p>
                <p class="text-white/80 text-sm mt-1">สแกน QR เพื่อโอนเงิน</p>
            </div>
        </a>
    </div>

    {{-- Debt Warning Card (แสดงเฉพาะเมื่อมีหนี้ active) --}}
    @if(isset($debtSummary) && $debtSummary['has_active_debt'])
    <a href="{{ route('user.wallet.debts') }}" class="block group">
        <div class="relative overflow-hidden bg-gradient-to-r from-red-500 via-orange-500 to-amber-500 dark:from-red-700 dark:via-orange-700 dark:to-amber-700 rounded-2xl shadow-xl p-6 text-white transition-all duration-300 group-hover:shadow-2xl group-hover:scale-[1.01]">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-exclamation-triangle text-9xl text-white"></i>
            </div>
            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl">
                        <i class="fas fa-file-invoice-dollar text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">คุณมีหนี้ค้างชำระ</h3>
                        <p class="text-white/80 text-sm">{{ $debtSummary['debt_count'] }} รายการ &middot; ระบบจะหักจากรายได้อัตโนมัติ 50%</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold">฿{{ number_format($debtSummary['total_debt'], 2) }}</p>
                    <p class="text-white/70 text-xs mt-1">ดูรายละเอียด <i class="fas fa-arrow-right ml-1"></i></p>
                </div>
            </div>
        </div>
    </a>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($wallet->total_income, 2)"
            label="รายรับทั้งหมด"
            icon="fas fa-arrow-down"
            gradient="from-green-500 to-emerald-600"
        />

        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($wallet->total_expense, 2)"
            label="รายจ่ายทั้งหมด"
            icon="fas fa-arrow-up"
            gradient="from-red-500 to-rose-600"
        />

        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($wallet->balance, 2)"
            label="ยอดเงินคงเหลือ"
            icon="fas fa-wallet"
            gradient="from-blue-500 to-indigo-600"
        />

        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($cashbackStats['total'] ?? 0, 2)"
            label="Cashback ทั้งหมด"
            subtitle="{{ ($cashbackStats['count'] ?? 0) }} ครั้ง"
            icon="fas fa-gift"
            gradient="from-amber-500 to-orange-600"
        />
    </div>

    {{-- Admin Adjustments Card --}}
    @if(isset($adminStats) && $adminStats['total'] > 0)
    <div class="bg-gradient-to-br from-purple-500 via-indigo-500 to-purple-600 dark:from-purple-600 dark:via-indigo-600 dark:to-purple-700 rounded-2xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <div class="text-5xl">⚡</div>
        </div>
        <p class="text-white/80 text-sm mb-1">เงินที่ได้จากแอดมิน/ระบบ</p>
        <p class="text-4xl md:text-5xl font-bold">฿{{ number_format($adminStats['total'] ?? 0, 2) }}</p>
        <p class="text-xs text-white/70 mt-2">{{ $adminStats['count'] ?? 0 }} ครั้ง</p>
        <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-white/20">
            <div>
                <p class="text-xs text-white/70 mb-1">เดือนนี้</p>
                <p class="text-lg font-bold">฿{{ number_format($adminStats['this_month'] ?? 0, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-white/70 mb-1">30 วันล่าสุด</p>
                <p class="text-lg font-bold">฿{{ number_format($adminStats['last_30_days'] ?? 0, 2) }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Cashback Details --}}
    @if(isset($cashbackStats) && $cashbackStats['total'] > 0)
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 backdrop-blur-sm border-2 border-green-200 dark:border-green-800 rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-green-800 dark:text-green-300 flex items-center gap-2">
                    <span class="text-2xl">💸</span>
                    สถิติ Cashback
                </h3>
                <p class="text-sm text-green-600 dark:text-green-400 mt-1">รายรับจากการคืนเงิน</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-green-200 dark:border-green-800">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">Cashback เดือนนี้</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($cashbackStats['this_month'] ?? 0, 2) }}</p>
            </div>

            <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-green-200 dark:border-green-800">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">30 วันล่าสุด</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($cashbackStats['last_30_days'] ?? 0, 2) }}</p>
            </div>

            <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-green-200 dark:border-green-800">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">Cashback เฉลี่ย</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    ฿{{ $cashbackStats['count'] > 0 ? number_format($cashbackStats['total'] / $cashbackStats['count'], 2) : '0.00' }}
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Additional Statistics --}}
    @if(isset($statistics) && !empty($statistics))
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($statistics as $key => $stat)
            <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="text-4xl">{{ $stat['icon'] ?? '📊' }}</div>
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $stat['label'] ?? ucfirst($key) }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] ?? '0' }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Recent Transactions --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-200 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-history text-indigo-600 dark:text-indigo-400"></i>
                        รายการธุรกรรมล่าสุด
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">10 รายการล่าสุด</p>
                </div>
                <a href="{{ route('user.wallet.transactions') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                    ดูทั้งหมด →
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/60 dark:bg-white/10">
                    <tr>
                        <th class="text-left py-3 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="text-left py-3 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">รายละเอียด</th>
                        <th class="text-right py-3 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">จำนวนเงิน</th>
                        <th class="text-center py-3 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="text-right py-3 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">วันที่</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse($recentTransactions as $transaction)
                        <tr class="hover:bg-white/80 dark:hover:bg-white/10 transition-all duration-200">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ $transaction->type_icon }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $transaction->type_label }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $transaction->description }}</p>
                                @if($transaction->relatedWallet)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $transaction->type === 'transfer_in' ? 'จาก' : 'ถึง' }}: {{ $transaction->relatedWallet->user->name }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">ID: {{ $transaction->transaction_id }}</p>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'refund', 'bonus']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $transaction->formatted_amount }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-{{ $transaction->status_color }}-100 dark:bg-{{ $transaction->status_color }}-900/30 text-{{ $transaction->status_color }}-800 dark:text-{{ $transaction->status_color }}-300">
                                    {{ $transaction->status_label }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $transaction->created_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $transaction->created_at->format('H:i') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="inline-block p-6 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-full mb-4">
                                    <i class="fas fa-inbox text-6xl text-indigo-600 dark:text-indigo-400"></i>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 text-lg font-semibold">ยังไม่มีรายการธุรกรรม</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment Methods --}}
    @if($paymentMethods->isNotEmpty())
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-university text-blue-600 dark:text-blue-400"></i>
                    ช่องทางรับเงิน
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">บัญชีสำหรับถอนเงิน</p>
            </div>
            <a href="{{ route('user.wallet.payment-methods') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                จัดการ →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($paymentMethods as $method)
                <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm border-2 border-gray-200 dark:border-white/20 rounded-xl p-4 hover:bg-white/80 dark:hover:bg-white/15 hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-200">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">
                                @if($method->type === 'promptpay') 💳
                                @elseif($method->type === 'bank_transfer') 🏦
                                @elseif($method->type === 'paypal') 💰
                                @else 💵
                                @endif
                            </span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $method->name }}</span>
                        </div>
                        @if($method->is_default)
                            <span class="px-2 py-1 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-800 dark:text-green-300 rounded-lg text-xs font-bold">
                                ค่าเริ่มต้น
                            </span>
                        @endif
                    </div>

                    @if($method->type === 'bank_transfer')
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $method->bank_name }}</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $method->account_name }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $method->account_number }}</p>
                    @elseif($method->type === 'promptpay')
                        <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $method->account_name }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $method->account_number }}</p>
                    @elseif($method->type === 'paypal')
                        <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $method->paypal_email }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Available Payment Gateways --}}
    @if(isset($availableGateways) && !empty($availableGateways))
    <div class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 dark:from-indigo-600 dark:via-purple-600 dark:to-pink-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-credit-card"></i>
            ช่องทางการฝากเงิน
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($availableGateways as $gateway)
                <div class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl p-4 text-center transition-all duration-200">
                    <div class="text-4xl mb-2">{{ $gateway['icon'] ?? '💰' }}</div>
                    <p class="text-sm font-semibold">{{ $gateway['name'] ?? 'Gateway' }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Wallet Status Alert --}}
    @if($wallet->isLocked())
        <x-arrow-x.alert-v3 type="error">
            <div class="flex items-center gap-3">
                <span class="text-3xl">🔒</span>
                <div>
                    <p class="font-semibold text-lg">กระเป๋าเงินของคุณถูกล็อก</p>
                    <p class="text-sm">กรุณาติดต่อฝ่ายสนับสนุนเพื่อปลดล็อค</p>
                    @if($wallet->locked_until)
                        <p class="text-xs mt-1">ล็อคจนถึง: {{ $wallet->locked_until->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </x-arrow-x.alert-v3>
    @endif
</div>

@push('scripts')
<script>
// Copy Wallet Address Function
function copyWalletAddress() {
    const walletAddress = '{{ $wallet->wallet_address }}';

    navigator.clipboard.writeText(walletAddress).then(() => {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
        toast.innerHTML = '✅ คัดลอก Wallet Address สำเร็จ!';
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('เกิดข้อผิดพลาดในการคัดลอก');
    });
}
</script>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>
@endpush
@endsection

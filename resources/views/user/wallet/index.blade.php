@extends('layouts.user-arrow-x')

@section('title', 'กระเป๋าเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Wallet Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white dark:bg-gray-800 opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white dark:bg-gray-800 opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">💳 กระเป๋าเงินของฉัน</h1>
                    <p class="text-indigo-100">จัดการการเงินของคุณ</p>
                </div>
                <div class="hidden md:block text-right">
                    <p class="text-sm text-indigo-100 mb-1">Wallet Address</p>
                    <div class="flex items-center gap-2">
                        <code class="px-3 py-1 bg-white dark:bg-gray-800 bg-opacity-20 rounded-lg text-sm font-mono">{{ $wallet->wallet_address }}</code>
                        <button onclick="copyWalletAddress()" class="p-2 bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                            📋
                        </button>
                    </div>
                </div>
            </div>

            <!-- Balance Display -->
            <div class="bg-white dark:bg-gray-800 bg-opacity-20 rounded-xl p-6 backdrop-blur-sm">
                <p class="text-indigo-100 text-sm mb-2">ยอดเงินคงเหลือ</p>
                <p class="text-5xl md:text-6xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
                <p class="text-indigo-100 text-sm mt-2">{{ $wallet->currency }}</p>
            </div>

            <!-- Mobile Wallet Address -->
            <div class="md:hidden mt-4">
                <p class="text-sm text-indigo-100 mb-1">Wallet Address</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 bg-opacity-20 rounded-lg text-xs font-mono">{{ $wallet->wallet_address }}</code>
                    <button onclick="copyWalletAddress()" class="p-2 bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                        📋
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('user.wallet.deposit') }}" class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900 dark:to-emerald-900 border border-green-200 dark:border-green-700 rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transition-transform hover:scale-[1.02]">
            <div class="text-4xl mb-3">💵</div>
            <p class="text-green-800 dark:text-green-100 font-semibold">ฝากเงิน</p>
            <p class="text-xs text-green-600 dark:text-green-300 mt-1">Deposit</p>
        </a>

        <a href="{{ route('user.wallet.withdraw') }}" class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900 dark:to-rose-900 border border-red-200 dark:border-red-700 rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transition-transform hover:scale-[1.02]">
            <div class="text-4xl mb-3">💸</div>
            <p class="text-red-800 dark:text-red-100 font-semibold">ถอนเงิน</p>
            <p class="text-xs text-red-600 dark:text-red-300 mt-1">Withdraw</p>
        </a>

        <a href="{{ route('user.wallet.transfer') }}" class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900 dark:to-cyan-900 border border-blue-200 dark:border-blue-700 rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transition-transform hover:scale-[1.02]">
            <div class="text-4xl mb-3">📤</div>
            <p class="text-blue-800 dark:text-blue-100 font-semibold">โอนเงิน</p>
            <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">Transfer</p>
        </a>

        <a href="{{ route('user.wallet.transactions') }}" class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900 dark:to-indigo-900 border border-purple-200 dark:border-purple-700 rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transition-transform hover:scale-[1.02]">
            <div class="text-4xl mb-3">📝</div>
            <p class="text-purple-800 dark:text-purple-100 font-semibold">ประวัติ</p>
            <p class="text-xs text-purple-600 dark:text-purple-300 mt-1">History</p>
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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

    <!-- Admin Adjustments Card -->
    @if(isset($adminStats) && $adminStats['total'] > 0)
    <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl">⚡</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">เงินที่ได้จากแอดมิน/ระบบ</p>
            <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($adminStats['total'] ?? 0, 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">{{ $adminStats['count'] ?? 0 }} ครั้ง</p>
            <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-white border-opacity-20">
                <div>
                    <p class="text-xs text-white text-opacity-70 mb-1">เดือนนี้</p>
                    <p class="text-lg font-bold">฿{{ number_format($adminStats['this_month'] ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-white text-opacity-70 mb-1">30 วันล่าสุด</p>
                    <p class="text-lg font-bold">฿{{ number_format($adminStats['last_30_days'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Cashback Details -->
    @if(isset($cashbackStats) && $cashbackStats['total'] > 0)
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-green-800 flex items-center gap-2">
                    <span class="text-2xl">💸</span>
                    สถิติ Cashback
                </h3>
                <p class="text-sm text-green-600 mt-1">รายรับจากการคืนเงิน</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 bg-opacity-60 rounded-xl p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Cashback เดือนนี้</p>
                <p class="text-2xl font-bold text-green-600">฿{{ number_format($cashbackStats['this_month'] ?? 0, 2) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 bg-opacity-60 rounded-xl p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">30 วันล่าสุด</p>
                <p class="text-2xl font-bold text-green-600">฿{{ number_format($cashbackStats['last_30_days'] ?? 0, 2) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 bg-opacity-60 rounded-xl p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Cashback เฉลี่ย</p>
                <p class="text-2xl font-bold text-green-600">
                    ฿{{ $cashbackStats['count'] > 0 ? number_format($cashbackStats['total'] / $cashbackStats['count'], 2) : '0.00' }}
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Additional Statistics -->
    @if(isset($statistics) && !empty($statistics))
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($statistics as $key => $stat)
            <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
                <div class="flex items-center gap-3">
                    <div class="text-3xl">{{ $stat['icon'] ?? '📊' }}</div>
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $stat['label'] ?? ucfirst($key) }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $stat['value'] ?? '0' }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Recent Transactions -->
    <x-arrow-x.card-v3 class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">📊 รายการธุรกรรมล่าสุด</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">10 รายการล่าสุด</p>
            </div>
            <a href="{{ route('user.wallet.transactions') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                ดูทั้งหมด →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">ประเภท</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">รายละเอียด</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">จำนวนเงิน</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">วันที่</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $transaction)
                        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ $transaction->type_icon }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $transaction->type_label }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $transaction->description }}</p>
                                @if($transaction->relatedWallet)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $transaction->type === 'transfer_in' ? 'จาก' : 'ถึง' }}: {{ $transaction->relatedWallet->user->name }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">ID: {{ $transaction->transaction_id }}</p>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'refund', 'bonus']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $transaction->formatted_amount }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-{{ $transaction->status_color }}-100 text-{{ $transaction->status_color }}-800 dark:bg-{{ $transaction->status_color }}-900 dark:text-{{ $transaction->status_color }}-200">
                                    {{ $transaction->status_label }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->created_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $transaction->created_at->format('H:i') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center">
                                <div class="text-gray-400 dark:text-gray-500">
                                    <span class="text-4xl block mb-2">📭</span>
                                    <p class="text-sm">ยังไม่มีรายการธุรกรรม</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-arrow-x.card-v3>

    <!-- Payment Methods -->
    @if($paymentMethods->isNotEmpty())
    <x-arrow-x.card-v3 class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">🏦 ช่องทางรับเงิน</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">บัญชีสำหรับถอนเงิน</p>
            </div>
            <a href="{{ route('user.wallet.payment-methods') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                จัดการ →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($paymentMethods as $method)
                <div class="border-2 border-gray-200 dark:border-gray-600 bg-gradient-to-br from-gray-50 to-white dark:from-gray-700 dark:to-gray-800 rounded-xl p-4 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition">
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
                            <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-xs font-semibold">
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
    </x-arrow-x.card-v3>
    @endif

    <!-- Available Payment Gateways -->
    @if(isset($availableGateways) && !empty($availableGateways))
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">💳 ช่องทางการฝากเงิน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($availableGateways as $gateway)
                <div class="bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                    <div class="text-3xl mb-2">{{ $gateway['icon'] ?? '💰' }}</div>
                    <p class="text-sm font-semibold">{{ $gateway['name'] ?? 'Gateway' }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Wallet Status Alert -->
    @if($wallet->isLocked())
        <x-arrow-x.alert-v3 type="error" class="mb-6">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🔒</span>
                <div>
                    <p class="font-semibold">กระเป๋าเงินของคุณถูกล็อก</p>
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

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>
@endpush
@endsection

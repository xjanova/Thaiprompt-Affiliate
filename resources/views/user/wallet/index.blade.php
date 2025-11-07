@extends('layouts.user')

@section('title', 'กระเป๋าเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Wallet Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">💳 กระเป๋าเงินของฉัน</h1>
                    <p class="text-indigo-100">จัดการการเงินของคุณ</p>
                </div>
                <div class="hidden md:block text-right">
                    <p class="text-sm text-indigo-100 mb-1">Wallet Address</p>
                    <div class="flex items-center gap-2">
                        <code class="px-3 py-1 bg-white bg-opacity-20 rounded-lg text-sm font-mono">{{ $wallet->wallet_address }}</code>
                        <button onclick="copyWalletAddress()" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                            📋
                        </button>
                    </div>
                </div>
            </div>

            <!-- Balance Display -->
            <div class="bg-white bg-opacity-20 rounded-xl p-6 backdrop-blur-sm">
                <p class="text-indigo-100 text-sm mb-2">ยอดเงินคงเหลือ</p>
                <p class="text-5xl md:text-6xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
                <p class="text-indigo-100 text-sm mt-2">{{ $wallet->currency }}</p>
            </div>

            <!-- Mobile Wallet Address -->
            <div class="md:hidden mt-4">
                <p class="text-sm text-indigo-100 mb-1">Wallet Address</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 px-3 py-2 bg-white bg-opacity-20 rounded-lg text-xs font-mono">{{ $wallet->wallet_address }}</code>
                    <button onclick="copyWalletAddress()" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                        📋
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('user.wallet.deposit') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">💵</div>
            <p class="text-gray-800 font-semibold">ฝากเงิน</p>
            <p class="text-xs text-gray-500 mt-1">Deposit</p>
        </a>

        <a href="{{ route('user.wallet.withdraw') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">💸</div>
            <p class="text-gray-800 font-semibold">ถอนเงิน</p>
            <p class="text-xs text-gray-500 mt-1">Withdraw</p>
        </a>

        <a href="{{ route('user.wallet.transfer') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">📤</div>
            <p class="text-gray-800 font-semibold">โอนเงิน</p>
            <p class="text-xs text-gray-500 mt-1">Transfer</p>
        </a>

        <a href="{{ route('user.wallet.transactions') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">📝</div>
            <p class="text-gray-800 font-semibold">ประวัติ</p>
            <p class="text-xs text-gray-500 mt-1">History</p>
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl">📥</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">รายรับทั้งหมด</p>
            <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($wallet->total_income, 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">Total Income</p>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl">📤</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">รายจ่ายทั้งหมด</p>
            <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($wallet->total_expense, 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">Total Expense</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl">💰</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">ยอดเงินคงเหลือ</p>
            <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">Current Balance</p>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl">🎁</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">Cashback ทั้งหมด</p>
            <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($cashbackStats['total'] ?? 0, 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">{{ $cashbackStats['count'] ?? 0 }} ครั้ง</p>
        </div>
    </div>

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
            <div class="bg-white bg-opacity-60 rounded-xl p-4">
                <p class="text-sm text-gray-600 mb-1">Cashback เดือนนี้</p>
                <p class="text-2xl font-bold text-green-600">฿{{ number_format($cashbackStats['this_month'] ?? 0, 2) }}</p>
            </div>

            <div class="bg-white bg-opacity-60 rounded-xl p-4">
                <p class="text-sm text-gray-600 mb-1">30 วันล่าสุด</p>
                <p class="text-2xl font-bold text-green-600">฿{{ number_format($cashbackStats['last_30_days'] ?? 0, 2) }}</p>
            </div>

            <div class="bg-white bg-opacity-60 rounded-xl p-4">
                <p class="text-sm text-gray-600 mb-1">Cashback เฉลี่ย</p>
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
            <div class="bg-white rounded-xl shadow-md p-4">
                <div class="flex items-center gap-3">
                    <div class="text-3xl">{{ $stat['icon'] ?? '📊' }}</div>
                    <div>
                        <p class="text-xs text-gray-500">{{ $stat['label'] ?? ucfirst($key) }}</p>
                        <p class="text-xl font-bold text-gray-800">{{ $stat['value'] ?? '0' }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Recent Transactions -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900">📊 รายการธุรกรรมล่าสุด</h3>
                <p class="text-sm text-gray-500">10 รายการล่าสุด</p>
            </div>
            <a href="{{ route('user.wallet.transactions') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                ดูทั้งหมด →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">ประเภท</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">รายละเอียด</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">จำนวนเงิน</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">สถานะ</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">วันที่</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $transaction)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ $transaction->type_icon }}</span>
                                    <span class="text-sm font-medium text-gray-800">{{ $transaction->type_label }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <p class="text-sm text-gray-800">{{ $transaction->description }}</p>
                                @if($transaction->relatedWallet)
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $transaction->type === 'transfer_in' ? 'จาก' : 'ถึง' }}: {{ $transaction->relatedWallet->user->name }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">ID: {{ $transaction->transaction_id }}</p>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'refund', 'bonus']) ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->formatted_amount }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-{{ $transaction->status_color }}-100 text-{{ $transaction->status_color }}-800">
                                    {{ $transaction->status_label }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <p class="text-sm text-gray-800">{{ $transaction->created_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $transaction->created_at->format('H:i') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center">
                                <div class="text-gray-400">
                                    <span class="text-4xl block mb-2">📭</span>
                                    <p class="text-sm">ยังไม่มีรายการธุรกรรม</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Methods -->
    @if($paymentMethods->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900">🏦 ช่องทางรับเงิน</h3>
                <p class="text-sm text-gray-500">บัญชีสำหรับถอนเงิน</p>
            </div>
            <a href="{{ route('user.wallet.payment-methods') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                จัดการ →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($paymentMethods as $method)
                <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">
                                @if($method->type === 'promptpay') 💳
                                @elseif($method->type === 'bank_transfer') 🏦
                                @elseif($method->type === 'paypal') 💰
                                @else 💵
                                @endif
                            </span>
                            <span class="text-sm font-semibold text-gray-800">{{ $method->name }}</span>
                        </div>
                        @if($method->is_default)
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                ค่าเริ่มต้น
                            </span>
                        @endif
                    </div>

                    @if($method->type === 'bank_transfer')
                        <p class="text-xs text-gray-600">{{ $method->bank_name }}</p>
                        <p class="text-sm text-gray-800 font-medium">{{ $method->account_name }}</p>
                        <p class="text-xs text-gray-500">{{ $method->account_number }}</p>
                    @elseif($method->type === 'promptpay')
                        <p class="text-sm text-gray-800 font-medium">{{ $method->account_name }}</p>
                        <p class="text-xs text-gray-500">{{ $method->account_number }}</p>
                    @elseif($method->type === 'paypal')
                        <p class="text-sm text-gray-800 font-medium">{{ $method->paypal_email }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Available Payment Gateways -->
    @if(isset($availableGateways) && !empty($availableGateways))
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">💳 ช่องทางการฝากเงิน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($availableGateways as $gateway)
                <div class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                    <div class="text-3xl mb-2">{{ $gateway['icon'] ?? '💰' }}</div>
                    <p class="text-sm font-semibold">{{ $gateway['name'] ?? 'Gateway' }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Wallet Status Alert -->
    @if($wallet->isLocked())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
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
        </div>
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

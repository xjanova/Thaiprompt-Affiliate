@extends('layouts.admin-v3')

@section('title', 'กระเป๋าเงิน')

@section('content')
<div class="space-y-6">
    <!-- Wallet Overview Card -->
    <div class="rounded-2xl shadow-2xl p-8 text-white" style="background: var(--arrow-x-primary-gradient)">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2">กระเป๋าเงินของฉัน</h2>
                <p class="opacity-80 text-sm">{{ $wallet->wallet_address }}</p>
            </div>
            <div class="text-right">
                <div class="inline-flex items-center px-4 py-2 glass-fusion backdrop-blur-sm rounded-full">
                    <span class="text-sm font-medium">สถานะ:</span>
                    <span class="ml-2 text-sm font-bold">
                        @if($wallet->isActive())
                            ✅ ใช้งานได้
                        @elseif($wallet->isLocked())
                            🔒 ถูกล็อก
                        @else
                            ⏸️ ระงับ
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Balance -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="opacity-80">ยอดคงเหลือ</span>
                    <span class="text-2xl">💰</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($wallet->balance, 2) }}</p>
                <p class="text-sm opacity-80 mt-1">{{ $wallet->currency }}</p>
            </div>

            <!-- Total Income -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="opacity-80">รายรับทั้งหมด</span>
                    <span class="text-2xl">📈</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($statistics['total_income'], 2) }}</p>
                <p class="text-sm opacity-80 mt-1">30 วันล่าสุด: {{ number_format($statistics['last_30_days_income'], 2) }}</p>
            </div>

            <!-- Total Expense -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">รายจ่ายทั้งหมด</span>
                    <span class="text-2xl">📉</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($statistics['total_expense'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">30 วันล่าสุด: {{ number_format($statistics['last_30_days_expense'], 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Deposit (Admin Only) -->
        @if(auth()->user()->isSuperAdmin())
        <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10 x-data="{ open: false }">
            <div class="flex items-center mb-4">
                <span class="text-3xl mr-3">💵</span>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">ฝากเงิน (Admin)</h3>
            </div>
            <button @click="open = !open" class="w-full text-white font-semibold py-3 px-6 rounded-xl transition-all transform hover:scale-105" style="background: linear-gradient(to right, var(--arrow-x-success), var(--arrow-x-info))">
                เติมเงิน
            </button>

            <!-- Deposit Form -->
            <div x-show="open" x-transition class="mt-4" style="display: none;">
                <form action="{{ route('admin.wallet.deposit') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">จำนวนเงิน</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">หมายเหตุ</label>
                        <input type="text" name="description" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="w-full text-white font-semibold py-2 px-4 rounded-xl transition" style="background: var(--arrow-x-success)">
                        ยืนยันการฝาก
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Withdraw -->
        <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10 x-data="{ open: false }">
            <div class="flex items-center mb-4">
                <span class="text-3xl mr-3">💸</span>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">ถอนเงิน</h3>
            </div>
            <button @click="open = !open" class="w-full text-white font-semibold py-3 px-6 rounded-xl transition-all transform hover:scale-105" style="background: linear-gradient(to right, var(--arrow-x-error), var(--arrow-x-accent))">
                ถอนเงิน
            </button>

            <!-- Withdraw Form -->
            <div x-show="open" x-transition class="mt-4" style="display: none;">
                <form action="{{ route('admin.wallet.withdraw') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">จำนวนเงิน</label>
                        <input type="number" name="amount" step="0.01" min="0.01" max="{{ $wallet->balance }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">คงเหลือ: {{ number_format($wallet->balance, 2) }} {{ $wallet->currency }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">PIN <span class="text-red-500">*</span></label>
                        <input type="password" name="pin" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">หมายเหตุ</label>
                        <input type="text" name="description" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-xl transition">
                        ยืนยันการถอน
                    </button>
                </form>
            </div>
        </div>

        <!-- Transfer -->
        <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10 x-data="{ open: false }">
            <div class="flex items-center mb-4">
                <span class="text-3xl mr-3">📤</span>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">โอนเงิน</h3>
            </div>
            <button @click="open = !open" class="w-full text-white font-semibold py-3 px-6 rounded-xl transition-all transform hover:scale-105" style="background: var(--arrow-x-primary-gradient)">
                โอนเงิน
            </button>

            <!-- Transfer Form -->
            <div x-show="open" x-transition class="mt-4" style="display: none;">
                <form action="{{ route('admin.wallet.transfer') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ที่อยู่กระเป๋าเงินปลายทาง</label>
                        <input type="text" name="wallet_address" required placeholder="TPW..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">จำนวนเงิน</label>
                        <input type="number" name="amount" step="0.01" min="0.01" max="{{ $wallet->balance }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">PIN <span class="text-red-500">*</span></label>
                        <input type="password" name="pin" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">หมายเหตุ</label>
                        <input type="text" name="description" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl transition">
                        ยืนยันการโอน
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white">ธุรกรรมล่าสุด</h3>
            <a href="{{ route('admin.wallet.transactions') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold text-sm">ดูทั้งหมด →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">รายละเอียด</th>
                        <th class="px-6 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($recentTransactions as $transaction)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-2xl mr-2">{{ $transaction->type_icon }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $transaction->type_label }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'bonus']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->formatted_amount }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900 dark:text-gray-300">{{ $transaction->description }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $transaction->status_color }}-100 dark:bg-{{ $transaction->status_color }}-900/30 text-{{ $transaction->status_color }}-800 dark:text-{{ $transaction->status_color }}-300">
                                {{ $transaction->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">ไม่มีธุรกรรม</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Security Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('admin.wallet.settings') }}" class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:scale-105">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-4xl">⚙️</span>
                </div>
                <div class="ml-4">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">การตั้งค่า</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">จัดการ PIN, 2FA และความปลอดภัย</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.wallet.logs') }}" class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:scale-105">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-4xl">🔒</span>
                </div>
                <div class="ml-4">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">ประวัติความปลอดภัย</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ดูกิจกรรมและการแจ้งเตือน</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection

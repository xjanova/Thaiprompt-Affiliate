@extends('layouts.admin-v3')

@section('title', 'รายละเอียดกระเป๋าเงิน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">รายละเอียดกระเป๋าเงิน</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1 font-mono">{{ $wallet->wallet_address }}</p>
        </div>
        <a href="{{ route('admin.wallet.all') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    <!-- Wallet Overview -->
    <div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-2xl font-bold mb-2">{{ $wallet->user->name }}</h3>
                <p class="opacity-80 text-sm">{{ $wallet->user->email }}</p>
            </div>
            <div class="text-right">
                <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    <span class="text-sm font-medium">สถานะ:</span>
                    <span class="ml-2 text-sm font-bold">
                        @if($wallet->isActive())
                            ✅ ใช้งานได้
                        @elseif($wallet->isLocked())
                            🔒 ถูกล็อก
                        @elseif($wallet->status == 'suspended')
                            ⏸️ ระงับชั่วคราว
                        @else
                            ❓ {{ $wallet->status }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Balance -->
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">ยอดคงเหลือ</span>
                    <span class="text-2xl">💰</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($wallet->balance, 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">{{ $wallet->currency }}</p>
            </div>

            <!-- Total Income -->
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">รายรับทั้งหมด</span>
                    <span class="text-2xl">📈</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($statistics['total_income'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">30 วันล่าสุด: {{ number_format($statistics['last_30_days_income'], 2) }}</p>
            </div>

            <!-- Total Expense -->
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">รายจ่ายทั้งหมด</span>
                    <span class="text-2xl">📉</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($statistics['total_expense'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">30 วันล่าสุด: {{ number_format($statistics['last_30_days_expense'], 2) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recent Transactions -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ธุรกรรมล่าสุด</h3>
                    <a href="{{ route('admin.wallet.all-transactions', ['wallet_id' => $wallet->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
                        ดูทั้งหมด →
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รายละเอียด</th>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($recentTransactions as $transaction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-xl mr-2">{{ $transaction->type_icon }}</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $transaction->type_label }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'bonus', 'refund']) ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->formatted_amount }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($transaction->description, 30) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">ไม่มีธุรกรรม</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Activity Logs -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Activity Logs</h3>
                    <a href="{{ route('admin.wallet.all-logs', ['wallet_id' => $wallet->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
                        ดูทั้งหมด →
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($recentLogs as $log)
                    <div class="flex items-start p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="flex-shrink-0 mr-3">
                            @if($log->severity == 'critical') 🚨
                            @elseif($log->severity == 'warning') ⚠️
                            @else ℹ️
                            @endif
                        </span>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $log->description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">ไม่มี logs</p>
                    @endforelse
                </div>
            </div>

            <!-- Wallet Info -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลกระเป๋าเงิน</h3>
                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">Wallet Address</span>
                        <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $wallet->wallet_address }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">สกุลเงิน</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $wallet->currency }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">มี PIN</span>
                        <span class="font-semibold {{ $wallet->hasPIN() ? 'text-green-600' : 'text-red-600' }}">
                            {{ $wallet->hasPIN() ? '✅ ตั้งแล้ว' : '❌ ยังไม่ตั้ง' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $wallet->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">อัปเดตล่าสุด</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $wallet->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($wallet->two_factor_enabled)
                    <div class="flex justify-between py-2">
                        <span class="text-gray-600 dark:text-gray-400">Two-Factor Authentication</span>
                        <span class="font-semibold text-green-600">✅ เปิดใช้งาน</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="space-y-6">
            <!-- Admin Actions -->
            <div x-data="{
                showAdjust: false,
                showRefund: false,
                showTransfer: false,
                showSuspend: false,
                showRollback: false
            }" class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">การจัดการ (Admin)</h3>
                <div class="space-y-3">
                    <!-- Adjust Balance -->
                    <button @click="showAdjust = !showAdjust; showRefund = false; showTransfer = false; showSuspend = false; showRollback = false;" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl transition flex items-center justify-center">
                        💵 ปรับยอดเงิน
                    </button>

                    <div x-show="showAdjust" x-transition class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <form action="{{ route('admin.wallet.adjust-balance', $wallet->id) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                                <select name="type" onchange="document.getElementById('amount_field').value = Math.abs(document.getElementById('amount_field').value) * (this.value == 'subtract' ? -1 : 1)" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="add">เพิ่มเงิน (+)</option>
                                    <option value="subtract">หักเงิน (-)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน</label>
                                <input type="number" id="amount_field" name="amount" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="2" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ระบุเหตุผล..."></textarea>
                            </div>
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-xl transition">
                                ✅ ยืนยันการปรับยอด
                            </button>
                        </form>
                    </div>

                    <!-- Refund -->
                    <button @click="showRefund = !showRefund; showAdjust = false; showTransfer = false; showSuspend = false; showRollback = false;" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition flex items-center justify-center">
                        🔄 คืนเงิน (Refund)
                    </button>

                    <div x-show="showRefund" x-transition class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <form action="{{ route('admin.wallet.refund', $wallet->id) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงินคืน</label>
                                <input type="number" name="amount" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="2" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ระบุเหตุผลในการคืนเงิน..."></textarea>
                            </div>
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-xl transition">
                                ✅ ยืนยันการคืนเงิน
                            </button>
                        </form>
                    </div>

                    <!-- Admin Transfer -->
                    <button @click="showTransfer = !showTransfer; showAdjust = false; showRefund = false; showSuspend = false; showRollback = false;" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition flex items-center justify-center">
                        ↔️ โอนเงิน (Admin)
                    </button>

                    <div x-show="showTransfer" x-transition class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <form action="{{ route('admin.wallet.admin-transfer') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="from_wallet_id" value="{{ $wallet->id }}">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โอนไปยัง Wallet</label>
                                <select name="to_wallet_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="">-- เลือกกระเป๋าปลายทาง --</option>
                                    @php
                                        $allWallets = \App\Models\Wallet::with('user')
                                            ->where('id', '!=', $wallet->id)
                                            ->where('status', 'active')
                                            ->orderBy('created_at', 'desc')
                                            ->get();
                                    @endphp
                                    @foreach($allWallets as $w)
                                        <option value="{{ $w->id }}">{{ $w->user->name }} ({{ $w->wallet_address }}) - {{ number_format($w->balance, 2) }} THB</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน</label>
                                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $wallet->balance }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ยอดคงเหลือ: {{ number_format($wallet->balance, 2) }} THB</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="2" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ระบุเหตุผลในการโอน..."></textarea>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl transition">
                                ✅ ยืนยันการโอนเงิน
                            </button>
                        </form>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700 my-4">

                    <!-- Lock/Unlock Wallet -->
                    @if($wallet->isActive())
                    <form action="{{ route('admin.wallet.lock-user', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการล็อกกระเป๋าเงินนี้?')" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-xl transition">
                            🔒 ล็อกกระเป๋าเงิน
                        </button>
                    </form>
                    @elseif($wallet->isLocked())
                    <form action="{{ route('admin.wallet.unlock-user', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปลดล็อกกระเป๋าเงินนี้?')" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition">
                            🔓 ปลดล็อกกระเป๋าเงิน
                        </button>
                    </form>
                    @endif

                    <!-- Suspend/Unsuspend -->
                    @if($wallet->status != 'suspended' && $wallet->isActive())
                    <button @click="showSuspend = !showSuspend; showAdjust = false; showRefund = false; showTransfer = false; showRollback = false;" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-xl transition flex items-center justify-center">
                        ⏸️ ระงับกระเป๋าชั่วคราว
                    </button>

                    <div x-show="showSuspend" x-transition class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <form action="{{ route('admin.wallet.suspend-user', $wallet->id) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผลในการระงับ <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="2" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ระบุเหตุผลในการระงับ..."></textarea>
                            </div>
                            <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการระงับกระเป๋าเงินนี้?')" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded-xl transition">
                                ⏸️ ยืนยันการระงับ
                            </button>
                        </form>
                    </div>
                    @elseif($wallet->status == 'suspended')
                    <form action="{{ route('admin.wallet.unsuspend-user', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการระงับกระเป๋าเงินนี้?')" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-xl transition">
                            ▶️ ยกเลิกการระงับ
                        </button>
                    </form>
                    @endif

                    <!-- Reset PIN (Super Admin Only) -->
                    @if(auth()->user()->isSuperAdmin() && $wallet->hasPIN())
                    <hr class="border-gray-200 dark:border-gray-700 my-4">
                    <form action="{{ route('admin.wallet.reset-pin', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการรีเซ็ต PIN? ผู้ใช้จะต้องตั้ง PIN ใหม่')" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-4 rounded-xl transition">
                            🔑 รีเซ็ต PIN
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- User Info -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลเจ้าของกระเป๋า</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 h-12 w-12 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                            <span class="text-indigo-600 dark:text-indigo-300 font-bold text-lg">{{ substr($wallet->user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $wallet->user->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $wallet->user->email }}</p>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                        <a href="{{ route('admin.users.show', $wallet->user->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-sm font-semibold">
                            ดูโปรไฟล์ผู้ใช้ →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สถิติ</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">ธุรกรรมทั้งหมด</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($statistics['transactions_count'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">รายรับ 30 วัน</span>
                        <span class="font-bold text-green-600">{{ number_format($statistics['last_30_days_income'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">รายจ่าย 30 วัน</span>
                        <span class="font-bold text-red-600">{{ number_format($statistics['last_30_days_expense'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">รายรับทั้งหมด</span>
                        <span class="font-bold text-green-600">{{ number_format($statistics['total_income'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">รายจ่ายทั้งหมด</span>
                        <span class="font-bold text-red-600">{{ number_format($statistics['total_expense'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ลิงก์ด่วน</h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.wallet.all-transactions', ['wallet_id' => $wallet->id]) }}" class="block px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 transition">
                        📊 ดูธุรกรรมทั้งหมด
                    </a>
                    <a href="{{ route('admin.wallet.all-logs', ['wallet_id' => $wallet->id]) }}" class="block px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 transition">
                        📋 ดู Activity Logs
                    </a>
                    <a href="{{ route('admin.wallet.all') }}" class="block px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 transition">
                        💼 กระเป๋าทั้งหมด
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

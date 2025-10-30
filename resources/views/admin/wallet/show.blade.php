@extends('layouts.admin')

@section('title', 'รายละเอียดกระเป๋าเงิน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">รายละเอียดกระเป๋าเงิน</h2>
            <p class="text-gray-600 mt-1">{{ $wallet->wallet_address }}</p>
        </div>
        <a href="{{ route('admin.wallet.all') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
            ← กลับ
        </a>
    </div>

    <!-- Wallet Overview -->
    <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-2xl font-bold mb-2">{{ $wallet->user->name }}</h3>
                <p class="text-indigo-100 text-sm">{{ $wallet->user->email }}</p>
            </div>
            <div class="text-right">
                <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
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
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">ยอดคงเหลือ</span>
                    <span class="text-2xl">💰</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($wallet->balance, 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">{{ $wallet->currency }}</p>
            </div>

            <!-- Total Income -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">รายรับทั้งหมด</span>
                    <span class="text-2xl">📈</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($statistics['total_income'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">30 วันล่าสุด: {{ number_format($statistics['last_30_days_income'], 2) }}</p>
            </div>

            <!-- Total Expense -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
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
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">ธุรกรรมล่าสุด</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recentTransactions as $transaction)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-2">{{ $transaction->type_icon }}</span>
                                        <span class="text-sm font-medium text-gray-900">{{ $transaction->type_label }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'bonus']) ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->formatted_amount }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900">{{ $transaction->description }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">ไม่มีธุรกรรม</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Wallet Info -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลกระเป๋าเงิน</h3>
                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Wallet Address</span>
                        <span class="font-mono font-semibold text-gray-900">{{ $wallet->wallet_address }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">สกุลเงิน</span>
                        <span class="font-semibold text-gray-900">{{ $wallet->currency }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">สร้างเมื่อ</span>
                        <span class="font-semibold text-gray-900">{{ $wallet->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">อัปเดตล่าสุด</span>
                        <span class="font-semibold text-gray-900">{{ $wallet->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($wallet->pin_set_at)
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">ตั้ง PIN เมื่อ</span>
                        <span class="font-semibold text-gray-900">{{ $wallet->pin_set_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                    @if($wallet->two_factor_enabled)
                    <div class="flex justify-between py-2">
                        <span class="text-gray-600">Two-Factor Authentication</span>
                        <span class="font-semibold text-green-600">✅ เปิดใช้งาน</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="space-y-6">
            <!-- Admin Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">การจัดการ (Admin)</h3>
                <div class="space-y-3">
                    <!-- Adjust Balance -->
                    <div x-data="{ open: false }">
                        <button @click="open = !open" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                            💵 ปรับยอดเงิน
                        </button>

                        <div x-show="open" x-transition class="mt-4 bg-gray-50 rounded-lg p-4" style="display: none;">
                            <form action="{{ route('admin.wallet.adjust-balance', $wallet->id) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ประเภท</label>
                                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="add">เพิ่มเงิน (+)</option>
                                        <option value="subtract">หักเงิน (-)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนเงิน</label>
                                    <input type="number" name="amount" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                                    <textarea name="reason" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="ระบุเหตุผล..."></textarea>
                                </div>
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                                    ยืนยันการปรับยอด
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Lock/Unlock Wallet -->
                    @if($wallet->isActive())
                    <form action="{{ route('admin.wallet.lock', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการล็อกกระเป๋าเงินนี้?')" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                            🔒 ล็อกกระเป๋าเงิน
                        </button>
                    </form>
                    @elseif($wallet->isLocked())
                    <form action="{{ route('admin.wallet.unlock', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปลดล็อกกระเป๋าเงินนี้?')" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                            🔓 ปลดล็อกกระเป๋าเงิน
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- User Info -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลเจ้าของกระเป๋า</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 h-12 w-12 bg-indigo-100 rounded-full flex items-center justify-center">
                            <span class="text-indigo-600 font-bold text-lg">{{ substr($wallet->user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $wallet->user->name }}</p>
                            <p class="text-sm text-gray-600">{{ $wallet->user->email }}</p>
                        </div>
                    </div>
                    <div class="border-t pt-3">
                        <a href="{{ route('admin.users.show', $wallet->user->id) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold">
                            ดูโปรไฟล์ผู้ใช้ →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">สถิติ</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600 text-sm">ธุรกรรมทั้งหมด</span>
                        <span class="font-bold text-gray-900">{{ number_format($statistics['total_transactions']) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t">
                        <span class="text-gray-600 text-sm">ธุรกรรมเดือนนี้</span>
                        <span class="font-bold text-gray-900">{{ number_format($statistics['this_month_transactions']) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t">
                        <span class="text-gray-600 text-sm">คอมมิชชั่นที่ได้รับ</span>
                        <span class="font-bold text-green-600">{{ number_format($statistics['total_commission'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t">
                        <span class="text-gray-600 text-sm">จำนวนถอนเงิน</span>
                        <span class="font-bold text-blue-600">{{ number_format($statistics['total_withdrawals']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'คำขอถอนเงิน')

@section('content')
<div class="space-y-6">
    <!-- Header with Stats -->
    <div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="mb-6">
            <h2 class="text-3xl font-bold mb-2">คำขอถอนเงินทั้งหมด</h2>
            <p class="text-indigo-100 text-sm">จัดการและอนุมัติคำขอถอนเงินจากผู้ใช้</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Pending Count -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">รอดำเนินการ</span>
                    <span class="text-2xl">⏳</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['pending_count']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">รายการ</p>
            </div>

            <!-- Pending Amount -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">ยอดรอถอน</span>
                    <span class="text-2xl">💰</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($stats['pending_amount'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">THB</p>
            </div>

            <!-- Approved Today -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">อนุมัติวันนี้</span>
                    <span class="text-2xl">✅</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['approved_today']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">รายการ</p>
            </div>

            <!-- Completed Today -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">โอนวันนี้</span>
                    <span class="text-2xl">✨</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($stats['completed_today']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">รายการ</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex flex-wrap gap-4">
        <a href="{{ route('admin.withdrawals.pending') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-semibold rounded-lg shadow-lg transition-all transform hover:scale-105">
            <span class="mr-2">⏳</span>
            รอดำเนินการ ({{ $stats['pending_count'] }})
        </a>
        <a href="{{ route('admin.withdrawals.index', ['status' => 'approved']) }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg shadow-lg transition-all transform hover:scale-105">
            <span class="mr-2">✅</span>
            อนุมัติแล้ว
        </a>
        <a href="{{ route('admin.withdrawals.index', ['status' => 'completed']) }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg transition-all transform hover:scale-105">
            <span class="mr-2">✨</span>
            โอนเรียบร้อย
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">กรองข้อมูล</h3>
        <form method="GET" action="{{ route('admin.withdrawals.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ $filters['status'] == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="processing" {{ $filters['status'] == 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="approved" {{ $filters['status'] == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="completed" {{ $filters['status'] == 'completed' ? 'selected' : '' }}>โอนเรียบร้อย</option>
                    <option value="rejected" {{ $filters['status'] == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เริ่มต้น</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่สิ้นสุด</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ช่องทางรับเงิน</label>
                <select name="payment_type" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="promptpay" {{ $filters['payment_type'] == 'promptpay' ? 'selected' : '' }}>PromptPay</option>
                    <option value="bank_transfer" {{ $filters['payment_type'] == 'bank_transfer' ? 'selected' : '' }}>โอนธนาคาร</option>
                    <option value="stripe" {{ $filters['payment_type'] == 'stripe' ? 'selected' : '' }}>Stripe</option>
                    <option value="paypal" {{ $filters['payment_type'] == 'paypal' ? 'selected' : '' }}>PayPal</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                    🔍 ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Withdrawal Requests Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">รหัสคำขอ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ขอถอน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ช่องทาง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($withdrawals as $withdrawal)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $withdrawal->request_id }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $withdrawal->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $withdrawal->user->email }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-bold text-indigo-600">{{ number_format($withdrawal->amount, 2) }} THB</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">สุทธิ: {{ number_format($withdrawal->net_amount, 2) }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900 dark:text-white">
                                @if($withdrawal->paymentMethod)
                                    {{ $withdrawal->paymentMethod->type_label }}
                                @else
                                    -
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($withdrawal->status == 'pending') bg-yellow-100 text-yellow-800
                                @elseif($withdrawal->status == 'approved') bg-green-100 text-green-800
                                @elseif($withdrawal->status == 'completed') bg-blue-100 text-blue-800
                                @elseif($withdrawal->status == 'rejected') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $withdrawal->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $withdrawal->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('admin.withdrawals.show', $withdrawal->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                ดูรายละเอียด →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center">
                            <div class="text-gray-400 dark:text-gray-500">
                                <div class="text-4xl mb-2">📭</div>
                                <p>ไม่มีคำขอถอนเงิน</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
        <div class="px-6 py-4 bg-gray-50 dark:bg-slate-700">
            {{ $withdrawals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

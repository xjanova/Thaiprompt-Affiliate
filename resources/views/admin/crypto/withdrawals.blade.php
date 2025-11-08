@extends('layouts.admin')

@section('title', 'Crypto Withdrawals Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">📤 Crypto Withdrawals Management</h1>
                <p class="text-orange-100">จัดการคำขอถอนเงินสกุลเงินดิจิทัล</p>
            </div>
            <a href="{{ route('admin.crypto.dashboard') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-6 py-3 rounded-xl transition font-semibold">
                ← กลับ Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics -->
    @if(isset($stats) && is_array($stats))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">⏳</div>
            <p class="text-white text-opacity-80 text-sm mb-1">รอดำเนินการ</p>
            <p class="text-4xl font-bold">{{ $stats['pending'] ?? 0 }}</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">🔍</div>
            <p class="text-white text-opacity-80 text-sm mb-1">กำลังตรวจสอบ</p>
            <p class="text-4xl font-bold">{{ $stats['reviewing'] ?? 0 }}</p>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">✅</div>
            <p class="text-white text-opacity-80 text-sm mb-1">อนุมัติแล้ว</p>
            <p class="text-4xl font-bold">{{ $stats['approved'] ?? 0 }}</p>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">❌</div>
            <p class="text-white text-opacity-80 text-sm mb-1">ปฏิเสธ</p>
            <p class="text-4xl font-bold">{{ $stats['rejected'] ?? 0 }}</p>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🔍 ตัวกรอง</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ รอดำเนินการ</option>
                    <option value="reviewing" {{ request('status') == 'reviewing' ? 'selected' : '' }}>🔍 กำลังตรวจสอบ</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>✅ อนุมัติแล้ว</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>⚙️ กำลังดำเนินการ</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>✔️ เสร็จสิ้น</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>❌ ปฏิเสธ</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>⚠️ ล้มเหลว</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สกุลเงิน</label>
                <select name="currency" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->code }}" {{ request('currency') == $currency->code ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">User ID</label>
                <input type="text" name="user_id" value="{{ request('user_id') }}"
                    placeholder="ค้นหา User ID"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
            </div>

            <div class="flex items-end">
                <div class="w-full flex gap-2">
                    <button type="submit" class="flex-1 px-6 py-2 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-lg transition font-semibold shadow-lg">
                        🔍 ค้นหา
                    </button>
                    <a href="{{ route('admin.crypto.withdrawals') }}" class="px-6 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg transition font-semibold">
                        ล้าง
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Withdrawals Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                📊 คำขอถอนเงิน ({{ $withdrawals->total() }} รายการ)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">มูลค่า (THB)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ที่อยู่ปลายทาง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($withdrawals as $withdrawal)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $withdrawal->user->name ?? 'ไม่ระบุ' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ID: {{ $withdrawal->user_id }}
                                    </div>
                                    @if($withdrawal->user && $withdrawal->user->email)
                                        <div class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $withdrawal->user->email }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-orange-600 dark:text-orange-400">
                                {{ $withdrawal->amount }} {{ $withdrawal->currency->code ?? '' }}
                            </div>
                            @if($withdrawal->fee)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ค่าธรรมเนียม: {{ $withdrawal->fee }} {{ $withdrawal->currency->code ?? '' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                ฿{{ number_format($withdrawal->amount_thb ?? 0, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-mono text-gray-500 dark:text-gray-400 max-w-xs">
                                <div class="truncate" title="{{ $withdrawal->to_address }}">
                                    {{ $withdrawal->to_address ?? '-' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $withdrawal->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                {{ $withdrawal->status === 'reviewing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                {{ $withdrawal->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                {{ $withdrawal->status === 'processing' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' : '' }}
                                {{ $withdrawal->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                {{ $withdrawal->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                {{ $withdrawal->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                {{ ucfirst($withdrawal->status) }}
                            </span>
                            @if($withdrawal->rejection_reason)
                                <div class="text-xs text-red-600 dark:text-red-400 mt-1">
                                    {{ $withdrawal->rejection_reason }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $withdrawal->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $withdrawal->created_at->format('H:i:s') }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $withdrawal->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if(in_array($withdrawal->status, ['pending', 'reviewing']))
                                <div class="flex flex-col gap-2">
                                    <form action="{{ route('admin.crypto.withdrawals.approve', $withdrawal->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('คุณต้องการอนุมัติคำขอถอนเงินนี้หรือไม่?')"
                                            class="w-full px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-semibold text-xs">
                                            ✅ อนุมัติ
                                        </button>
                                    </form>
                                    <button type="button"
                                        onclick="openRejectModal({{ $withdrawal->id }}, '{{ $withdrawal->user->name ?? 'ไม่ระบุ' }}')"
                                        class="w-full px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold text-xs">
                                        ❌ ปฏิเสธ
                                    </button>
                                </div>
                            @else
                                <div class="flex flex-col gap-2">
                                    <a href="{{ route('admin.crypto.wallets', ['user_id' => $withdrawal->user_id]) }}"
                                       class="text-cyan-600 dark:text-cyan-400 hover:text-cyan-900 dark:hover:text-cyan-300 font-medium text-xs">
                                        ดูกระเป๋า
                                    </a>
                                    @if($withdrawal->tx_hash)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate" title="{{ $withdrawal->tx_hash }}">
                                            TX: {{ substr($withdrawal->tx_hash, 0, 8) }}...
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <span class="text-4xl block mb-2">📭</span>
                                <p class="text-sm">ไม่พบคำขอถอนเงิน</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($withdrawals->hasPages())
        <div class="bg-gray-50 dark:bg-slate-900 px-6 py-4">
            {{ $withdrawals->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-2xl bg-white dark:bg-slate-800">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">❌ ปฏิเสธคำขอถอนเงิน</h3>
                <button type="button" onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ผู้ใช้: <span id="rejectUserName" class="font-bold"></span>
                    </label>
                </div>
                <div class="mb-4">
                    <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เหตุผลในการปฏิเสธ <span class="text-red-600">*</span>
                    </label>
                    <textarea
                        name="reason"
                        id="reason"
                        rows="4"
                        required
                        maxlength="500"
                        placeholder="กรุณาระบุเหตุผลที่ปฏิเสธคำขอถอนเงิน..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-red-500"></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">สูงสุด 500 ตัวอักษร</p>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold">
                        ยืนยันปฏิเสธ
                    </button>
                    <button type="button" onclick="closeRejectModal()" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg transition font-semibold">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openRejectModal(withdrawalId, userName) {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectForm').action = '{{ route("admin.crypto.withdrawals.reject", ":id") }}'.replace(':id', withdrawalId);
    document.getElementById('rejectUserName').textContent = userName;
    document.getElementById('reason').value = '';
    document.getElementById('reason').focus();
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectForm').reset();
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeRejectModal();
    }
});

// Close modal on background click
document.getElementById('rejectModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeRejectModal();
    }
});
</script>
@endpush

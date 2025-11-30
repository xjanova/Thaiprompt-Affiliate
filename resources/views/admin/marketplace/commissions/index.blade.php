@extends('layouts.admin-v3')

@section('title', 'คอมมิชชั่น Marketplace')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คอมมิชชั่น Marketplace Affiliate</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการคอมมิชชั่นจาก Lazada, Shopee, TikTok Shop</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_commissions']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">รายการทั้งหมด</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending_commissions']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">รออนุมัติ</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['approved_commissions']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">อนุมัติแล้ว</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['paid_commissions']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">จ่ายแล้ว</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-orange-600">฿{{ number_format($stats['total_pending_amount'], 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">รอจ่าย</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-emerald-600">฿{{ number_format($stats['total_paid_amount'], 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">จ่ายไปแล้ว</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card p-4 rounded-xl">
        <form method="GET" action="{{ route('admin.marketplace.commissions.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาชื่อผู้ใช้, อีเมล..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <select name="platform" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุก Platform</option>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform->id }}" {{ request('platform') == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รออนุมัติ</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>
            <div class="w-40">
                <select name="type" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุกประเภท</option>
                    <option value="direct" {{ request('type') == 'direct' ? 'selected' : '' }}>Direct</option>
                    <option value="mlm" {{ request('type') == 'mlm' ? 'selected' : '' }}>MLM</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                <i class="fas fa-search mr-1"></i> ค้นหา
            </button>
        </form>
    </div>

    {{-- Bulk Actions --}}
    <div class="flex items-center gap-4" x-data="{ selectedIds: [] }">
        <button type="button" onclick="bulkApprove()"
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition disabled:opacity-50"
                id="bulk-approve-btn" disabled>
            <i class="fas fa-check mr-1"></i> อนุมัติที่เลือก
        </button>
        <button type="button" onclick="bulkPay()"
                class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition disabled:opacity-50"
                id="bulk-pay-btn" disabled>
            <i class="fas fa-money-bill mr-1"></i> จ่ายที่เลือก
        </button>
    </div>

    {{-- Commissions Table --}}
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" id="select-all"
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้รับ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ออเดอร์</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวน</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($commissions as $commission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" class="commission-checkbox w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                       value="{{ $commission->id }}" data-status="{{ $commission->status }}">
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $commission->user->name ?? '-' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $commission->user->email ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.marketplace.orders.show', $commission->order_id) }}"
                                   class="text-blue-600 hover:underline">
                                    {{ $commission->order->external_order_id ?? '-' }}
                                </a>
                                @if($commission->order && $commission->order->platform)
                                    <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium
                                        @if($commission->order->platform->slug == 'lazada') bg-orange-100 text-orange-700
                                        @elseif($commission->order->platform->slug == 'shopee') bg-red-100 text-red-700
                                        @elseif($commission->order->platform->slug == 'tiktok') bg-pink-100 text-pink-700
                                        @endif">
                                        {{ $commission->order->platform->name }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded-lg text-xs font-medium
                                    {{ $commission->commission_type == 'direct' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ $commission->commission_type }}
                                    @if($commission->mlm_level)
                                        (L{{ $commission->mlm_level }})
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                ฿{{ number_format($commission->commission_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'paid' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'รออนุมัติ',
                                        'approved' => 'อนุมัติแล้ว',
                                        'paid' => 'จ่ายแล้ว',
                                        'rejected' => 'ปฏิเสธ',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-lg text-xs font-medium {{ $statusColors[$commission->status] ?? $statusColors['pending'] }}">
                                    {{ $statusLabels[$commission->status] ?? $commission->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $commission->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if($commission->status === 'pending')
                                        <button type="button"
                                                onclick="approveCommission({{ $commission->id }})"
                                                class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                                                title="อนุมัติ">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    @if($commission->status === 'approved')
                                        <button type="button"
                                                onclick="payCommission({{ $commission->id }})"
                                                class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition"
                                                title="จ่ายเงิน">
                                            <i class="fas fa-money-bill"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.marketplace.commissions.show', $commission) }}"
                                       class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-coins text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                <p>ยังไม่มีคอมมิชชั่น</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($commissions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $commissions->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Select All Checkbox
    document.getElementById('select-all').addEventListener('change', function() {
        document.querySelectorAll('.commission-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkButtons();
    });

    // Individual Checkboxes
    document.querySelectorAll('.commission-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkButtons);
    });

    function updateBulkButtons() {
        const checked = document.querySelectorAll('.commission-checkbox:checked');
        const pendingCount = Array.from(checked).filter(cb => cb.dataset.status === 'pending').length;
        const approvedCount = Array.from(checked).filter(cb => cb.dataset.status === 'approved').length;

        document.getElementById('bulk-approve-btn').disabled = pendingCount === 0;
        document.getElementById('bulk-pay-btn').disabled = approvedCount === 0;
    }

    function getSelectedIds(status = null) {
        const checkboxes = document.querySelectorAll('.commission-checkbox:checked');
        if (status) {
            return Array.from(checkboxes)
                .filter(cb => cb.dataset.status === status)
                .map(cb => cb.value);
        }
        return Array.from(checkboxes).map(cb => cb.value);
    }

    function approveCommission(id) {
        if (!confirm('ต้องการอนุมัติคอมมิชชั่นนี้?')) return;

        fetch(`{{ url('admin/marketplace/commissions') }}/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message));
    }

    function payCommission(id) {
        if (!confirm('ต้องการจ่ายคอมมิชชั่นนี้?')) return;

        fetch(`{{ url('admin/marketplace/commissions') }}/${id}/pay`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message));
    }

    function bulkApprove() {
        const ids = getSelectedIds('pending');
        if (ids.length === 0) {
            alert('กรุณาเลือกรายการที่รออนุมัติ');
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
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message));
    }

    function bulkPay() {
        const ids = getSelectedIds('approved');
        if (ids.length === 0) {
            alert('กรุณาเลือกรายการที่อนุมัติแล้ว');
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
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message));
    }
</script>
@endpush
@endsection

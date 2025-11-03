@extends('layouts.admin')

@section('title', 'จัดการคอมมิชชั่น MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">จัดการคอมมิชชั่น MLM</h1>
            <p class="text-gray-600 mt-1">ดูและจัดการคอมมิชชั่น MLM ทั้งหมด</p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('admin.mlm.commissions.approve-all') }}" method="POST" class="inline-block">
                @csrf
                <button type="submit" onclick="return confirm('อนุมัติคอมมิชชั่นที่รออนุมัติทั้งหมด?')"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    อนุมัติทั้งหมด
                </button>
            </form>
            <form action="{{ route('admin.mlm.commissions.pay-all') }}" method="POST" class="inline-block">
                @csrf
                <button type="submit" onclick="return confirm('จ่ายคอมมิชชั่นที่อนุมัติแล้วทั้งหมด?')"
                        class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-2.5 rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    จ่ายทั้งหมด
                </button>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">รออนุมัติ</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['pending_count']) }}</h3>
                    <p class="text-sm mt-1 opacity-90">฿{{ number_format($stats['pending_amount'], 2) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">อนุมัติแล้ว</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['approved_count']) }}</h3>
                    <p class="text-sm mt-1 opacity-90">฿{{ number_format($stats['approved_amount'], 2) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">จ่ายแล้ว</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['paid_count']) }}</h3>
                    <p class="text-sm mt-1 opacity-90">฿{{ number_format($stats['paid_amount'], 2) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">แผน MLM</label>
                <select name="plan_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">สถานะ</label>
                <select name="status" class="w-full border-gray-300 rounded-lg">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รออนุมัติ</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ประเภท</label>
                <select name="type" class="w-full border-gray-300 rounded-lg">
                    <option value="">ทั้งหมด</option>
                    <option value="unilevel" {{ request('type') == 'unilevel' ? 'selected' : '' }}>Unilevel</option>
                    <option value="binary" {{ request('type') == 'binary' ? 'selected' : '' }}>Binary</option>
                    <option value="direct_sponsor" {{ request('type') == 'direct_sponsor' ? 'selected' : '' }}>Direct Sponsor</option>
                    <option value="matching" {{ request('type') == 'matching' ? 'selected' : '' }}>Matching</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เริ่มต้น</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่สิ้นสุด</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <input type="checkbox" id="select-all" class="rounded border-gray-300">
                    <label for="select-all" class="text-sm text-gray-700">เลือกทั้งหมด</label>
                </div>
                <div class="flex gap-2">
                    <button onclick="bulkAction('approve')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                        อนุมัติที่เลือก
                    </button>
                    <button onclick="bulkAction('pay')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        จ่ายที่เลือก
                    </button>
                    <button onclick="bulkAction('reject')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                        ปฏิเสธที่เลือก
                    </button>
                </div>
            </div>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                        <input type="checkbox" class="rounded border-white">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">สมาชิก</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ประเภท</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">จาก</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">จำนวน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">วันที่</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($commissions as $commission)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" name="commission_ids[]" value="{{ $commission->id }}" class="commission-checkbox rounded border-gray-300">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $commission->member->user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $commission->member->member_code }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full
                            {{ $commission->type === 'unilevel' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $commission->type === 'binary' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $commission->type === 'direct_sponsor' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $commission->type === 'matching' ? 'bg-pink-100 text-pink-800' : '' }}">
                            {{ ucfirst($commission->type) }}
                        </span>
                        @if($commission->level)
                            <span class="text-xs text-gray-500 ml-1">Lv.{{ $commission->level }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($commission->fromMember)
                            <div class="text-sm">{{ $commission->fromMember->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $commission->fromMember->member_code }}</div>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-green-600">฿{{ number_format($commission->commission_amount, 2) }}</div>
                        @if($commission->pv_amount)
                            <div class="text-xs text-gray-500">{{ number_format($commission->pv_amount, 2) }} PV</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $commission->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $commission->status === 'paid' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $commission->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($commission->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $commission->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.mlm.commissions.show', $commission) }}" class="text-blue-600 hover:text-blue-900 mr-3">ดูข้อมูล</a>
                        @if($commission->status === 'pending')
                            <button onclick="approveCommission({{ $commission->id }})" class="text-green-600 hover:text-green-900 mr-3">อนุมัติ</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        ไม่พบข้อมูลคอมมิชชั่น
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $commissions->links() }}
    </div>
</div>

<script>
// Select All Checkbox
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.commission-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
});

// Bulk Actions
function bulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.commission-checkbox:checked');
    const ids = Array.from(checkedBoxes).map(cb => cb.value);

    if (ids.length === 0) {
        alert('กรุณาเลือกคอมมิชชั่นที่ต้องการดำเนินการ');
        return;
    }

    let confirmMessage = '';
    let url = '';

    switch(action) {
        case 'approve':
            confirmMessage = `อนุมัติคอมมิชชั่น ${ids.length} รายการ?`;
            url = '{{ route("admin.mlm.commissions.approve") }}';
            break;
        case 'pay':
            confirmMessage = `จ่ายคอมมิชชั่น ${ids.length} รายการ?`;
            url = '{{ route("admin.mlm.commissions.pay") }}';
            break;
        case 'reject':
            const reason = prompt('กรุณาระบุเหตุผล:');
            if (!reason) return;
            confirmMessage = `ปฏิเสธคอมมิชชั่น ${ids.length} รายการ?`;
            url = '{{ route("admin.mlm.commissions.bulk-action") }}';
            break;
    }

    if (!confirm(confirmMessage)) return;

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('action', action);
    ids.forEach(id => formData.append('commission_ids[]', id));
    if (action === 'reject') {
        formData.append('reason', reason);
    }

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        alert('เกิดข้อผิดพลาด: ' + error.message);
    });
}

// Single Commission Approve
function approveCommission(id) {
    if (!confirm('อนุมัติคอมมิชชั่นนี้?')) return;

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('commission_ids[]', id);

    fetch('{{ route("admin.mlm.commissions.approve") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('อนุมัติคอมมิชชั่นสำเร็จ');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด');
        }
    });
}
</script>
@endsection

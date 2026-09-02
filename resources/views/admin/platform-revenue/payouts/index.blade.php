@extends('layouts.admin-v3')

@section('title', 'Payout Requests')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-orange-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-money-bill-wave text-orange-400"></i>
                        Payout Requests
                    </h1>
                    <p class="text-white/60">จัดการคำขอถอนเงินของผู้ใช้</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.platform-revenue.payouts.settings') }}"
                       class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white hover:bg-white/20 transition">
                        <i class="fas fa-cog mr-2"></i>ตั้งค่า
                    </a>
                    <a href="{{ route('admin.platform-revenue.index') }}"
                       class="px-4 py-2 bg-orange-500 rounded-xl text-white hover:bg-orange-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i>กลับ Dashboard
                    </a>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-white/60 text-sm mb-1">รอดำเนินการ</div>
                <div class="text-3xl font-bold text-orange-400">{{ $stats['pending_count'] ?? 0 }}</div>
                <div class="text-white/40 text-sm mt-1">฿{{ number_format($stats['pending_amount'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-white/60 text-sm mb-1">สำเร็จ</div>
                <div class="text-3xl font-bold text-green-400">{{ $stats['completed_count'] ?? 0 }}</div>
                <div class="text-white/40 text-sm mt-1">฿{{ number_format($stats['completed_amount'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-white/60 text-sm mb-1">ค่าธรรมเนียมรวม</div>
                <div class="text-3xl font-bold text-cyan-400">฿{{ number_format($stats['total_fee_amount'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-white/60 text-sm mb-1">จ่ายรวม</div>
                <div class="text-3xl font-bold text-white">฿{{ number_format($stats['total_net_amount'] ?? 0, 2) }}</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="text-white/60 text-sm block mb-1">สถานะ</label>
                    <select name="status" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                        <option value="scheduled" {{ ($filters['status'] ?? '') === 'scheduled' ? 'selected' : '' }}>ตั้งเวลาจ่ายแล้ว</option>
                        <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                        <option value="processing" {{ ($filters['status'] ?? '') === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                        <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>สำเร็จ</option>
                        <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                    </select>
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">ประเภทรายได้</label>
                    <select name="earning_type" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <option value="">ทั้งหมด</option>
                        <option value="seller_sale" {{ ($filters['earning_type'] ?? '') === 'seller_sale' ? 'selected' : '' }}>รายได้ขาย</option>
                        <option value="mlm_commission" {{ ($filters['earning_type'] ?? '') === 'mlm_commission' ? 'selected' : '' }}>MLM Commission</option>
                        <option value="affiliate_commission" {{ ($filters['earning_type'] ?? '') === 'affiliate_commission' ? 'selected' : '' }}>Affiliate Commission</option>
                    </select>
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">จากวันที่</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">ถึงวันที่</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-2 bg-orange-500 rounded-xl text-white hover:bg-orange-600 transition">
                        <i class="fas fa-search mr-2"></i>ค้นหา
                    </button>
                </div>
            </form>
        </div>

        {{-- Bulk Actions --}}
        <form id="bulkForm" method="POST" action="{{ route('admin.platform-revenue.payouts.bulk-approve') }}">
            @csrf
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
                <div class="p-4 border-b border-white/10 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <input type="checkbox" id="selectAll" class="w-5 h-5 rounded">
                        <span class="text-white/60 text-sm">เลือกทั้งหมด</span>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-green-500 rounded-xl text-white hover:bg-green-600 transition hidden" id="bulkApproveBtn">
                        <i class="fas fa-check mr-2"></i>อนุมัติที่เลือก
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-white/60 text-left text-sm bg-white/5">
                                <th class="p-4 font-medium w-12"></th>
                                <th class="p-4 font-medium">ID</th>
                                <th class="p-4 font-medium">ผู้ใช้</th>
                                <th class="p-4 font-medium">ประเภท</th>
                                <th class="p-4 font-medium">ยอดเงิน</th>
                                <th class="p-4 font-medium">ค่าธรรมเนียม</th>
                                <th class="p-4 font-medium">ยอดสุทธิ</th>
                                <th class="p-4 font-medium">สถานะ</th>
                                <th class="p-4 font-medium">วันที่</th>
                                <th class="p-4 font-medium text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="text-white">
                            @forelse($payouts as $payout)
                            <tr class="border-t border-white/10 hover:bg-white/5">
                                <td class="p-4">
                                    @if($payout->status === 'pending')
                                    <input type="checkbox" name="payout_ids[]" value="{{ $payout->id }}" class="payout-checkbox w-5 h-5 rounded">
                                    @endif
                                </td>
                                <td class="p-4 font-mono text-sm">#{{ $payout->id }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center text-white font-bold">
                                            {{ substr($payout->user->name ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ $payout->user->name ?? 'User #'.$payout->user_id }}</div>
                                            <div class="text-white/40 text-sm">{{ $payout->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 bg-white/10 rounded-lg text-sm">{{ $payout->earning_type }}</span>
                                </td>
                                <td class="p-4 font-mono">฿{{ number_format($payout->amount, 2) }}</td>
                                <td class="p-4 font-mono text-red-400">-฿{{ number_format($payout->fee, 2) }}</td>
                                <td class="p-4 font-mono text-green-400 font-bold">฿{{ number_format($payout->net_amount, 2) }}</td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full text-sm
                                        {{ $payout->status === 'pending' ? 'bg-orange-500/20 text-orange-400' : '' }}
                                        {{ $payout->status === 'scheduled' ? 'bg-indigo-500/20 text-indigo-400' : '' }}
                                        {{ $payout->status === 'approved' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                        {{ $payout->status === 'processing' ? 'bg-cyan-500/20 text-cyan-400' : '' }}
                                        {{ $payout->status === 'paid' ? 'bg-green-500/20 text-green-400' : '' }}
                                        {{ $payout->status === 'rejected' ? 'bg-red-500/20 text-red-400' : '' }}
                                        {{ $payout->status === 'failed' ? 'bg-red-500/20 text-red-400' : '' }}
                                    ">
                                        {{ $payout->status_label }}
                                    </span>
                                </td>
                                <td class="p-4 text-white/40 text-sm">{{ $payout->created_at->format('d/m/Y H:i') }}</td>
                                <td class="p-4 text-right">
                                    <a href="{{ route('admin.platform-revenue.payouts.show', $payout) }}"
                                       class="px-3 py-1 bg-white/10 rounded-lg text-white hover:bg-white/20 transition text-sm">
                                        <i class="fas fa-eye mr-1"></i>ดู
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="p-8 text-center text-white/40">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p>ไม่พบรายการ Payout</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-white/10">
                    {{ $payouts->links() }}
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.payout-checkbox');
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');

    function updateBulkButton() {
        const checked = document.querySelectorAll('.payout-checkbox:checked').length;
        bulkApproveBtn.classList.toggle('hidden', checked === 0);
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkButton();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkButton);
    });
});
</script>
@endpush

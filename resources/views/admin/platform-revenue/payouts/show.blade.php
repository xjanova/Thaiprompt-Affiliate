@extends('layouts.admin-v3')

@section('title', "Payout Request #{$payout->id}")

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-orange-900 to-gray-900 py-8 px-4">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.platform-revenue.payouts.index') }}"
               class="text-orange-400 hover:text-orange-300 mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>กลับรายการ Payout
            </a>
            <h1 class="text-4xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-money-bill-wave text-orange-400"></i>
                Payout Request #{{ $payout->id }}
            </h1>
        </div>

        {{-- Status Banner --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center
                        {{ $payout->status === 'pending' ? 'bg-orange-500/20' : '' }}
                        {{ $payout->status === 'scheduled' ? 'bg-indigo-500/20' : '' }}
                        {{ $payout->status === 'approved' ? 'bg-blue-500/20' : '' }}
                        {{ $payout->status === 'processing' ? 'bg-cyan-500/20' : '' }}
                        {{ $payout->status === 'paid' ? 'bg-green-500/20' : '' }}
                        {{ $payout->status === 'rejected' ? 'bg-red-500/20' : '' }}
                        {{ $payout->status === 'failed' ? 'bg-red-500/20' : '' }}
                    ">
                        <i class="fas text-3xl
                            {{ $payout->status === 'pending' ? 'fa-clock text-orange-400' : '' }}
                            {{ $payout->status === 'scheduled' ? 'fa-calendar-check text-indigo-400' : '' }}
                            {{ $payout->status === 'approved' ? 'fa-check text-blue-400' : '' }}
                            {{ $payout->status === 'processing' ? 'fa-spinner fa-spin text-cyan-400' : '' }}
                            {{ $payout->status === 'paid' ? 'fa-check-circle text-green-400' : '' }}
                            {{ $payout->status === 'rejected' ? 'fa-times-circle text-red-400' : '' }}
                            {{ $payout->status === 'failed' ? 'fa-exclamation-circle text-red-400' : '' }}
                        "></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white">{{ $payout->status_label }}</div>
                        <div class="text-white/60">
                            @if($payout->status === 'pending')
                                รอการอนุมัติจาก Admin
                            @elseif($payout->status === 'scheduled')
                                ตั้งเวลาจ่ายอัตโนมัติไว้วันที่ {{ $payout->scheduled_at?->format('d/m/Y H:i') }}
                            @elseif($payout->status === 'approved')
                                อนุมัติแล้ว รอดำเนินการ
                            @elseif($payout->status === 'paid')
                                จ่ายเงินสำเร็จเมื่อ {{ $payout->paid_at?->format('d/m/Y H:i') }}
                            @elseif($payout->status === 'rejected')
                                ถูกปฏิเสธ: {{ $payout->rejection_reason }}
                            @endif
                        </div>
                    </div>
                </div>

                @if($payout->status === 'pending')
                <div class="flex gap-3">
                    <form method="POST" action="{{ route('admin.platform-revenue.payouts.approve', $payout) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-green-500 rounded-xl text-white hover:bg-green-600 transition">
                            <i class="fas fa-check mr-2"></i>อนุมัติ
                        </button>
                    </form>
                    <button onclick="showRejectModal()" class="px-6 py-3 bg-red-500 rounded-xl text-white hover:bg-red-600 transition">
                        <i class="fas fa-times mr-2"></i>ปฏิเสธ
                    </button>
                </div>
                @elseif($payout->status === 'approved')
                <form method="POST" action="{{ route('admin.platform-revenue.payouts.process', $payout) }}">
                    @csrf
                    <button type="submit" class="px-6 py-3 bg-cyan-500 rounded-xl text-white hover:bg-cyan-600 transition">
                        <i class="fas fa-play mr-2"></i>ดำเนินการจ่ายเงิน
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- User Info --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">ข้อมูลผู้ใช้</h2>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        {{ substr($payout->user->name ?? '?', 0, 1) }}
                    </div>
                    <div>
                        <div class="text-xl font-bold text-white">{{ $payout->user->name ?? 'User #'.$payout->user_id }}</div>
                        <div class="text-white/60">{{ $payout->user->email ?? '' }}</div>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between p-2 bg-white/5 rounded-lg">
                        <span class="text-white/60">User ID</span>
                        <span class="text-white">#{{ $payout->user_id }}</span>
                    </div>
                    <div class="flex justify-between p-2 bg-white/5 rounded-lg">
                        <span class="text-white/60">ประเภทรายได้</span>
                        <span class="text-white">{{ $payout->earning_type }}</span>
                    </div>
                    <div class="flex justify-between p-2 bg-white/5 rounded-lg">
                        <span class="text-white/60">วิธีจ่าย</span>
                        <span class="text-white">{{ $payout->payment_method ?? 'โอนเข้า Wallet' }}</span>
                    </div>
                </div>
            </div>

            {{-- Amount Details --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">รายละเอียดเงิน</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">ยอดขอถอน</span>
                        <span class="text-white font-bold text-lg">฿{{ number_format($payout->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">ค่าธรรมเนียม</span>
                        <span class="text-red-400">-฿{{ number_format($payout->fee, 2) }}</span>
                    </div>
                    @if($payout->debt_deduction > 0)
                    <div class="flex justify-between items-center p-3 bg-red-500/10 rounded-xl border border-red-500/30">
                        <span class="text-red-400">หักชำระหนี้</span>
                        <span class="text-red-400">-฿{{ number_format($payout->debt_deduction, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center p-4 bg-green-500/20 rounded-xl border border-green-500/30">
                        <span class="text-green-400 font-bold">ยอดสุทธิที่ได้รับ</span>
                        <span class="text-green-400 font-bold text-2xl">฿{{ number_format($payout->net_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-6">
            <h2 class="text-xl font-bold text-white mb-4">ไทม์ไลน์</h2>
            <div class="space-y-4">
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-plus text-blue-400"></i>
                    </div>
                    <div>
                        <div class="text-white font-medium">สร้างคำขอ</div>
                        <div class="text-white/40 text-sm">{{ $payout->created_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                </div>

                @if($payout->approved_at)
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-400"></i>
                    </div>
                    <div>
                        <div class="text-white font-medium">อนุมัติโดย {{ $payout->approvedByUser->name ?? 'Admin' }}</div>
                        <div class="text-white/40 text-sm">{{ $payout->approved_at->format('d/m/Y H:i:s') }}</div>
                        @if($payout->approval_note)
                        <div class="text-white/60 text-sm mt-1">หมายเหตุ: {{ $payout->approval_note }}</div>
                        @endif
                    </div>
                </div>
                @endif

                @if($payout->paid_at)
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-emerald-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-emerald-400"></i>
                    </div>
                    <div>
                        <div class="text-white font-medium">จ่ายเงินสำเร็จ</div>
                        <div class="text-white/40 text-sm">{{ $payout->paid_at->format('d/m/Y H:i:s') }}</div>
                        @if($payout->payment_reference)
                        <div class="text-white/60 text-sm mt-1">Ref: {{ $payout->payment_reference }}</div>
                        @endif
                    </div>
                </div>
                @endif

                @if($payout->rejected_at)
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-red-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-times text-red-400"></i>
                    </div>
                    <div>
                        <div class="text-white font-medium">ปฏิเสธโดย {{ $payout->rejectedByUser->name ?? 'Admin' }}</div>
                        <div class="text-white/40 text-sm">{{ $payout->rejected_at->format('d/m/Y H:i:s') }}</div>
                        <div class="text-red-400 text-sm mt-1">เหตุผล: {{ $payout->rejection_reason }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Linked Earnings --}}
        @if($payout->earnings && $payout->earnings->count() > 0)
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
            <h2 class="text-xl font-bold text-white mb-4">รายได้ที่เกี่ยวข้อง</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-white/60 text-left">
                            <th class="pb-3">ID</th>
                            <th class="pb-3">ประเภท</th>
                            <th class="pb-3">ยอดสุทธิ</th>
                            <th class="pb-3">วันที่</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        @foreach($payout->earnings as $earning)
                        <tr class="border-t border-white/10">
                            <td class="py-2">#{{ $earning->id }}</td>
                            <td class="py-2">{{ $earning->earning_type }}</td>
                            <td class="py-2 text-green-400">฿{{ number_format($earning->net_amount, 2) }}</td>
                            <td class="py-2 text-white/40">{{ $earning->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-2xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-white mb-4">ปฏิเสธ Payout Request</h3>
        <form method="POST" action="{{ route('admin.platform-revenue.payouts.reject', $payout) }}">
            @csrf
            <div class="mb-4">
                <label class="text-white/60 text-sm block mb-2">เหตุผลในการปฏิเสธ *</label>
                <textarea name="reason" rows="3" required
                          class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400"
                          placeholder="ระบุเหตุผล..."></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="hideRejectModal()"
                        class="px-4 py-2 bg-white/10 rounded-xl text-white hover:bg-white/20 transition">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-red-500 rounded-xl text-white hover:bg-red-600 transition">
                    <i class="fas fa-times mr-2"></i>ยืนยันปฏิเสธ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}
</script>
@endpush

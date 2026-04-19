@extends('layouts.admin-v3')

@section('title', "Debt #{$debt->id}")

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-red-900 to-gray-900 py-8 px-4">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.platform-revenue.debts.index') }}" class="text-red-400 hover:text-red-300 mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>กลับรายการหนี้
            </a>
            <h1 class="text-4xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-file-invoice-dollar text-red-400"></i>
                Debt #{{ $debt->id }}
            </h1>
        </div>

        {{-- Status Banner --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-{{ $debt->status_color }}-500/30 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center bg-{{ $debt->status_color }}-500/20">
                        <i class="fas text-3xl text-{{ $debt->status_color }}-400
                            {{ $debt->status === 'active' ? 'fa-exclamation-circle' : '' }}
                            {{ $debt->status === 'paid' ? 'fa-check-circle' : '' }}
                            {{ $debt->status === 'waived' ? 'fa-hand-holding-usd' : '' }}
                            {{ $debt->status === 'cancelled' ? 'fa-times-circle' : '' }}
                        "></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white">{{ $debt->status_label }}</div>
                        <div class="text-white/60">{{ $debt->reason }}</div>
                    </div>
                </div>

                @if($debt->status === 'active')
                <div class="flex gap-3">
                    <button onclick="showWaiveModal()" class="px-6 py-3 bg-yellow-500 rounded-xl text-white hover:bg-yellow-600 transition">
                        <i class="fas fa-hand-holding-usd mr-2"></i>ยกเว้น
                    </button>
                    <form method="POST" action="{{ route('admin.platform-revenue.debts.cancel', $debt) }}" onsubmit="return confirm('ยืนยันยกเลิกหนี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-6 py-3 bg-red-500 rounded-xl text-white hover:bg-red-600 transition">
                            <i class="fas fa-times mr-2"></i>ยกเลิก
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- User Info --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">ข้อมูลผู้ใช้</h2>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-400 to-pink-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        {{ substr($debt->user->name ?? '?', 0, 1) }}
                    </div>
                    <div>
                        <div class="text-xl font-bold text-white">{{ $debt->user->name ?? 'User #'.$debt->user_id }}</div>
                        <div class="text-white/60">{{ $debt->user->email ?? '' }}</div>
                    </div>
                </div>
            </div>

            {{-- Amount Details --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">รายละเอียดหนี้</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">ยอดหนี้เดิม</span>
                        <span class="text-red-400 font-bold">฿{{ number_format($debt->original_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">หักชำระแล้ว</span>
                        <span class="text-green-400 font-bold">฿{{ number_format($debt->deducted_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-{{ $debt->remaining_amount > 0 ? 'red' : 'green' }}-500/20 rounded-xl border border-{{ $debt->remaining_amount > 0 ? 'red' : 'green' }}-500/30">
                        <span class="text-{{ $debt->remaining_amount > 0 ? 'red' : 'green' }}-400 font-bold">คงเหลือ</span>
                        <span class="text-{{ $debt->remaining_amount > 0 ? 'red' : 'green' }}-400 font-bold text-2xl">฿{{ number_format($debt->remaining_amount, 2) }}</span>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mt-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-white/60">ความคืบหน้า</span>
                            <span class="text-white">{{ number_format($debt->paid_percentage, 1) }}%</span>
                        </div>
                        <div class="h-3 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-green-400 to-emerald-500 transition-all" style="width: {{ min($debt->paid_percentage, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Deduction History --}}
        @if(count($deductionHistory) > 0)
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
            <h2 class="text-xl font-bold text-white mb-4">ประวัติการหัก</h2>
            <div class="space-y-3">
                @foreach($deductionHistory as $history)
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-minus text-green-400"></i>
                        </div>
                        <div>
                            <div class="text-white font-medium">หัก ฿{{ number_format($history['amount'], 2) }}</div>
                            <div class="text-white/40 text-sm">{{ \Carbon\Carbon::parse($history['date'])->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    @if($history['from_earning_id'])
                    <span class="text-white/40 text-sm">จาก Earning #{{ $history['from_earning_id'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Waive Modal --}}
<div id="waiveModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-2xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-white mb-4">ยกเว้นหนี้</h3>
        <form method="POST" action="{{ route('admin.platform-revenue.debts.waive', $debt) }}">
            @csrf
            <div class="mb-4">
                <label class="text-white/60 text-sm block mb-2">เหตุผล *</label>
                <textarea name="reason" rows="3" required class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="hideWaiveModal()" class="px-4 py-2 bg-white/10 rounded-xl text-white">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-yellow-500 rounded-xl text-white">ยืนยัน</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showWaiveModal() {
    document.getElementById('waiveModal').classList.remove('hidden');
    document.getElementById('waiveModal').classList.add('flex');
}
function hideWaiveModal() {
    document.getElementById('waiveModal').classList.add('hidden');
    document.getElementById('waiveModal').classList.remove('flex');
}
</script>
@endpush

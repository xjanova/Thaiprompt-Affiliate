@extends('layouts.user-arrow-x')

@section('title', 'หนี้ค้างชำระ')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Hero Header (Red-Orange gradient for Debt) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-red-600 via-orange-600 to-amber-600 dark:from-red-800 dark:via-orange-800 dark:to-amber-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('user.wallet.index') }}" class="glass-fusion px-4 py-2 hover:bg-white/25 rounded-lg transition-all text-white">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-file-invoice-dollar text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">หนี้ค้างชำระ</h1>
                    <p class="text-orange-100 text-lg mt-1">รายการหนี้และประวัติการชำระ</p>
                </div>
            </div>

            {{-- ยอดหนี้คงเหลือ --}}
            @if($debtSummary['has_active_debt'])
            <div class="bg-white/20 dark:bg-white/10 backdrop-blur-xl border border-white/30 rounded-2xl p-6">
                <p class="text-orange-100 text-sm mb-2">ยอดหนี้คงเหลือ</p>
                <p class="text-5xl md:text-6xl font-bold text-white drop-shadow-lg">฿{{ number_format($debtSummary['total_debt'], 2) }}</p>
                <p class="text-orange-100 text-sm mt-2">{{ $debtSummary['debt_count'] }} รายการค้างชำระ</p>
            </div>
            @else
            <div class="bg-white/20 dark:bg-white/10 backdrop-blur-xl border border-white/30 rounded-2xl p-6">
                <p class="text-green-100 text-sm mb-2">สถานะ</p>
                <p class="text-3xl font-bold text-white drop-shadow-lg">ไม่มีหนี้ค้างชำระ</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">💰</div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">ยอดหนี้คงเหลือ</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400">฿{{ number_format($debtSummary['total_debt'], 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">📋</div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">จำนวนหนี้ค้าง</p>
                    <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $debtSummary['debt_count'] }} รายการ</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">✅</div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">ชำระแล้ว</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($debtSummary['total_paid'], 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">📊</div>
                <div>
                    @php
                        $totalOriginal = $debtSummary['total_debt'] + $debtSummary['total_paid'];
                        $paidPercent = $totalOriginal > 0 ? round(($debtSummary['total_paid'] / $totalOriginal) * 100, 1) : 100;
                    @endphp
                    <p class="text-xs text-gray-600 dark:text-gray-400">อัตราชำระ</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $paidPercent }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Banner --}}
    @if($debtSummary['has_active_debt'])
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-2 border-amber-300 dark:border-amber-700 rounded-2xl p-5">
        <div class="flex items-start gap-3">
            <div class="text-3xl mt-1">
                <i class="fas fa-info-circle text-amber-500"></i>
            </div>
            <div>
                <h4 class="text-lg font-bold text-amber-800 dark:text-amber-300">ระบบหักหนี้อัตโนมัติ</h4>
                <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                    หนี้จะถูกหักอัตโนมัติ <strong>สูงสุด 50%</strong> จากรายได้ใหม่ทุกครั้งที่คุณมีรายรับเข้า Wallet
                    จนกว่าจะชำระครบ ไม่ต้องดำเนินการใดๆ เพิ่มเติม
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Filter --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-4">
        <form method="GET" action="{{ route('user.wallet.debts') }}" class="flex flex-wrap items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">กรองสถานะ:</label>
            <select name="status" onchange="this.form.submit()"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">ทั้งหมด</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ค้างชำระ</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                <option value="waived" {{ request('status') === 'waived' ? 'selected' : '' }}>ยกเว้น</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>
            @if(request('status'))
                <a href="{{ route('user.wallet.debts') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times mr-1"></i>ล้างตัวกรอง
                </a>
            @endif
        </form>
    </div>

    {{-- Debt List --}}
    <div class="space-y-4" x-data="{ expanded: null }">
        @forelse($debts as $debt)
            <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden transition-all duration-200 hover:shadow-2xl">
                {{-- Debt Card Header --}}
                <div class="p-5 cursor-pointer" @click="expanded === {{ $debt->id }} ? expanded = null : expanded = {{ $debt->id }}">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        {{-- Left: Info --}}
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center
                                @if($debt->status === 'active') bg-red-100 dark:bg-red-900/30
                                @elseif($debt->status === 'paid') bg-green-100 dark:bg-green-900/30
                                @elseif($debt->status === 'waived') bg-yellow-100 dark:bg-yellow-900/30
                                @else bg-gray-100 dark:bg-gray-900/30
                                @endif">
                                <i class="fas
                                    @if($debt->status === 'active') fa-exclamation-triangle text-red-600 dark:text-red-400
                                    @elseif($debt->status === 'paid') fa-check-circle text-green-600 dark:text-green-400
                                    @elseif($debt->status === 'waived') fa-hand-holding-heart text-yellow-600 dark:text-yellow-400
                                    @else fa-times-circle text-gray-600 dark:text-gray-400
                                    @endif text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $debt->reason ?? 'หนี้จากระบบ' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $debt->source_type }} #{{ $debt->source_id }}
                                    &middot; {{ $debt->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>

                        {{-- Right: Amount & Status --}}
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-lg font-bold text-red-600 dark:text-red-400">฿{{ number_format($debt->original_amount, 2) }}</p>
                                @if($debt->status === 'active' && $debt->deducted_amount > 0)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">คงเหลือ ฿{{ number_format($debt->remaining_amount, 2) }}</p>
                                @endif
                            </div>

                            {{-- Status Badge --}}
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold
                                @if($debt->status === 'active') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                @elseif($debt->status === 'paid') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                @elseif($debt->status === 'waived') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                @else bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300
                                @endif">
                                {{ $debt->status_label }}
                            </span>

                            {{-- Expand Icon --}}
                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200"
                               :class="expanded === {{ $debt->id }} ? 'rotate-180' : ''"></i>
                        </div>
                    </div>

                    {{-- Progress Bar (สำหรับหนี้ active) --}}
                    @if($debt->status === 'active' && $debt->original_amount > 0)
                    <div class="mt-4">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <span>ชำระแล้ว {{ number_format($debt->paid_percentage, 1) }}%</span>
                            <span>฿{{ number_format($debt->deducted_amount, 2) }} / ฿{{ number_format($debt->original_amount, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div class="bg-gradient-to-r from-orange-500 to-red-500 h-2.5 rounded-full transition-all duration-500"
                                 style="width: {{ min($debt->paid_percentage, 100) }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Expanded Detail --}}
                <div x-show="expanded === {{ $debt->id }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="border-t border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 p-5">

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ยอดเดิม</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">฿{{ number_format($debt->original_amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">หักแล้ว</p>
                            <p class="text-sm font-bold text-green-600 dark:text-green-400">฿{{ number_format($debt->deducted_amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">คงเหลือ</p>
                            <p class="text-sm font-bold text-red-600 dark:text-red-400">฿{{ number_format($debt->remaining_amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ลำดับความสำคัญ</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $debt->priority }}</p>
                        </div>
                    </div>

                    @if($debt->fully_paid_at)
                    <p class="text-xs text-green-600 dark:text-green-400 mb-2">
                        <i class="fas fa-check-circle mr-1"></i>ชำระครบวันที่ {{ $debt->fully_paid_at->format('d/m/Y H:i') }}
                    </p>
                    @endif

                    @if($debt->waived_at)
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 mb-2">
                        <i class="fas fa-hand-holding-heart mr-1"></i>ยกเว้นวันที่ {{ $debt->waived_at->format('d/m/Y H:i') }}
                        @if($debt->waive_reason)
                            — {{ $debt->waive_reason }}
                        @endif
                    </p>
                    @endif

                    {{-- Deduction History --}}
                    @php
                        $history = $debt->metadata['deduction_history'] ?? [];
                    @endphp
                    @if(!empty($history))
                    <div class="mt-4">
                        <h5 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-history mr-1"></i>ประวัติการหัก
                        </h5>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach(array_reverse($history) as $entry)
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg px-3 py-2 text-xs border border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y H:i') }}
                                </span>
                                <span class="font-bold text-green-600 dark:text-green-400">
                                    -฿{{ number_format($entry['amount'], 2) }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        @empty
            {{-- Empty State --}}
            <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-12 text-center">
                <div class="inline-block p-6 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/20 dark:to-emerald-900/20 rounded-full mb-4">
                    <i class="fas fa-check-circle text-6xl text-green-600 dark:text-green-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่มีหนี้ค้างชำระ</h3>
                <p class="text-gray-600 dark:text-gray-400">คุณไม่มีรายการหนี้{{ request('status') ? 'ในสถานะที่เลือก' : '' }}</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($debts->hasPages())
    <div class="flex justify-center">
        {{ $debts->withQueryString()->links() }}
    </div>
    @endif
</div>

@push('scripts')
<style>
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}
</style>
@endpush
@endsection

@extends('layouts.admin-v3')

@section('title', 'Wallet Debts')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-red-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                        Wallet Debts
                    </h1>
                    <p class="text-white/60">จัดการหนี้และยอดติดลบของผู้ใช้</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.platform-revenue.debts.create') }}"
                       class="px-4 py-2 bg-red-500 rounded-xl text-white hover:bg-red-600 transition">
                        <i class="fas fa-plus mr-2"></i>สร้างหนี้ใหม่
                    </a>
                    <form method="POST" action="{{ route('admin.platform-revenue.debts.batch-collect') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white hover:bg-white/20 transition">
                            <i class="fas fa-sync mr-2"></i>เก็บหนี้ Batch
                        </button>
                    </form>
                    <a href="{{ route('admin.platform-revenue.debts.export') }}"
                       class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white hover:bg-white/20 transition">
                        <i class="fas fa-download mr-2"></i>Export
                    </a>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-red-500/30">
                <div class="text-white/60 text-sm mb-1">หนี้ค้างชำระ</div>
                <div class="text-3xl font-bold text-red-400">{{ $stats['active']['count'] ?? 0 }}</div>
                <div class="text-white/40 text-sm mt-1">฿{{ number_format($stats['active']['amount'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-green-500/30">
                <div class="text-white/60 text-sm mb-1">ชำระแล้ว</div>
                <div class="text-3xl font-bold text-green-400">{{ $stats['paid']['count'] ?? 0 }}</div>
                <div class="text-white/40 text-sm mt-1">฿{{ number_format($stats['paid']['amount'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-yellow-500/30">
                <div class="text-white/60 text-sm mb-1">ยกเว้น</div>
                <div class="text-3xl font-bold text-yellow-400">{{ $stats['waived']['count'] ?? 0 }}</div>
                <div class="text-white/40 text-sm mt-1">฿{{ number_format($stats['waived']['amount'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-cyan-500/30">
                <div class="text-white/60 text-sm mb-1">อัตราเก็บหนี้</div>
                <div class="text-3xl font-bold text-cyan-400">{{ number_format($stats['collection_rate'] ?? 0, 1) }}%</div>
                <div class="text-white/40 text-sm mt-1">เก็บได้/ทั้งหมด</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="text-white/60 text-sm block mb-1">สถานะ</label>
                    <select name="status" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>ค้างชำระ</option>
                        <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                        <option value="waived" {{ ($filters['status'] ?? '') === 'waived' ? 'selected' : '' }}>ยกเว้น</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    </select>
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">แหล่งที่มา</label>
                    <select name="source_type" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400">
                        <option value="">ทั้งหมด</option>
                        <option value="Refund" {{ ($filters['source_type'] ?? '') === 'Refund' ? 'selected' : '' }}>Refund</option>
                        <option value="Manual" {{ ($filters['source_type'] ?? '') === 'Manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">จากวันที่</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">ถึงวันที่</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-2 bg-red-500 rounded-xl text-white hover:bg-red-600 transition">
                        <i class="fas fa-search mr-2"></i>ค้นหา
                    </button>
                </div>
            </form>
        </div>

        {{-- Debts Table --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-white/60 text-left text-sm bg-white/5">
                            <th class="p-4 font-medium">ID</th>
                            <th class="p-4 font-medium">ผู้ใช้</th>
                            <th class="p-4 font-medium">ยอดหนี้เดิม</th>
                            <th class="p-4 font-medium">หักแล้ว</th>
                            <th class="p-4 font-medium">คงเหลือ</th>
                            <th class="p-4 font-medium">สถานะ</th>
                            <th class="p-4 font-medium">แหล่งที่มา</th>
                            <th class="p-4 font-medium">วันที่</th>
                            <th class="p-4 font-medium text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        @forelse($debts as $debt)
                        <tr class="border-t border-white/10 hover:bg-white/5">
                            <td class="p-4 font-mono text-sm">#{{ $debt->id }}</td>
                            <td class="p-4">
                                <a href="{{ route('admin.platform-revenue.debts.user', $debt->user_id) }}"
                                   class="flex items-center gap-3 hover:text-red-400 transition">
                                    <div class="w-10 h-10 bg-gradient-to-br from-red-400 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                                        {{ substr($debt->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ $debt->user->name ?? 'User #'.$debt->user_id }}</div>
                                        <div class="text-white/40 text-sm">{{ $debt->user->email ?? '' }}</div>
                                    </div>
                                </a>
                            </td>
                            <td class="p-4 font-mono text-red-400">฿{{ number_format($debt->original_amount, 2) }}</td>
                            <td class="p-4 font-mono text-green-400">฿{{ number_format($debt->deducted_amount, 2) }}</td>
                            <td class="p-4 font-mono font-bold {{ $debt->remaining_amount > 0 ? 'text-red-400' : 'text-green-400' }}">
                                ฿{{ number_format($debt->remaining_amount, 2) }}
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 rounded-full text-sm {{ $debt->status_color === 'red' ? 'bg-red-500/20 text-red-400' : '' }}
                                    {{ $debt->status_color === 'green' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $debt->status_color === 'yellow' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $debt->status_color === 'gray' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                                    {{ $debt->status_label }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 bg-white/10 rounded-lg text-sm">{{ $debt->source_type }}</span>
                            </td>
                            <td class="p-4 text-white/40 text-sm">{{ $debt->created_at->format('d/m/Y H:i') }}</td>
                            <td class="p-4 text-right">
                                <div class="flex gap-2 justify-end">
                                    <a href="{{ route('admin.platform-revenue.debts.show', $debt) }}"
                                       class="px-3 py-1 bg-white/10 rounded-lg text-white hover:bg-white/20 transition text-sm">
                                        <i class="fas fa-eye mr-1"></i>ดู
                                    </a>
                                    @if($debt->status === 'active')
                                    <button onclick="showWaiveModal({{ $debt->id }})"
                                            class="px-3 py-1 bg-yellow-500/20 rounded-lg text-yellow-400 hover:bg-yellow-500/30 transition text-sm">
                                        <i class="fas fa-hand-holding-usd mr-1"></i>ยกเว้น
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-white/40">
                                <i class="fas fa-check-circle text-4xl mb-3 text-green-400"></i>
                                <p>ไม่มีหนี้ค้างชำระ</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-white/10">
                {{ $debts->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Waive Modal --}}
<div id="waiveModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-2xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-white mb-4">ยกเว้นหนี้</h3>
        <form id="waiveForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="text-white/60 text-sm block mb-2">เหตุผลในการยกเว้น *</label>
                <textarea name="reason" rows="3" required
                          class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-yellow-400"
                          placeholder="ระบุเหตุผล..."></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="hideWaiveModal()"
                        class="px-4 py-2 bg-white/10 rounded-xl text-white hover:bg-white/20 transition">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-yellow-500 rounded-xl text-white hover:bg-yellow-600 transition">
                    <i class="fas fa-check mr-2"></i>ยืนยันยกเว้น
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showWaiveModal(debtId) {
    document.getElementById('waiveForm').action = '/admin/platform-revenue/debts/' + debtId + '/waive';
    document.getElementById('waiveModal').classList.remove('hidden');
    document.getElementById('waiveModal').classList.add('flex');
}

function hideWaiveModal() {
    document.getElementById('waiveModal').classList.add('hidden');
    document.getElementById('waiveModal').classList.remove('flex');
}
</script>
@endpush

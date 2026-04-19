@extends('layouts.admin-v3')

@section('title', 'Earnings Ledger')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-emerald-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-coins text-emerald-400"></i>
                        Earnings Ledger
                    </h1>
                    <p class="text-white/60">บันทึกรายได้ของผู้ใช้ทั้งหมด</p>
                </div>
                <a href="{{ route('admin.platform-revenue.index') }}"
                   class="px-4 py-2 bg-emerald-500 rounded-xl text-white hover:bg-emerald-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ Dashboard
                </a>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-white/60 text-sm mb-1">รายได้รวม (Gross)</div>
                <div class="text-3xl font-bold text-white">฿{{ number_format($stats['total_gross'], 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-white/60 text-sm mb-1">ค่าธรรมเนียมรวม</div>
                <div class="text-3xl font-bold text-red-400">฿{{ number_format($stats['total_fees'], 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-white/60 text-sm mb-1">รายได้สุทธิรวม</div>
                <div class="text-3xl font-bold text-emerald-400">฿{{ number_format($stats['total_net'], 2) }}</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="text-white/60 text-sm block mb-1">สถานะ</label>
                    <select name="status" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                        <option value="available" {{ ($filters['status'] ?? '') === 'available' ? 'selected' : '' }}>ถอนได้</option>
                        <option value="processing" {{ ($filters['status'] ?? '') === 'processing' ? 'selected' : '' }}>กำลังถอน</option>
                        <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    </select>
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">ประเภท</label>
                    <select name="earning_type" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="seller_sale" {{ ($filters['earning_type'] ?? '') === 'seller_sale' ? 'selected' : '' }}>รายได้ขาย</option>
                        <option value="mlm_commission" {{ ($filters['earning_type'] ?? '') === 'mlm_commission' ? 'selected' : '' }}>MLM Commission</option>
                        <option value="affiliate_commission" {{ ($filters['earning_type'] ?? '') === 'affiliate_commission' ? 'selected' : '' }}>Affiliate Commission</option>
                    </select>
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">จากวันที่</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white">
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">ถึงวันที่</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-2 bg-emerald-500 rounded-xl text-white hover:bg-emerald-600 transition">
                        <i class="fas fa-search mr-2"></i>ค้นหา
                    </button>
                </div>
            </form>
        </div>

        {{-- Earnings Table --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-white/60 text-left text-sm bg-white/5">
                            <th class="p-4 font-medium">ID</th>
                            <th class="p-4 font-medium">ผู้ใช้</th>
                            <th class="p-4 font-medium">ประเภท</th>
                            <th class="p-4 font-medium">ยอด Gross</th>
                            <th class="p-4 font-medium">ค่า Fee</th>
                            <th class="p-4 font-medium">ยอดสุทธิ</th>
                            <th class="p-4 font-medium">สถานะ</th>
                            <th class="p-4 font-medium">ถอนได้</th>
                            <th class="p-4 font-medium">วันที่</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        @forelse($earnings as $earning)
                        <tr class="border-t border-white/10 hover:bg-white/5">
                            <td class="p-4 font-mono text-sm">#{{ $earning->id }}</td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        {{ substr($earning->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <span>{{ $earning->user->name ?? 'User #'.$earning->user_id }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 bg-white/10 rounded-lg text-sm">{{ $earning->earning_type }}</span>
                            </td>
                            <td class="p-4 font-mono">฿{{ number_format($earning->gross_amount, 2) }}</td>
                            <td class="p-4 font-mono text-red-400">-฿{{ number_format($earning->platform_fee, 2) }}</td>
                            <td class="p-4 font-mono text-emerald-400 font-bold">฿{{ number_format($earning->net_amount, 2) }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded-lg text-sm
                                    {{ $earning->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $earning->status === 'available' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $earning->status === 'processing' ? 'bg-cyan-500/20 text-cyan-400' : '' }}
                                    {{ $earning->status === 'paid' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                ">{{ $earning->status }}</span>
                            </td>
                            <td class="p-4 text-white/40 text-sm">
                                {{ $earning->available_at ? $earning->available_at->format('d/m/Y') : '-' }}
                            </td>
                            <td class="p-4 text-white/40 text-sm">{{ $earning->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-white/40">ไม่พบรายการ</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-white/10">
                {{ $earnings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

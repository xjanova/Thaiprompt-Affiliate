@extends('layouts.admin')

@section('title', 'Platform Transactions')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-emerald-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-exchange-alt text-emerald-400"></i>
                        Platform Transactions
                    </h1>
                    <p class="text-white/60">รายการธุรกรรมทั้งหมดของ Platform Wallets</p>
                </div>
                <a href="{{ route('admin.platform-revenue.index') }}"
                   class="px-4 py-2 bg-emerald-500 rounded-xl text-white hover:bg-emerald-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ Dashboard
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="text-white/60 text-sm block mb-1">Wallet</label>
                    <select name="wallet_id" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white">
                        <option value="">ทั้งหมด</option>
                        @foreach($wallets as $wallet)
                        <option value="{{ $wallet->id }}" {{ ($filters['wallet_id'] ?? '') == $wallet->id ? 'selected' : '' }}>
                            {{ $wallet->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-white/60 text-sm block mb-1">ประเภท</label>
                    <select name="type" class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="income" {{ ($filters['type'] ?? '') === 'income' ? 'selected' : '' }}>รายรับ</option>
                        <option value="expense" {{ ($filters['type'] ?? '') === 'expense' ? 'selected' : '' }}>รายจ่าย</option>
                        <option value="transfer" {{ ($filters['type'] ?? '') === 'transfer' ? 'selected' : '' }}>โอน</option>
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

        {{-- Transactions Table --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-white/60 text-left text-sm bg-white/5">
                            <th class="p-4 font-medium">ID</th>
                            <th class="p-4 font-medium">Wallet</th>
                            <th class="p-4 font-medium">ประเภท</th>
                            <th class="p-4 font-medium">จำนวน</th>
                            <th class="p-4 font-medium">ยอดหลัง</th>
                            <th class="p-4 font-medium">รายละเอียด</th>
                            <th class="p-4 font-medium">อ้างอิง</th>
                            <th class="p-4 font-medium">เวลา</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        @forelse($transactions as $tx)
                        <tr class="border-t border-white/10 hover:bg-white/5">
                            <td class="p-4 font-mono text-sm">#{{ $tx->id }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 bg-white/10 rounded-lg text-sm">{{ $tx->wallet->name ?? '-' }}</span>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded-lg text-sm
                                    {{ $tx->type === 'income' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $tx->type === 'expense' ? 'bg-red-500/20 text-red-400' : '' }}
                                    {{ $tx->type === 'transfer' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                    {{ $tx->type === 'payout' ? 'bg-orange-500/20 text-orange-400' : '' }}
                                ">{{ $tx->type }}</span>
                            </td>
                            <td class="p-4 font-mono {{ $tx->type === 'income' ? 'text-green-400' : 'text-red-400' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }}฿{{ number_format(abs($tx->amount), 2) }}
                            </td>
                            <td class="p-4 font-mono text-white/60">฿{{ number_format($tx->balance_after, 2) }}</td>
                            <td class="p-4 text-white/60 text-sm max-w-xs truncate">{{ $tx->description }}</td>
                            <td class="p-4 text-white/40 text-sm">
                                @if($tx->reference_type)
                                {{ $tx->reference_type }}#{{ $tx->reference_id }}
                                @else
                                -
                                @endif
                            </td>
                            <td class="p-4 text-white/40 text-sm">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-white/40">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p>ไม่พบรายการ</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-white/10">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

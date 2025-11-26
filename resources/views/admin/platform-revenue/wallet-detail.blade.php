@extends('layouts.admin')

@section('title', "Wallet: {$wallet->name}")

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-emerald-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.platform-revenue.index') }}" class="text-emerald-400 hover:text-emerald-300 mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>กลับ Dashboard
            </a>
            <h1 class="text-4xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-wallet text-emerald-400"></i>
                {{ $wallet->name }}
            </h1>
            <p class="text-white/60">{{ $wallet->description }}</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-emerald-500/30">
                <div class="text-white/60 text-sm mb-1">ยอดคงเหลือ</div>
                <div class="text-3xl font-bold text-emerald-400">฿{{ number_format($stats['balance'], 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-green-500/30">
                <div class="text-white/60 text-sm mb-1">รายได้สะสม</div>
                <div class="text-3xl font-bold text-green-400">฿{{ number_format($stats['total_income'], 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-red-500/30">
                <div class="text-white/60 text-sm mb-1">รายจ่ายสะสม</div>
                <div class="text-3xl font-bold text-red-400">฿{{ number_format($stats['total_expense'], 2) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-cyan-500/30">
                <div class="text-white/60 text-sm mb-1">รายได้วันนี้</div>
                <div class="text-3xl font-bold text-cyan-400">+฿{{ number_format($stats['today_income'], 2) }}</div>
            </div>
        </div>

        {{-- Transactions --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
            <div class="p-4 border-b border-white/10">
                <h2 class="text-xl font-bold text-white">ประวัติธุรกรรม</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-white/60 text-left text-sm bg-white/5">
                            <th class="p-4 font-medium">ID</th>
                            <th class="p-4 font-medium">ประเภท</th>
                            <th class="p-4 font-medium">จำนวน</th>
                            <th class="p-4 font-medium">ยอดหลัง</th>
                            <th class="p-4 font-medium">รายละเอียด</th>
                            <th class="p-4 font-medium">เวลา</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        @forelse($transactions as $tx)
                        <tr class="border-t border-white/10">
                            <td class="p-4 font-mono text-sm">#{{ $tx->id }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded-lg text-sm {{ $tx->type === 'income' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $tx->type }}
                                </span>
                            </td>
                            <td class="p-4 font-mono {{ $tx->type === 'income' ? 'text-green-400' : 'text-red-400' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }}฿{{ number_format(abs($tx->amount), 2) }}
                            </td>
                            <td class="p-4 font-mono text-white/60">฿{{ number_format($tx->balance_after, 2) }}</td>
                            <td class="p-4 text-white/60 text-sm">{{ $tx->description }}</td>
                            <td class="p-4 text-white/40 text-sm">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-white/40">ไม่มีรายการ</td>
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

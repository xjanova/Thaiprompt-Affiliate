@extends('layouts.admin')

@section('title', $pageTitle ?? 'Revenue Reports')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-emerald-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-file-alt text-emerald-400"></i>
                        {{ $pageTitle ?? 'Revenue Reports' }}
                    </h1>
                    <p class="text-white/60">รายงานรายได้และรายจ่ายของ Platform</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.platform-revenue.index') }}"
                       class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white hover:bg-white/20 transition">
                        <i class="fas fa-arrow-left mr-2"></i>กลับ
                    </a>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-8">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-white/60 text-sm mb-2">ช่วงเวลา</label>
                    <select name="period" class="bg-white/10 border border-white/30 rounded-xl px-4 py-2 text-white">
                        <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>รายเดือน</option>
                        <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>รายวัน</option>
                    </select>
                </div>
                <div>
                    <label class="block text-white/60 text-sm mb-2">ปี</label>
                    <select name="year" class="bg-white/10 border border-white/30 rounded-xl px-4 py-2 text-white">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                @if($period === 'daily')
                <div>
                    <label class="block text-white/60 text-sm mb-2">เดือน</label>
                    <select name="month" class="bg-white/10 border border-white/30 rounded-xl px-4 py-2 text-white">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m)->locale('th')->monthName }}
                            </option>
                        @endfor
                    </select>
                </div>
                @endif
                <button type="submit" class="px-6 py-2 bg-emerald-500 rounded-xl text-white hover:bg-emerald-600 transition">
                    <i class="fas fa-search mr-2"></i>ค้นหา
                </button>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-emerald-500/20 to-emerald-600/20 backdrop-blur-lg rounded-2xl p-6 border border-emerald-400/30">
                <div class="text-white/60 text-sm mb-1">รายได้รวม (ปี {{ $year }})</div>
                <div class="text-3xl font-bold text-emerald-400">฿{{ number_format($summary['income'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-gradient-to-br from-red-500/20 to-red-600/20 backdrop-blur-lg rounded-2xl p-6 border border-red-400/30">
                <div class="text-white/60 text-sm mb-1">รายจ่ายรวม (ปี {{ $year }})</div>
                <div class="text-3xl font-bold text-red-400">฿{{ number_format($summary['expense'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/20 backdrop-blur-lg rounded-2xl p-6 border border-blue-400/30">
                <div class="text-white/60 text-sm mb-1">กำไรสุทธิ (ปี {{ $year }})</div>
                <div class="text-3xl font-bold {{ ($summary['net'] ?? 0) >= 0 ? 'text-blue-400' : 'text-red-400' }}">
                    ฿{{ number_format($summary['net'] ?? 0, 2) }}
                </div>
            </div>
            <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/20 backdrop-blur-lg rounded-2xl p-6 border border-purple-400/30">
                <div class="text-white/60 text-sm mb-1">จำนวน Transactions</div>
                <div class="text-3xl font-bold text-purple-400">{{ number_format($summary['transaction_count'] ?? 0) }}</div>
            </div>
        </div>

        {{-- Reports Table --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
            <div class="p-6 border-b border-white/10">
                <h2 class="text-xl font-bold text-white">
                    @if($period === 'daily')
                        รายงานรายวัน - {{ \Carbon\Carbon::create($year, $month)->locale('th')->monthName }} {{ $year }}
                    @else
                        รายงานรายเดือน - ปี {{ $year }}
                    @endif
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-white/60 text-sm font-medium">
                                {{ $period === 'daily' ? 'วันที่' : 'เดือน' }}
                            </th>
                            <th class="px-6 py-4 text-right text-white/60 text-sm font-medium">รายได้</th>
                            <th class="px-6 py-4 text-right text-white/60 text-sm font-medium">รายจ่าย</th>
                            <th class="px-6 py-4 text-right text-white/60 text-sm font-medium">กำไรสุทธิ</th>
                            <th class="px-6 py-4 text-right text-white/60 text-sm font-medium">Transactions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($reports as $report)
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4 text-white">
                                @if($period === 'daily')
                                    {{ \Carbon\Carbon::parse($report['date'])->locale('th')->format('j M Y') }}
                                @else
                                    {{ $report['month_name'] ?? '' }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-emerald-400 font-medium">
                                ฿{{ number_format($report['income'] ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-red-400 font-medium">
                                ฿{{ number_format($report['expense'] ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium {{ ($report['net'] ?? 0) >= 0 ? 'text-blue-400' : 'text-red-400' }}">
                                ฿{{ number_format($report['net'] ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-white/60">
                                {{ number_format($report['transaction_count'] ?? 0) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-white/40">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p>ไม่พบข้อมูลรายงาน</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($reports) > 0)
                    <tfoot class="bg-white/5 border-t border-white/20">
                        <tr>
                            <td class="px-6 py-4 text-white font-bold">รวมทั้งหมด</td>
                            <td class="px-6 py-4 text-right text-emerald-400 font-bold">
                                ฿{{ number_format(collect($reports)->sum('income'), 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-red-400 font-bold">
                                ฿{{ number_format(collect($reports)->sum('expense'), 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold {{ collect($reports)->sum('net') >= 0 ? 'text-blue-400' : 'text-red-400' }}">
                                ฿{{ number_format(collect($reports)->sum('net'), 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-white/60 font-bold">
                                {{ number_format(collect($reports)->sum('transaction_count')) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Wallet Breakdown (if available) --}}
        @if(isset($summary['by_wallet']) && count($summary['by_wallet']) > 0)
        <div class="mt-8 bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
            <div class="p-6 border-b border-white/10">
                <h2 class="text-xl font-bold text-white">รายได้แยกตาม Wallet (ปี {{ $year }})</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($summary['by_wallet'] as $slug => $amount)
                    <div class="bg-white/5 rounded-xl p-4">
                        <div class="text-white/60 text-sm mb-1">{{ ucfirst(str_replace('_', ' ', $slug)) }}</div>
                        <div class="text-xl font-bold text-white">฿{{ number_format($amount, 2) }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

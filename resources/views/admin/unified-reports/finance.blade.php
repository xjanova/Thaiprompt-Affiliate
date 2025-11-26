{{-- รายงานการเงิน/กระเป๋าเงิน --}}
@extends('layouts.admin')
@section('title', $pageTitle ?? 'รายงานการเงิน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <nav class="flex mb-3 text-sm">
                    <a href="{{ route('admin.unified-reports.index') }}" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400">ศูนย์รายงาน</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 dark:text-white font-medium">การเงิน</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    {{ $pageTitle }}
                </h1>
            </div>
            <a href="{{ route('admin.unified-reports.export', ['type' => 'finance', 'period' => $period]) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow">
                Export Excel
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ยอดคงเหลือรวม</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">฿{{ number_format($report['summary']['total_balance'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ฝากรวม</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">฿{{ number_format($report['summary']['total_deposits'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ถอนรวม</p>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">฿{{ number_format($report['summary']['total_withdrawals'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Flow</p>
                <p class="text-3xl font-bold {{ ($report['summary']['net_flow'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-2">
                    ฿{{ number_format($report['summary']['net_flow'] ?? 0, 2) }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ประเภท Transaction</h3>
                <div class="space-y-3">
                    @forelse(($report['transaction_types'] ?? []) as $type)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300 capitalize">{{ $type['type'] ?? 'N/A' }}</span>
                        <div class="text-right">
                            <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($type['total'] ?? 0, 2) }}</span>
                            <span class="text-gray-500 text-sm ml-2">({{ $type['count'] ?? 0 }})</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">กระเป๋าเงินยอดสูงสุด</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead><tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ใช้</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ยอดคงเหลือ</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($report['top_wallets'] ?? []) as $i => $wallet)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $wallet['user']['name'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-right text-green-600 dark:text-green-400 font-semibold">฿{{ number_format($wallet['balance'] ?? 0, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

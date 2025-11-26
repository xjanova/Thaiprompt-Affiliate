{{--
    รายงาน MLM/Affiliate
    แสดงข้อมูลสมาชิก คอมมิชชั่น Rank และการเติบโต
--}}
@extends('layouts.admin')

@section('title', $pageTitle ?? 'รายงาน MLM/Affiliate')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <nav class="flex mb-3" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2 text-sm">
                            <li><a href="{{ route('admin.unified-reports.index') }}" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400">ศูนย์รายงาน</a></li>
                            <li><span class="text-gray-400">/</span></li>
                            <li class="text-gray-900 dark:text-white font-medium">MLM/Affiliate</li>
                        </ol>
                    </nav>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        {{ $pageTitle ?? 'รายงาน MLM/Affiliate' }}
                    </h1>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.unified-reports.export', ['type' => 'mlm', 'period' => $period]) }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Excel
                    </a>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">สมาชิกทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($report['summary']['total_affiliates'] ?? 0) }}</p>
                <p class="text-sm text-green-600 dark:text-green-400 mt-1">+{{ $report['summary']['new_affiliates'] ?? 0 }} ใหม่</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">สมาชิก Active</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($report['summary']['active_affiliates'] ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">คอมมิชชั่นรวม</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">฿{{ number_format($report['summary']['total_commissions'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">รอจ่าย</p>
                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">฿{{ number_format($report['summary']['pending_commissions'] ?? 0, 2) }}</p>
            </div>
        </div>

        {{-- Charts and Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Rank Distribution --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การกระจาย Rank</h3>
                <div class="h-64">
                    <canvas id="rankDistributionChart"></canvas>
                </div>
            </div>

            {{-- Commission by Type --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">คอมมิชชั่นตามประเภท</h3>
                <div class="space-y-3">
                    @forelse(($report['commission_by_type'] ?? []) as $type)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-gray-700 dark:text-gray-300">{{ $type['type'] ?? 'N/A' }}</span>
                            <div class="text-right">
                                <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($type['total'] ?? 0, 2) }}</span>
                                <span class="text-gray-500 text-sm ml-2">({{ $type['count'] ?? 0 }} รายการ)</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Top Performers --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Earners --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ผู้มีรายได้สูงสุด</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ชื่อ</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รายได้</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($report['top_earners'] ?? []) as $index => $earner)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $earner['user']['name'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-green-600 dark:text-green-400 font-semibold">
                                        ฿{{ number_format($earner['total_earned'] ?? 0, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Top Recruiters --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ผู้แนะนำสมาชิกสูงสุด</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ชื่อ</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($report['top_recruiters'] ?? []) as $index => $recruiter)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $recruiter['name'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-indigo-600 dark:text-indigo-400 font-semibold">
                                        {{ $recruiter['referrals_count'] ?? 0 }} คน
                                    </td>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rankData = @json($report['rank_distribution'] ?? []);
    const rankCtx = document.getElementById('rankDistributionChart');

    if (rankCtx && rankData.length > 0) {
        new Chart(rankCtx, {
            type: 'doughnut',
            data: {
                labels: rankData.map(item => item.name || 'N/A'),
                datasets: [{
                    data: rankData.map(item => item.count || 0),
                    backgroundColor: ['#6366F1', '#8B5CF6', '#A855F7', '#D946EF', '#EC4899', '#F43F5E', '#F97316', '#FBBF24'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20 } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection

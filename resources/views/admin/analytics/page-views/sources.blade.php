{{--
/**
 * หน้า Traffic Sources Analytics
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'Traffic Sources')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-3xl">🔗</span>
                แหล่งที่มาของ Traffic
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                วิเคราะห์ว่าผู้เข้าชมมาจากที่ใด
            </p>
        </div>

        <div class="mt-4 md:mt-0 flex items-center gap-4">
            <select onchange="window.location.href = '?period=' + this.value"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg">
                <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 วันล่าสุด</option>
                <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 วันล่าสุด</option>
            </select>

            <a href="{{ route('admin.analytics.page-views.index') }}"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Direct vs Referral --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-lg opacity-90">Direct Traffic</p>
            <p class="text-4xl font-bold mt-2">{{ number_format($directVsReferral['direct']) }}</p>
            <p class="text-sm opacity-75 mt-1">ผู้เข้าชมโดยตรง</p>
        </div>
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-lg opacity-90">Referral Traffic</p>
            <p class="text-4xl font-bold mt-2">{{ number_format($directVsReferral['referral']) }}</p>
            <p class="text-sm opacity-75 mt-1">จากเว็บอื่น/แคมเปญ</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Referrers --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🌐</span>
                    Referrer Domains
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Domain</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Visits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($referrers as $ref)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $ref->referrer_domain }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-green-600 dark:text-green-400">{{ number_format($ref->visits) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-8 text-center text-gray-500">ไม่มีข้อมูล</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- UTM Campaigns --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🎯</span>
                    UTM Campaigns
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Source/Campaign</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Visits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($campaigns as $campaign)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $campaign->utm_source }}</div>
                                <div class="text-xs text-gray-500">{{ $campaign->utm_campaign ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-purple-600 dark:text-purple-400">{{ number_format($campaign->visits) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-8 text-center text-gray-500">ไม่มี UTM tracking</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

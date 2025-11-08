@extends('layouts.admin')

@section('title', 'Cookie Analytics Dashboard')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Cookie Analytics Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">วิเคราะห์พฤติกรรมผู้ใช้งานและการตลาด</p>
        </div>
        <div class="flex gap-3">
            <select name="range" onchange="window.location.href='?range='+this.value" class="px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>วันนี้</option>
                <option value="7days" {{ $dateRange == '7days' ? 'selected' : '' }}>7 วันที่แล้ว</option>
                <option value="30days" {{ $dateRange == '30days' ? 'selected' : '' }}>30 วันที่แล้ว</option>
                <option value="90days" {{ $dateRange == '90days' ? 'selected' : '' }}>90 วันที่แล้ว</option>
            </select>
            <a href="{{ route('admin.cookie-analytics.export', ['format' => 'csv', 'range' => $dateRange]) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Export CSV
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ผู้เยี่ยมชมทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_visitors']) }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เซสชันทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_sessions']) }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เวลาเฉลี่ย</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ gmdate('i:s', $stats['avg_session_duration'] ?? 0) }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">การดูหน้า</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_page_views']) }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">อัตรายินยอม</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['consent_rate'], 1) }}%</p>
                </div>
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Referrers -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">แหล่งที่มาหลัก (Referrers)</h2>
            <div class="space-y-3">
                @forelse($topReferrers as $referrer)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-gray-900 dark:text-white font-medium">{{ $referrer->referrer_domain }}</span>
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm">
                        {{ number_format($referrer->count) }}
                    </span>
                </div>
                @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>

        <!-- Top Keywords -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">คีย์เวิร์ดยอดนิยม</h2>
            <div class="space-y-3">
                @forelse($topKeywords->take(10) as $keyword)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-gray-900 dark:text-white font-medium">{{ $keyword->keyword }}</span>
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm">
                        {{ number_format($keyword->total_searches) }}
                    </span>
                </div>
                @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Device Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">อุปกรณ์</h2>
            <div class="space-y-3">
                @foreach($deviceStats as $device)
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 dark:text-gray-300 capitalize">{{ $device->device_type }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            @php
                                $percentage = ($device->count / $stats['total_visitors']) * 100;
                            @endphp
                            <div class="h-full bg-indigo-600" style="width: {{ $percentage }}%"></div>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($device->count) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Browser Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">เบราว์เซอร์</h2>
            <div class="space-y-3">
                @foreach($browserStats->take(5) as $browser)
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 dark:text-gray-300">{{ $browser->browser }}</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($browser->count) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Top Interests -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">ความสนใจ</h2>
            <div class="space-y-3">
                @foreach($interestsData as $interest => $count)
                <div class="flex items-center justify-between p-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900 dark:to-pink-900 rounded-lg">
                    <span class="text-gray-900 dark:text-white font-medium capitalize">{{ $interest }}</span>
                    <span class="px-3 py-1 bg-purple-600 text-white rounded-full text-sm">
                        {{ $count }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- UTM Campaigns -->
    @if($utmCampaigns->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">ประสิทธิภาพแคมเปญ (UTM)</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-300">Source</th>
                        <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-300">Medium</th>
                        <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-300">Campaign</th>
                        <th class="text-right py-3 px-4 text-gray-700 dark:text-gray-300">ผู้เยี่ยมชม</th>
                        <th class="text-right py-3 px-4 text-gray-700 dark:text-gray-300">การดูหน้า</th>
                        <th class="text-right py-3 px-4 text-gray-700 dark:text-gray-300">เวลาเฉลี่ย</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($utmCampaigns as $campaign)
                    <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="py-3 px-4 text-gray-900 dark:text-white">{{ $campaign->utm_source }}</td>
                        <td class="py-3 px-4 text-gray-900 dark:text-white">{{ $campaign->utm_medium }}</td>
                        <td class="py-3 px-4 text-gray-900 dark:text-white">{{ $campaign->utm_campaign }}</td>
                        <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ number_format($campaign->visitors) }}</td>
                        <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ number_format($campaign->total_page_views) }}</td>
                        <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ gmdate('i:s', $campaign->avg_duration) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Quick Links -->
    <div class="flex gap-4">
        <a href="{{ route('admin.cookie-analytics.visitors') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            ดูรายละเอียดผู้เยี่ยมชม
        </a>
        <a href="{{ route('admin.cookie-analytics.settings') }}" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            ตั้งค่าคุกกี้
        </a>
    </div>
</div>
@endsection

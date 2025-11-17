@extends('layouts.admin-v3')

@section('title', 'บันทึกการส่งอีเมล')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 dark:from-blue-800 dark:via-cyan-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold mb-2">📧 บันทึกการส่งอีเมล</h1>
                <p class="text-blue-100 dark:text-blue-200">ติดตามและจัดการบันทึกการส่งอีเมลทั้งหมดในระบบ</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-blue-200 dark:text-blue-300 mb-1">ส่งวันนี้</div>
                <div class="text-4xl font-bold">{{ $logs->where('created_at', '>=', today())->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Sent -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                    ✅
                </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">ส่งสำเร็จ</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($logs->where('status', 'sent')->count()) }}
            </p>
        </div>

        <!-- Failed -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-rose-600 dark:from-red-600 dark:to-rose-700 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                    ❌
                </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">ล้มเหลว</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($logs->where('status', 'failed')->count()) }}
            </p>
        </div>

        <!-- Opened -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-700 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                    👁️
                </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">เปิดอ่าน</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($logs->where('status', 'opened')->count()) }}
            </p>
        </div>

        <!-- Bounced -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 dark:from-orange-600 dark:to-amber-700 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                    ⚠️
                </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Bounce</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($logs->where('status', 'bounced')->count()) }}
            </p>
        </div>

        <!-- Pending -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 dark:from-yellow-600 dark:to-orange-700 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                    ⏳
                </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">รอส่ง</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($logs->where('status', 'pending')->count()) }}
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('admin.email.logs') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาอีเมล, หัวข้อ, Message ID..." class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทุกสถานะ</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>ส่งสำเร็จ</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอส่ง</option>
                    <option value="opened" {{ request('status') === 'opened' ? 'selected' : '' }}>เปิดอ่าน</option>
                    <option value="bounced" {{ request('status') === 'bounced' ? 'selected' : '' }}>Bounce</option>
                </select>
            </div>

            <!-- Provider Filter -->
            <div>
                <select name="provider" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทุก Provider</option>
                    <option value="smtp" {{ request('provider') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                    <option value="sendgrid" {{ request('provider') === 'sendgrid' ? 'selected' : '' }}>SendGrid</option>
                    <option value="mailgun" {{ request('provider') === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                    <option value="ses" {{ request('provider') === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors">
                    <i class="fa-solid fa-filter mr-2"></i>
                    กรอง
                </button>
            </div>
        </form>
    </div>

    <!-- Email Logs Timeline -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">📋 บันทึกการส่งอีเมล</h3>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                แสดง {{ $logs->count() }} รายการ
            </div>
        </div>

        @if($logs->count() > 0)
        <div class="space-y-4">
            @foreach($logs as $log)
            <div class="group relative bg-gray-50 dark:bg-slate-700 rounded-xl p-4 hover:shadow-lg transition-all duration-300 border-l-4
                {{ $log->status === 'sent' ? 'border-green-500' : '' }}
                {{ $log->status === 'failed' ? 'border-red-500' : '' }}
                {{ $log->status === 'pending' ? 'border-yellow-500' : '' }}
                {{ $log->status === 'opened' ? 'border-blue-500' : '' }}
                {{ $log->status === 'bounced' ? 'border-orange-500' : '' }}">

                <div class="flex items-start gap-4">
                    <!-- Status Icon -->
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold
                        {{ $log->status === 'sent' ? 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-200' : '' }}
                        {{ $log->status === 'failed' ? 'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-200' : '' }}
                        {{ $log->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-200' : '' }}
                        {{ $log->status === 'opened' ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-200' : '' }}
                        {{ $log->status === 'bounced' ? 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-200' : '' }}">
                        @if($log->status === 'sent') ✅
                        @elseif($log->status === 'failed') ❌
                        @elseif($log->status === 'pending') ⏳
                        @elseif($log->status === 'opened') 👁️
                        @elseif($log->status === 'bounced') ⚠️
                        @endif
                    </div>

                    <!-- Email Details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-base font-bold text-gray-900 dark:text-white mb-1 truncate">
                                    {{ $log->subject }}
                                </h4>
                                <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <i class="fa-solid fa-envelope text-xs"></i>
                                        {{ $log->to_email }}
                                    </span>
                                    @if($log->user)
                                    <span class="flex items-center gap-1">
                                        <i class="fa-solid fa-user text-xs"></i>
                                        {{ $log->user->name }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    {{ $log->status === 'sent' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : '' }}
                                    {{ $log->status === 'failed' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' : '' }}
                                    {{ $log->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : '' }}
                                    {{ $log->status === 'opened' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : '' }}
                                    {{ $log->status === 'bounced' ? 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200' : '' }}">
                                    {{ strtoupper($log->status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Additional Info -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-xs">
                            <div>
                                <div class="text-gray-500 dark:text-gray-400 mb-1">Provider</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ strtoupper($log->provider ?? 'N/A') }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400 mb-1">ส่งเมื่อ</div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ $log->sent_at ? $log->sent_at->format('d/m/Y H:i') : '-' }}
                                </div>
                            </div>
                            @if($log->opened_at)
                            <div>
                                <div class="text-gray-500 dark:text-gray-400 mb-1">เปิดอ่านเมื่อ</div>
                                <div class="font-semibold text-blue-600 dark:text-blue-400">
                                    {{ $log->opened_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            @endif
                            @if($log->message_id)
                            <div>
                                <div class="text-gray-500 dark:text-gray-400 mb-1">Message ID</div>
                                <div class="font-mono text-xs text-gray-900 dark:text-white truncate">
                                    {{ Str::limit($log->message_id, 20) }}
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Error Message -->
                        @if($log->error_message)
                        <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-exclamation-triangle text-red-600 dark:text-red-400 mt-0.5"></i>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-red-800 dark:text-red-200 mb-1">Error:</div>
                                    <div class="text-xs text-red-700 dark:text-red-300">{{ $log->error_message }}</div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Retry Count -->
                        @if($log->retry_count > 0)
                        <div class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded text-xs font-semibold">
                                <i class="fa-solid fa-rotate"></i>
                                ลองส่งใหม่ {{ $log->retry_count }} ครั้ง
                            </span>
                        </div>
                        @endif
                    </div>

                    <!-- Action Button -->
                    <div class="flex-shrink-0">
                        <a href="{{ route('admin.email.logs.show', $log->id) }}" class="px-3 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-lg text-xs font-semibold transition-colors">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
        <div class="mt-6">
            {{ $logs->links() }}
        </div>
        @endif

        @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="text-8xl mb-4">📭</div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบบันทึกอีเมล</h3>
            <p class="text-gray-600 dark:text-gray-400">ยังไม่มีการส่งอีเมลในระบบ หรือลองปรับเปลี่ยนตัวกรอง</p>
        </div>
        @endif
    </div>

    <!-- Email Statistics Chart -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📊 สถิติการส่งอีเมล (7 วันล่าสุด)</h3>
        <div style="height: 300px;">
            <canvas id="emailStatsChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const gridColor = isDark ? '#374151' : '#e5e7eb';

    // Email Stats Chart
    const ctx = document.getElementById('emailStatsChart');

    // Generate mock data for the last 7 days
    const days = [];
    const sentData = [];
    const failedData = [];
    const openedData = [];

    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        days.push(date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }));
        sentData.push(Math.floor(Math.random() * 100) + 50);
        failedData.push(Math.floor(Math.random() * 20));
        openedData.push(Math.floor(Math.random() * 80) + 30);
    }

    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: days,
                datasets: [
                    {
                        label: 'ส่งสำเร็จ',
                        data: sentData,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    },
                    {
                        label: 'เปิดอ่าน',
                        data: openedData,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    },
                    {
                        label: 'ล้มเหลว',
                        data: failedData,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: textColor }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#000000',
                        bodyColor: isDark ? '#ffffff' : '#000000',
                        borderColor: gridColor,
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }
});
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Email Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📧 Email Delivery System</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">ระบบควบคุมการส่งอีเมลแบบ Multi-Provider</p>
        </div>
        <button onclick="sendTestEmail()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            ส่งอีเมลทดสอบ
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Sent -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ส่งทั้งหมด</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <span class="text-2xl">📧</span>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ส่งสำเร็จ</p>
                    <p class="mt-1 text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['sent']) }}</p>
                    @php
                        $successRate = $stats['total'] > 0 ? ($stats['sent'] / $stats['total']) * 100 : 0;
                    @endphp
                    <p class="text-xs text-gray-500">{{ number_format($successRate, 1) }}%</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
        </div>

        <!-- Failed -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ล้มเหลว</p>
                    <p class="mt-1 text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['failed']) }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                    <span class="text-2xl">❌</span>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">รอส่ง</p>
                    <p class="mt-1 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['pending']) }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <span class="text-2xl">⏳</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Providers Status -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📡 Email Providers</h2>
        <div class="space-y-4">
            @forelse($providers as $provider)
                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            @if($provider->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    ใช้งาน
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    ปิด
                                </span>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $provider->display_name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $provider->type === 'api' ? 'API' : 'SMTP' }} • Priority: {{ $provider->priority }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if($provider->daily_limit)
                            <div class="text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400">วันนี้</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $provider->sent_today }} / {{ $provider->daily_limit }}
                                </p>
                            </div>
                        @endif
                        <a href="{{ route('admin.email.providers.edit', $provider) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            แก้ไข
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มี Email Provider</p>
                    <a href="{{ route('admin.email.providers.create') }}" class="mt-2 inline-block text-blue-600 hover:text-blue-800 dark:text-blue-400">
                        เพิ่ม Provider แรก
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📝 ประวัติการส่งล่าสุด</h2>
            <a href="{{ route('admin.email.logs') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                ดูทั้งหมด →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ถึง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เรื่อง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Provider</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เวลา</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentLogs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $log->to_email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ Str::limit($log->subject, 50) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->provider }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status === 'sent')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">ส่งแล้ว</span>
                                @elseif($log->status === 'failed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">ล้มเหลว</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ $log->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                ยังไม่มีประวัติการส่งอีเมล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function sendTestEmail() {
    const email = prompt('กรุณาใส่อีเมลสำหรับทดสอบ:');
    if (!email) return;

    fetch('{{ route('admin.email.test') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ to: email })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ส่งอีเมลทดสอบสำเร็จ!');
        } else {
            alert('❌ เกิดข้อผิดพลาด: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    });
}
</script>
@endsection

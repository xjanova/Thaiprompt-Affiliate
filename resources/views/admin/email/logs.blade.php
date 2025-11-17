@extends('layouts.admin-v3')

@section('title', 'Email Logs')

@section('content')
<div class="space-y-6">
    <!-- Header with Filters -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📝 ประวัติการส่งอีเมล</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ตรวจสอบและติดตามสถานะการส่งอีเมล</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form method="GET" action="{{ route('admin.email.logs') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>ส่งแล้ว</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอส่ง</option>
                    <option value="bounced" {{ request('status') === 'bounced' ? 'selected' : '' }}>Bounced</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Provider</label>
                <select name="provider" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="gmail_api" {{ request('provider') === 'gmail_api' ? 'selected' : '' }}>Gmail API</option>
                    <option value="gmail_smtp" {{ request('provider') === 'gmail_smtp' ? 'selected' : '' }}>Gmail SMTP</option>
                    <option value="smtp" {{ request('provider') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="อีเมล, เรื่อง..." class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    กรอง
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">ถึง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">เรื่อง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">Provider</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">ความพยายาม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">เวลา</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $log->to_email }}
                                @if($log->user)
                                    <br><span class="text-xs text-gray-500 dark:text-gray-400">{{ $log->user->name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ Str::limit($log->subject, 50) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                {{ $log->provider }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status === 'sent')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">✅ ส่งแล้ว</span>
                                @elseif($log->status === 'failed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">❌ ล้มเหลว</span>
                                @elseif($log->status === 'bounced')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">📭 Bounced</span>
                                @elseif($log->status === 'opened')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">👁️ เปิดแล้ว</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">⏳ {{ $log->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                {{ $log->retry_count }} / 3
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                {{ $log->created_at->format('d/m/Y H:i') }}
                                <br><span class="text-xs">{{ $log->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                ไม่พบประวัติการส่งอีเมล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

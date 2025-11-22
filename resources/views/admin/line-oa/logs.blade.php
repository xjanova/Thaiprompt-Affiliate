@extends('layouts.admin-v3')

@section('title', 'ประวัติการใช้งาน LINE')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="glass-fusion rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">ประวัติการใช้งาน LINE</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">ประวัติการ login, register และการเชื่อมต่อบัญชี LINE</p>
            </div>
            <a href="{{ route('admin.line-oa.index') }}" class="px-4 py-2 bg-gray-600 text-white font-semibold rounded-xl hover:bg-gray-700 transition">
                ← กลับไปการตั้งค่า
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form method="GET" action="{{ route('admin.line-oa.logs') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="ชื่อ, อีเมล, LINE User ID">
            </div>

            <div>
                <label for="action" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทการกระทำ</label>
                <select name="action" id="action"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Login</option>
                    <option value="register" {{ request('action') === 'register' ? 'selected' : '' }}>Register</option>
                    <option value="register_initiated" {{ request('action') === 'register_initiated' ? 'selected' : '' }}>Register Initiated</option>
                    <option value="link" {{ request('action') === 'link' ? 'selected' : '' }}>Link Account</option>
                    <option value="unlink" {{ request('action') === 'unlink' ? 'selected' : '' }}>Unlink Account</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">
                    กรอง
                </button>
                <a href="{{ route('admin.line-oa.logs') }}" class="ml-2 px-6 py-2 bg-gray-300 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-400 transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="glass-fusion rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            เวลา
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            LINE User ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            การกระทำ
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            IP Address
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            รายละเอียด
                        </th>
                    </tr>
                </thead>
                <tbody class="glass-fusion divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->user)
                                    <div class="text-sm font-medium text-gray-900">{{ $log->user->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <code class="bg-gray-100/50 dark:bg-gray-800/50 px-2 py-1 rounded text-xs">{{ $log->line_user_id }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badgeColors = [
                                        'login' => 'bg-blue-100 text-blue-800',
                                        'register' => 'bg-green-100 text-green-800',
                                        'register_initiated' => 'bg-yellow-100 text-yellow-800',
                                        'link' => 'bg-purple-100 text-purple-800',
                                        'unlink' => 'bg-red-100 text-red-800',
                                    ];
                                    $actionLabels = [
                                        'login' => 'Login',
                                        'register' => 'Register',
                                        'register_initiated' => 'Register Started',
                                        'link' => 'Link Account',
                                        'unlink' => 'Unlink Account',
                                    ];
                                    $badgeColor = $badgeColors[$log->action] ?? 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white';
                                    $actionLabel = $actionLabels[$log->action] ?? $log->action;
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeColor }}">
                                    {{ $actionLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($log->metadata)
                                    <button type="button"
                                            onclick="showMetadata(@js($log->metadata))"
                                            class="text-indigo-600 hover:text-indigo-900 underline">
                                        ดูรายละเอียด
                                    </button>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-lg">ไม่พบข้อมูล</div>
                                <p class="text-sm mt-2">ยังไม่มีประวัติการใช้งาน LINE</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="glass-fusion px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6" border border-white/20 dark:border-white/10>
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="glass-fusion rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Logins</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ \App\Models\LineLoginLog::where('action', 'login')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Registers</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ \App\Models\LineLoginLog::where('action', 'register')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Linked Accounts</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ \App\Models\User::whereNotNull('line_user_id')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Today</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ \App\Models\LineLoginLog::whereDate('created_at', today())->count() }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for metadata -->
<div id="metadataModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeMetadataModal()"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom glass-fusion rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" border border-white/20 dark:border-white/10>
            <div class="glass-fusion px-4 pt-5 pb-4 sm:p-6 sm:pb-4" border border-white/20 dark:border-white/10>
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">รายละเอียด Metadata</h3>
                        <div class="mt-2">
                            <pre id="metadataContent" class="bg-gray-100/50 dark:bg-gray-800/50 p-4 rounded text-sm overflow-auto max-h-96 text-left"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-100/50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeMetadataModal()"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 glass-fusion text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                    ปิด
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showMetadata(data) {
    const modal = document.getElementById('metadataModal');
    const content = document.getElementById('metadataContent');

    if (data && typeof data === 'object') {
        content.textContent = JSON.stringify(data, null, 2);
    } else {
        content.textContent = 'ไม่มีข้อมูล';
    }

    modal.classList.remove('hidden');
}

function closeMetadataModal() {
    const modal = document.getElementById('metadataModal');
    modal.classList.add('hidden');
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeMetadataModal();
    }
});
</script>
@endsection

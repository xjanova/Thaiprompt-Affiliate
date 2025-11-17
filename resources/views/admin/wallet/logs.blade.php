@extends('layouts.admin-v3')

@section('title', 'ประวัติความปลอดภัย')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ประวัติความปลอดภัย</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">บันทึกกิจกรรมและการแจ้งเตือนด้านความปลอดภัย</p>
            </div>
            <a href="{{ route('admin.wallet.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">การกระทำ</label>
                <select name="action" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="transaction_success" {{ request('action') == 'transaction_success' ? 'selected' : '' }}>ธุรกรรมสำเร็จ</option>
                    <option value="transaction_failed" {{ request('action') == 'transaction_failed' ? 'selected' : '' }}>ธุรกรรมล้มเหลว</option>
                    <option value="pin_changed" {{ request('action') == 'pin_changed' ? 'selected' : '' }}>เปลี่ยน PIN</option>
                    <option value="pin_failed" {{ request('action') == 'pin_failed' ? 'selected' : '' }}>PIN ไม่ถูกต้อง</option>
                    <option value="wallet_locked" {{ request('action') == 'wallet_locked' ? 'selected' : '' }}>ล็อกกระเป๋าเงิน</option>
                    <option value="suspicious_activity" {{ request('action') == 'suspicious_activity' ? 'selected' : '' }}>กิจกรรมน่าสงสัย</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระดับความสำคัญ</label>
                <select name="severity" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>ทั่วไป</option>
                    <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>คำเตือน</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>ร้ายแรง</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.wallet.logs') }}" class="px-6 py-2 bg-gray-100/50 dark:bg-gray-800/50 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl transition">
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Timeline -->
    <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="space-y-4">
            @forelse($logs as $log)
            <div class="flex items-start p-4 rounded-xl hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 transition border-l-4 border-{{ $log->severity_color }}-500">
                <div class="flex-shrink-0">
                    <span class="text-3xl">{{ $log->action_icon }}</span>
                </div>
                <div class="ml-4 flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-gray-900">{{ $log->action_label }}</h4>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-{{ $log->severity_color }}-100 text-{{ $log->severity_color }}-800">
                            {{ $log->severity_label }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $log->description }}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>🕒 {{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                        <span>🌐 {{ $log->ip_address }}</span>
                    </div>
                    @if($log->metadata)
                    <details class="mt-2">
                        <summary class="text-xs text-indigo-600 cursor-pointer hover:text-indigo-800">ดูรายละเอียด</summary>
                        <pre class="text-xs bg-gray-100/50 dark:bg-gray-800/50 p-2 rounded mt-2 overflow-auto">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-400">
                <span class="text-4xl block mb-2">📋</span>
                <p class="text-sm">ไม่พบบันทึก</p>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
        <div class="mt-6 border-t pt-4">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

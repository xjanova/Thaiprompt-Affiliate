@extends('layouts.admin')

@section('title', 'รายละเอียดการรีเซ็ต')

@section('content')
<div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.system-reset.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            กลับ
        </a>
        <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">รายละเอียดการรีเซ็ต</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Log ID: #{{ $log->id }}</p>
        </div>
    </div>

    <!-- Status Card -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border-2 border-gray-200 dark:border-slate-700 p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">สถานะการรีเซ็ต</h3>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 text-sm font-bold rounded-full
                        {{ $log->status === 'completed' ? 'bg-green-100 text-green-800' :
                           ($log->status === 'failed' ? 'bg-red-100 text-red-800' :
                           ($log->status === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')) }}">
                        {{ $log->status_label }}
                    </span>
                    @if($log->status === 'completed')
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @elseif($log->status === 'failed')
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400">เริ่มต้น</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $log->started_at->format('d/m/Y H:i:s') }}</p>
                @if($log->completed_at)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">เสร็จสิ้น</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $log->completed_at->format('d/m/Y H:i:s') }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ดำเนินการโดย</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $log->performedBy->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $log->performedBy->email }}</p>
            </div>

            @if($log->duration_seconds)
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ระยะเวลา</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $log->duration_human }}</p>
            </div>
            @endif

            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">IP Address</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $log->ip_address ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    @if($log->error_message)
    <!-- Error Message -->
    <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-300 dark:border-red-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-red-800 dark:text-red-200 mb-2 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            ข้อความ Error
        </h3>
        <pre class="text-sm text-red-700 dark:text-red-300 whitespace-pre-wrap font-mono bg-red-100 dark:bg-red-950/50 p-4 rounded-lg">{{ $log->error_message }}</pre>
    </div>
    @endif

    <!-- Reset Options -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border-2 border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">รายการที่เลือกรีเซ็ต</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($log->reset_options as $option)
                <div class="flex items-center p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700">
                    <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $option }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($log->status === 'completed' && !empty($log->reset_summary))
    <!-- Summary -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border-2 border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">สรุปผลการรีเซ็ต</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @php
                    $totalDeleted = 0;
                @endphp
                @foreach($log->reset_summary as $key => $summary)
                    @php
                        $totalDeleted += $summary['deleted_count'] ?? 0;
                    @endphp
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-900 dark:to-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $summary['label'] ?? $key }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                ตาราง: {{ implode(', ', $summary['tables'] ?? []) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-red-600">{{ number_format($summary['deleted_count'] ?? 0) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">รายการที่ลบ</p>
                        </div>
                    </div>
                @endforeach

                <!-- Total -->
                <div class="mt-6 p-6 bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-xl border-2 border-indigo-300 dark:border-indigo-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">รวมทั้งหมด</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">จำนวนข้อมูลที่ถูกลบทั้งหมด</p>
                        </div>
                        <div class="text-right">
                            <p class="text-4xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($totalDeleted) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">รายการ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- User Agent -->
    @if($log->user_agent)
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border-2 border-gray-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">User Agent</h3>
        <pre class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap font-mono bg-gray-50 dark:bg-slate-900 p-4 rounded-lg">{{ $log->user_agent }}</pre>
    </div>
    @endif
</div>
@endsection

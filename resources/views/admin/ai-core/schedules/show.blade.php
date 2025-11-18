@extends('layouts.admin')
@section('title', 'รายละเอียด Schedule: ' . $schedule->name)
@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('admin.ai-core.schedules.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-4 inline-block">← กลับ</a>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">⏰ {{ $schedule->name }}</h1>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('admin.ai-core.schedules.execute', $schedule) }}">
                    @csrf
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg">
                        ▶️ Execute Now
                    </button>
                </form>
                <a href="{{ route('admin.ai-core.schedules.edit', $schedule) }}"
                   class="px-6 py-2 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white font-semibold rounded-xl shadow-lg">
                    ✏️ แก้ไข
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ℹ️ ข้อมูลพื้นฐาน</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">ชื่อ</label>
                        <p class="text-lg text-gray-900 dark:text-white font-medium">{{ $schedule->name }}</p>
                    </div>
                    @if($schedule->description)
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">คำอธิบาย</label>
                            <p class="text-gray-700 dark:text-gray-300">{{ $schedule->description }}</p>
                        </div>
                    @endif
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">Action</label>
                        <p class="text-lg font-medium">
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-semibold
                                {{ $schedule->action === 'enable' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ ucfirst($schedule->action) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Schedule Configuration --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⏰ กำหนดการ</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">Schedule Type</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ ucfirst($schedule->schedule_type) }}</p>
                    </div>
                    @if($schedule->schedule_type === 'recurring')
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">ความถี่</label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ ucfirst($schedule->recurrence) }}</p>
                        </div>
                    @elseif($schedule->schedule_type === 'cron')
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">Cron Expression</label>
                            <p class="text-gray-900 dark:text-white font-mono">{{ $schedule->cron_expression }}</p>
                        </div>
                    @endif
                    @if($schedule->starts_at)
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">เริ่ม</label>
                            <p class="text-gray-900 dark:text-white">{{ $schedule->starts_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                    @if($schedule->ends_at)
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">สิ้นสุด</label>
                            <p class="text-gray-900 dark:text-white">{{ $schedule->ends_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                    @if($schedule->next_execution_at)
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">รันครั้งถัดไป</label>
                            <p class="text-gray-900 dark:text-white">{{ $schedule->next_execution_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-gray-500">{{ $schedule->next_execution_at->diffForHumans() }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Execution History --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📜 ประวัติการทำงาน</h2>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Execution Count</span>
                        <span class="font-bold">{{ $schedule->execution_count }}</span>
                    </div>
                    @if($schedule->last_execution_at)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">ทำงานล่าสุด</span>
                            <span>{{ $schedule->last_execution_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚡ สถานะ</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Active</span>
                        <span>{{ $schedule->is_active ? '✅' : '❌' }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Due</span>
                        <span>{{ $schedule->isDue() ? '✅' : '❌' }}</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl p-6 border-2 border-teal-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚡ Quick Actions</h2>
                <div class="space-y-2">
                    <form method="POST" action="{{ route('admin.ai-core.schedules.toggle', $schedule) }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center justify-between p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors duration-150">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">
                                {{ $schedule->is_active ? '⏸️ Pause' : '▶️ Activate' }}
                            </span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🕐 เวลา</h2>
                <div class="space-y-2 text-sm">
                    <div>
                        <label class="text-gray-500 dark:text-gray-400">Created</label>
                        <p class="text-gray-900 dark:text-white">{{ $schedule->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-gray-500 dark:text-gray-400">Updated</label>
                        <p class="text-gray-900 dark:text-white">{{ $schedule->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

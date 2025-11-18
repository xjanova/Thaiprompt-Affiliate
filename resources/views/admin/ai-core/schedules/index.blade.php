@extends('layouts.admin')
@section('title', 'จัดการ Schedules')
@section('content')
<div class="container-fluid px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-teal-600 to-cyan-600 dark:from-teal-400 dark:to-cyan-400 bg-clip-text text-transparent">
            ⏰ จัดการ Schedules
        </h1>
        <a href="{{ route('admin.ai-core.schedules.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white font-semibold rounded-xl shadow-lg">
            ➕ เพิ่ม Schedule
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-green-100 text-sm mb-1">Active</p>
            <h3 class="text-3xl font-bold">{{ $schedules->where('is_active', true)->count() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-blue-100 text-sm mb-1">Once</p>
            <h3 class="text-3xl font-bold">{{ $schedules->where('schedule_type', 'once')->count() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-purple-100 text-sm mb-1">Recurring</p>
            <h3 class="text-3xl font-bold">{{ $schedules->where('schedule_type', 'recurring')->count() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-orange-100 text-sm mb-1">Cron</p>
            <h3 class="text-3xl font-bold">{{ $schedules->where('schedule_type', 'cron')->count() }}</h3>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Schedule</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Action</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Type</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Next Execution</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($schedules as $schedule)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $schedule->name }}</div>
                                @if($schedule->description)
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($schedule->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    {{ $schedule->action === 'enable' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ ucfirst($schedule->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ ucfirst($schedule->schedule_type) }}</div>
                                @if($schedule->schedule_type === 'recurring')
                                    <div class="text-xs text-gray-500">{{ ucfirst($schedule->recurrence) }}</div>
                                @elseif($schedule->schedule_type === 'cron')
                                    <div class="text-xs text-gray-500 font-mono">{{ $schedule->cron_expression }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($schedule->next_execution_at)
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $schedule->next_execution_at->format('d/m/Y H:i') }}</div>
                                    <div class="text-xs text-gray-500">{{ $schedule->next_execution_at->diffForHumans() }}</div>
                                @else
                                    <span class="text-sm text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($schedule->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        ✅ Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        ⏸️ Paused
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.ai-core.schedules.show', $schedule) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg">
                                        👁️
                                    </a>
                                    <a href="{{ route('admin.ai-core.schedules.edit', $schedule) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg">
                                        ✏️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">ไม่มี Schedules</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($schedules->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $schedules->links() }}</div>
        @endif
    </div>
</div>
@endsection

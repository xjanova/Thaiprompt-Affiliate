@extends('layouts.admin')
@section('title', 'แก้ไข Schedule: ' . $schedule->name)
@section('content')
<div class="container-fluid px-4 py-6" x-data="scheduleEdit()">
    <div class="mb-6">
        <a href="{{ route('admin.ai-core.schedules.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-4 inline-block">← กลับ</a>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-teal-600 to-cyan-600 dark:from-teal-400 dark:to-cyan-400 bg-clip-text text-transparent">
            ✏️ แก้ไข Schedule
        </h1>
    </div>

    <form method="POST" action="{{ route('admin.ai-core.schedules.update', $schedule) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ℹ️ ข้อมูลพื้นฐาน</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อ Schedule <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('name', $schedule->name) }}"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-teal-500 text-gray-900 dark:text-white @error('name') border-red-500 @enderror">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Action <span class="text-red-500">*</span></label>
                    <select name="action" required
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-teal-500 text-gray-900 dark:text-white">
                        <option value="enable" {{ old('action', $schedule->action) == 'enable' ? 'selected' : '' }}>Enable</option>
                        <option value="disable" {{ old('action', $schedule->action) == 'disable' ? 'selected' : '' }}>Disable</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-teal-500 text-gray-900 dark:text-white">{{ old('description', $schedule->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Schedule Type --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⏰ ประเภท Schedule</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Schedule Type <span class="text-red-500">*</span></label>
                    <select name="schedule_type" x-model="scheduleType" required
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-teal-500 text-gray-900 dark:text-white">
                        <option value="once" {{ old('schedule_type', $schedule->schedule_type) == 'once' ? 'selected' : '' }}>Once (ครั้งเดียว)</option>
                        <option value="recurring" {{ old('schedule_type', $schedule->schedule_type) == 'recurring' ? 'selected' : '' }}>Recurring (ทำซ้ำ)</option>
                        <option value="cron" {{ old('schedule_type', $schedule->schedule_type) == 'cron' ? 'selected' : '' }}>Cron (ขั้นสูง)</option>
                    </select>
                </div>

                <div x-show="scheduleType === 'once'">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">วันที่และเวลา</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $schedule->starts_at ? $schedule->starts_at->format('Y-m-d\TH:i') : '') }}"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-teal-500 text-gray-900 dark:text-white">
                </div>

                <div x-show="scheduleType === 'recurring'">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ความถี่</label>
                    <select name="recurrence"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-teal-500 text-gray-900 dark:text-white">
                        <option value="daily" {{ old('recurrence', $schedule->recurrence) == 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="weekly" {{ old('recurrence', $schedule->recurrence) == 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ old('recurrence', $schedule->recurrence) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('recurrence', $schedule->recurrence) == 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                </div>

                <div x-show="scheduleType === 'cron'">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cron Expression</label>
                    <input type="text" name="cron_expression" value="{{ old('cron_expression', $schedule->cron_expression) }}"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-teal-500 text-gray-900 dark:text-white font-mono">
                </div>
            </div>
        </div>

        {{-- Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚙️ การตั้งค่า</h2>
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule->is_active) ? 'checked' : '' }}
                           class="w-5 h-5 text-teal-600 rounded">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">เปิดใช้งาน</label>
                </div>
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white font-semibold rounded-xl shadow-lg">
                💾 บันทึกการแก้ไข
            </button>
            <a href="{{ route('admin.ai-core.schedules.index') }}"
               class="px-8 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl text-center">
                ❌ ยกเลิก
            </a>
        </div>
    </form>
</div>
@push('scripts')
<script>
function scheduleEdit() {
    return {
        scheduleType: '{{ old('schedule_type', $schedule->schedule_type) }}'
    }
}
</script>
@endpush
@endsection

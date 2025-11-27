@extends('layouts.admin')

@section('title', 'กำหนดการ - Video Automation')

@section('styles')
<style>
    /* การ์ด Glassmorphism */
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ปุ่ม Gradient */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.3s ease;
    }
    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    /* Schedule Card */
    .schedule-card {
        transition: all 0.3s ease;
    }
    .schedule-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .dark .schedule-card:hover {
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    /* Time Badge */
    .time-badge {
        font-family: monospace;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 1.25rem;
        font-weight: 700;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    กำหนดการสร้างอัตโนมัติ
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                ตั้งเวลาให้ระบบสร้างวิดีโออัตโนมัติตามกำหนด
            </p>
        </div>
        <a href="{{ route('admin.video-automation.schedules.create') }}"
           class="btn-gradient-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างกำหนดการใหม่
        </a>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-green-700 dark:text-green-300">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Schedules List --}}
    @if($schedules->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($schedules as $schedule)
                <div class="schedule-card glass-card rounded-2xl p-6">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ $schedule->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if($schedule->template)
                                    Template: {{ $schedule->template->name }}
                                @else
                                    ไม่ระบุ Template
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       {{ $schedule->is_active ? 'checked' : '' }}
                                       onchange="toggleSchedule({{ $schedule->id }}, this.checked)"
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                    </div>

                    {{-- Schedule Info --}}
                    <div class="space-y-4">
                        {{-- Frequency --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">ความถี่</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    @php
                                        $frequencyLabels = [
                                            'daily' => 'ทุกวัน',
                                            'weekly' => 'ทุกสัปดาห์',
                                            'monthly' => 'ทุกเดือน',
                                            'custom' => 'กำหนดเอง',
                                        ];
                                    @endphp
                                    {{ $frequencyLabels[$schedule->frequency] ?? $schedule->frequency }}
                                </p>
                            </div>
                        </div>

                        {{-- Time --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">เวลา</p>
                                <p class="font-semibold text-gray-900 dark:text-white font-mono">
                                    {{ $schedule->scheduled_time ? \Carbon\Carbon::parse($schedule->scheduled_time)->format('H:i') : '-' }}
                                </p>
                            </div>
                        </div>

                        {{-- Next Run --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">รันถัดไป</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $schedule->next_run_at ? $schedule->next_run_at->format('d/m/Y H:i') : '-' }}
                                </p>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $schedule->total_runs ?? 0 }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">รันทั้งหมด</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">{{ $schedule->successful_runs ?? 0 }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">สำเร็จ</p>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex gap-2">
                            <a href="{{ route('admin.video-automation.schedules.edit', $schedule) }}"
                               class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                               title="แก้ไข">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form action="{{ route('admin.video-automation.schedules.destroy', $schedule) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('ต้องการลบกำหนดการนี้หรือไม่?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition"
                                        title="ลบ">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <form action="{{ route('admin.video-automation.schedules.run', $schedule) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                                รันทันที
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $schedules->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="glass-card rounded-2xl p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                ยังไม่มีกำหนดการ
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                สร้างกำหนดการเพื่อให้ระบบสร้างวิดีโออัตโนมัติตามเวลาที่กำหนด
            </p>
            <a href="{{ route('admin.video-automation.schedules.create') }}"
               class="btn-gradient-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้างกำหนดการแรก
            </a>
        </div>
    @endif

    {{-- Info Box --}}
    <div class="mt-8 glass-card rounded-2xl p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            เกี่ยวกับกำหนดการอัตโนมัติ
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm text-gray-600 dark:text-gray-400">
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">ทุกวัน (Daily)</h4>
                <p>ระบบจะสร้างวิดีโอใหม่ทุกวันตามเวลาที่กำหนด เหมาะสำหรับช่องที่ต้องการอัปเดตเนื้อหาบ่อยๆ</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">ทุกสัปดาห์ (Weekly)</h4>
                <p>ระบบจะสร้างวิดีโอใหม่ตามวันและเวลาที่กำหนด เหมาะสำหรับช่องที่ต้องการความสม่ำเสมอ</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">กำหนดเอง (Custom)</h4>
                <p>ใช้ Cron Expression สำหรับการตั้งเวลาที่ซับซ้อน เช่น ทุกวันจันทร์ พุธ ศุกร์</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleSchedule(id, isActive) {
    fetch(`{{ url('/admin/video-automation/schedules') }}/${id}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ is_active: isActive })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message (optional)
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revert the toggle if there's an error
        window.location.reload();
    });
}
</script>
@endsection

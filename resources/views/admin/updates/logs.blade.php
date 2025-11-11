@extends('layouts.admin')

@section('title', 'บันทึกการอัปเดต')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-history mr-3"></i>
                    บันทึกการอัปเดตระบบ
                </h1>
                <p class="text-indigo-100">ประวัติการอัปเดตและเปลี่ยนแปลงของระบบ</p>
            </div>
            <a href="{{ route('admin.updates.index') }}"
               class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition-all">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับ
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">อัปเดตทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ $logs->total() }}</p>
                </div>
                <div class="bg-white/20 p-4 rounded-full">
                    <i class="fas fa-list text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">สำเร็จ</p>
                    <p class="text-3xl font-bold">{{ $logs->where('status', 'completed')->count() }}</p>
                </div>
                <div class="bg-white/20 p-4 rounded-full">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">ล้มเหลว</p>
                    <p class="text-3xl font-bold">{{ $logs->where('status', 'failed')->count() }}</p>
                </div>
                <div class="bg-white/20 p-4 rounded-full">
                    <i class="fas fa-times-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">กำลังดำเนินการ</p>
                    <p class="text-3xl font-bold">{{ $logs->where('status', 'in_progress')->count() }}</p>
                </div>
                <div class="bg-white/20 p-4 rounded-full">
                    <i class="fas fa-spinner text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-clock text-indigo-600 mr-2"></i>
            Timeline การอัปเดต
        </h2>

        <div class="relative">
            <!-- Timeline Line -->
            <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

            <!-- Timeline Items -->
            <div class="space-y-6">
                @forelse($logs as $log)
                    <div class="relative pl-20">
                        <!-- Timeline Dot -->
                        <div class="absolute left-0 top-0 w-16 h-16 rounded-full flex items-center justify-center
                            {{ $log->status === 'completed' ? 'bg-gradient-to-br from-green-500 to-emerald-600' : '' }}
                            {{ $log->status === 'failed' ? 'bg-gradient-to-br from-red-500 to-pink-600' : '' }}
                            {{ $log->status === 'in_progress' ? 'bg-gradient-to-br from-yellow-500 to-orange-600' : '' }}
                            {{ $log->status === 'rolled_back' ? 'bg-gradient-to-br from-purple-500 to-indigo-600' : '' }}
                            shadow-lg">
                            @if($log->status === 'completed')
                                <i class="fas fa-check text-2xl text-white"></i>
                            @elseif($log->status === 'failed')
                                <i class="fas fa-times text-2xl text-white"></i>
                            @elseif($log->status === 'in_progress')
                                <i class="fas fa-spinner fa-spin text-2xl text-white"></i>
                            @else
                                <i class="fas fa-undo text-2xl text-white"></i>
                            @endif
                        </div>

                        <!-- Log Card -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                                        @if($log->systemUpdate)
                                            เวอร์ชัน {{ $log->systemUpdate->version }}
                                        @else
                                            อัปเดตระบบ
                                        @endif
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-user mr-1"></i>
                                        {{ $log->initiator ? $log->initiator->name : 'ระบบอัตโนมัติ' }}
                                    </p>
                                </div>

                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $log->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                    {{ $log->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                    {{ $log->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                    {{ $log->status === 'rolled_back' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}">
                                    @if($log->status === 'completed') สำเร็จ
                                    @elseif($log->status === 'failed') ล้มเหลว
                                    @elseif($log->status === 'in_progress') กำลังดำเนินการ
                                    @else Rolled Back
                                    @endif
                                </span>
                            </div>

                            @if($log->systemUpdate)
                                <div class="mb-4">
                                    <p class="text-gray-700 dark:text-gray-300">
                                        {{ $log->systemUpdate->description ?? 'อัปเดตระบบ' }}
                                    </p>
                                </div>
                            @endif

                            <!-- Details -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                @if($log->started_at)
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">เริ่มเมื่อ</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">
                                            {{ $log->started_at->format('H:i:s') }}
                                        </p>
                                    </div>
                                @endif

                                @if($log->completed_at)
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">เสร็จเมื่อ</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">
                                            {{ $log->completed_at->format('H:i:s') }}
                                        </p>
                                    </div>
                                @endif

                                @if($log->started_at && $log->completed_at)
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">ระยะเวลา</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">
                                            {{ $log->started_at->diffInSeconds($log->completed_at) }} วินาที
                                        </p>
                                    </div>
                                @endif

                                @if($log->backup_created)
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">สำรองข้อมูล</p>
                                        <p class="font-semibold text-green-600 dark:text-green-400">
                                            <i class="fas fa-check-circle mr-1"></i> มี
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- Error Message -->
                            @if($log->error_message)
                                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded">
                                    <p class="text-sm font-semibold text-red-800 dark:text-red-200 mb-1">ข้อผิดพลาด:</p>
                                    <p class="text-sm text-red-700 dark:text-red-300 font-mono">{{ $log->error_message }}</p>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="flex items-center gap-3 mt-4">
                                <button onclick="viewLogDetails({{ $log->id }})"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold">
                                    <i class="fas fa-eye mr-1"></i>
                                    ดูรายละเอียด
                                </button>

                                @if($log->status === 'completed' && $log->backup_created)
                                    <button onclick="confirmRollback({{ $log->id }})"
                                            class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 font-semibold">
                                        <i class="fas fa-undo mr-1"></i>
                                        Rollback
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <i class="fas fa-history text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีประวัติการอัปเดต</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Legend -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สัญลักษณ์สถานะ</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-check text-white"></i>
                </div>
                <span class="text-gray-700 dark:text-gray-300">อัปเดตสำเร็จ</span>
            </div>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-600 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-times text-white"></i>
                </div>
                <span class="text-gray-700 dark:text-gray-300">อัปเดตล้มเหลว</span>
            </div>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-spinner text-white"></i>
                </div>
                <span class="text-gray-700 dark:text-gray-300">กำลังดำเนินการ</span>
            </div>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-undo text-white"></i>
                </div>
                <span class="text-gray-700 dark:text-gray-300">Rollback แล้ว</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function viewLogDetails(logId) {
        window.location.href = `/admin/updates/logs/${logId}`;
    }

    function confirmRollback(logId) {
        if (confirm('คุณแน่ใจหรือไม่ที่จะ Rollback การอัปเดตนี้? ระบบจะกลับไปยังเวอร์ชันก่อนหน้า')) {
            fetch(`/admin/updates/rollback/${logId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Rollback สำเร็จ!');
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            });
        }
    }

    // Animations
    gsap.from('.space-y-6 > div', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        ease: 'power2.out'
    });

    // Auto refresh for in-progress updates
    @if($logs->where('status', 'in_progress')->count() > 0)
        setTimeout(() => location.reload(), 10000); // Reload every 10 seconds
    @endif
</script>
@endpush
@endsection

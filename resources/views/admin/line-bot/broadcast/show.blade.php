@extends('layouts.admin-v3')

@section('title', 'ผลลัพธ์ Broadcast')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.line-bot.broadcast.index') }}"
                   class="flex items-center justify-center w-12 h-12 rounded-xl glass-fusion border-2 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="flex-1">
                    <h1 class="text-3xl font-black bg-gradient-to-r from-[#06C755] via-emerald-600 to-teal-600 bg-clip-text text-transparent">
                        📊 {{ $broadcast->name }}
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ผลลัพธ์และสถิติการส่ง Broadcast</p>
                </div>
            </div>

            {{-- Status Badge --}}
            <div class="px-6 py-3 rounded-xl text-sm font-bold shadow-lg
                @if($broadcast->status === 'completed') bg-gradient-to-r from-green-500 to-emerald-600 text-white
                @elseif($broadcast->status === 'sending') bg-gradient-to-r from-blue-500 to-cyan-600 text-white animate-pulse
                @elseif($broadcast->status === 'scheduled') bg-gradient-to-r from-orange-500 to-yellow-600 text-white
                @elseif($broadcast->status === 'failed') bg-gradient-to-r from-red-500 to-pink-600 text-white
                @else bg-gradient-to-r from-gray-500 to-gray-600 text-white
                @endif">
                <i class="fas fa-circle mr-2 animate-pulse"></i>
                {{ strtoupper($broadcast->status) }}
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Recipients --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-700 p-6 shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
                <h3 class="text-4xl font-black text-white mb-1">{{ number_format($broadcast->total_recipients ?? 0) }}</h3>
                <p class="text-blue-100 text-sm font-medium">ผู้รับทั้งหมด</p>
            </div>
        </div>

        {{-- Delivered --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 to-emerald-700 p-6 shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <h3 class="text-4xl font-black text-white mb-1">{{ number_format($broadcast->sent_count ?? 0) }}</h3>
                <p class="text-green-100 text-sm font-medium">
                    ส่งสำเร็จ
                    @if(($broadcast->total_recipients ?? 0) > 0)
                        ({{ number_format(($broadcast->sent_count / $broadcast->total_recipients) * 100, 1) }}%)
                    @endif
                </p>
            </div>
        </div>

        {{-- Read Rate --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-700 p-6 shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3">
                    <i class="fas fa-eye text-white text-xl"></i>
                </div>
                <h3 class="text-4xl font-black text-white mb-1">
                    {{ number_format(($broadcast->read_count ?? 0)) }}
                </h3>
                <p class="text-purple-100 text-sm font-medium">
                    อ่านแล้ว
                    @if($broadcast->sent_count > 0)
                        ({{ number_format((($broadcast->read_count ?? 0) / $broadcast->sent_count) * 100, 1) }}%)
                    @endif
                </p>
            </div>
        </div>

        {{-- Failed --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-red-500 to-pink-700 p-6 shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3">
                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                </div>
                <h3 class="text-4xl font-black text-white mb-1">{{ number_format($broadcast->failed_count ?? 0) }}</h3>
                <p class="text-red-100 text-sm font-medium">
                    ส่งล้มเหลว
                    @if(($broadcast->total_recipients ?? 0) > 0)
                        ({{ number_format((($broadcast->failed_count ?? 0) / $broadcast->total_recipients) * 100, 1) }}%)
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Delivery Progress Chart --}}
                <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-line text-[#06C755]"></i>
                        ความคืบหน้าการส่ง
                    </h3>
                    <canvas id="deliveryChart" height="200"></canvas>
                </div>

                {{-- Read Rate Pie Chart --}}
                <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-pie text-purple-600"></i>
                        อัตราการอ่าน
                    </h3>
                    <canvas id="readRateChart" height="200"></canvas>
                </div>
            </div>

            {{-- Message Content --}}
            <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-comment-alt text-blue-600"></i>
                    เนื้อหาข้อความ
                </h3>
                <div class="p-5 bg-gray-100/50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-900 dark:text-white whitespace-pre-wrap leading-relaxed">{{ $broadcast->message ?? $broadcast->content ?? 'ไม่มีข้อความ' }}</p>
                </div>
            </div>

            {{-- Failed Deliveries Table (ถ้ามี) --}}
            @if($broadcast->failed_count > 0)
                <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-exclamation-circle text-red-600"></i>
                            การส่งที่ล้มเหลว ({{ number_format($broadcast->failed_count) }})
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ผู้ใช้</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">สาเหตุ</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">เวลา</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        ไม่มีข้อมูลรายละเอียดการล้มเหลว
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-6">
                {{-- Quick Actions --}}
                @if($broadcast->status === 'draft' || $broadcast->status === 'scheduled')
                    <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-bolt mr-2"></i>การจัดการ
                        </h3>
                        <div class="space-y-3">
                            <form method="POST" action="{{ route('admin.line-bot.broadcast.send', $broadcast->id) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('ยืนยันการส่ง broadcast นี้?')"
                                        class="w-full px-4 py-3 bg-gradient-to-r from-[#06C755] to-emerald-600 hover:from-emerald-600 hover:to-[#06C755] text-white rounded-xl transition-all shadow-lg font-bold">
                                    <i class="fas fa-paper-plane mr-2"></i>ส่งเลย
                                </button>
                            </form>

                            <a href="{{ route('admin.line-bot.broadcast.edit', $broadcast->id) }}"
                               class="block w-full px-4 py-3 glass-fusion border-2 border-purple-200 dark:border-purple-700 text-purple-700 dark:text-purple-300 hover:border-purple-400 dark:hover:border-purple-500 rounded-xl transition-all text-center font-bold">
                                <i class="fas fa-edit mr-2"></i>แก้ไข
                            </a>

                            <form method="POST" action="{{ route('admin.line-bot.broadcast.destroy', $broadcast->id) }}" onsubmit="return confirm('ลบ broadcast นี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full px-4 py-3 glass-fusion border-2 border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 hover:border-red-400 dark:hover:border-red-500 rounded-xl transition-all font-bold">
                                    <i class="fas fa-trash mr-2"></i>ลบ
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Broadcast Details --}}
                <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-info-circle mr-2 text-blue-600"></i>รายละเอียด
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">กลุ่มเป้าหมาย:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($broadcast->target_type ?? 'all') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">ประเภทข้อความ:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($broadcast->message_type ?? 'text') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">สร้างเมื่อ:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $broadcast->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        @if($broadcast->scheduled_at)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-600 dark:text-gray-400">ตั้งเวลาส่ง:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $broadcast->scheduled_at->format('d M Y, H:i') }}</span>
                            </div>
                        @endif
                        @if($broadcast->sent_at)
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">ส่งเมื่อ:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $broadcast->sent_at->format('d M Y, H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Progress (ถ้ากำลังส่งหรือส่งแล้ว) --}}
                @if(($broadcast->status === 'sending' || $broadcast->status === 'completed') && ($broadcast->total_recipients ?? 0) > 0)
                    @php
                        $progress = ($broadcast->sent_count / $broadcast->total_recipients) * 100;
                    @endphp
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 p-6 shadow-2xl">
                        <h3 class="font-bold text-white mb-4">ความคืบหน้า</h3>
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-white mb-2">
                                <span>{{ number_format($progress, 1) }}%</span>
                                <span>{{ number_format($broadcast->sent_count) }} / {{ number_format($broadcast->total_recipients) }}</span>
                            </div>
                            <div class="w-full bg-emerald-200 rounded-full h-4 overflow-hidden">
                                <div class="bg-gradient-to-r from-white to-emerald-100 h-4 rounded-full transition-all duration-500 shadow-lg"
                                     style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                        @if($broadcast->status === 'sending')
                            <p class="text-sm text-white mt-3">
                                <i class="fas fa-sync-alt fa-spin mr-2"></i>กำลังส่ง...
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Export Report --}}
                <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-download mr-2 text-orange-600"></i>ส่งออกรายงาน
                    </h3>
                    <button onclick="exportReport()"
                            class="w-full px-4 py-3 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white rounded-xl transition-all shadow-lg font-bold">
                        <i class="fas fa-file-export mr-2"></i>
                        Export PDF
                    </button>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">รายงานสรุปผลการส่ง Broadcast</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/**
 * Initialize Charts สำหรับ Broadcast Statistics
 */
document.addEventListener('DOMContentLoaded', function() {
    // Delivery Progress Chart (Line Chart)
    const deliveryCtx = document.getElementById('deliveryChart');
    if (deliveryCtx) {
        new Chart(deliveryCtx, {
            type: 'doughnut',
            data: {
                labels: ['ส่งสำเร็จ', 'ล้มเหลว', 'รอส่ง'],
                datasets: [{
                    data: [
                        {{ $broadcast->sent_count ?? 0 }},
                        {{ $broadcast->failed_count ?? 0 }},
                        {{ ($broadcast->total_recipients ?? 0) - ($broadcast->sent_count ?? 0) - ($broadcast->failed_count ?? 0) }}
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(156, 163, 175, 0.8)'
                    ],
                    borderColor: [
                        'rgba(34, 197, 94, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(156, 163, 175, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }

    // Read Rate Pie Chart
    const readRateCtx = document.getElementById('readRateChart');
    if (readRateCtx) {
        new Chart(readRateCtx, {
            type: 'pie',
            data: {
                labels: ['อ่านแล้ว', 'ยังไม่อ่าน'],
                datasets: [{
                    data: [
                        {{ $broadcast->read_count ?? 0 }},
                        {{ ($broadcast->sent_count ?? 0) - ($broadcast->read_count ?? 0) }}
                    ],
                    backgroundColor: [
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(209, 213, 219, 0.8)'
                    ],
                    borderColor: [
                        'rgba(147, 51, 234, 1)',
                        'rgba(209, 213, 219, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }
});

/**
 * Export Report Function
 */
function exportReport() {
    // TODO: Implement export functionality
    alert('ฟีเจอร์ Export PDF กำลังพัฒนา\n\nจะสามารถส่งออกรายงานสรุปผลการส่ง Broadcast เป็นไฟล์ PDF');
}
</script>
@endpush
@endsection

@extends('layouts.admin-v3')

@section('title', 'รายงานเนื้อหาไม่เหมาะสม')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">🚨 รายงานเนื้อหาไม่เหมาะสม</h1>
            <p class="text-white/60 mt-1">ตรวจสอบและจัดการรายงานจากผู้ใช้</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 px-4 py-2 bg-yellow-500/20 rounded-lg">
                <span class="text-yellow-400 font-bold">{{ $pendingCount }}</span>
                <span class="text-white/60">รอดำเนินการ</span>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-green-500/20 rounded-lg">
                <span class="text-green-400 font-bold">{{ $resolvedCount }}</span>
                <span class="text-white/60">ดำเนินการแล้ว</span>
            </div>
        </div>
    </div>

    {{-- Reports List --}}
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">ผู้รายงาน</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">เนื้อหา</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">เหตุผล</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-white/60 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($reports as $report)
                        <tr class="hover:bg-white/5 transition {{ $report->status === 'pending' ? 'bg-yellow-500/5' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ substr($report->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-white/80">{{ $report->user->name ?? 'ไม่ทราบ' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white/80">
                                    @if($report->reportable_type === 'App\Models\ForumThread')
                                        <span class="text-blue-400">📝 กระทู้:</span>
                                    @else
                                        <span class="text-green-400">💬 ความคิดเห็น:</span>
                                    @endif
                                    {{ Str::limit($report->reportable->title ?? $report->reportable->content ?? '-', 40) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-white/80">{{ $report->reason }}</td>
                            <td class="px-6 py-4">
                                @switch($report->status)
                                    @case('pending')
                                        <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 rounded text-xs">⏳ รอดำเนินการ</span>
                                        @break
                                    @case('resolved')
                                        <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs">✅ ดำเนินการแล้ว</span>
                                        @break
                                    @case('dismissed')
                                        <span class="px-2 py-1 bg-gray-500/20 text-gray-400 rounded text-xs">❌ ยกเลิก</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-6 py-4 text-white/60 text-sm">{{ $report->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-4 text-right">
                                @if($report->status === 'pending')
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="resolveReport({{ $report->id }})"
                                                class="px-3 py-1 bg-green-500/20 text-green-400 rounded hover:bg-green-500/30 transition text-sm">
                                            ✅ ดำเนินการ
                                        </button>
                                        <button onclick="dismissReport({{ $report->id }})"
                                                class="px-3 py-1 bg-gray-500/20 text-gray-400 rounded hover:bg-gray-500/30 transition text-sm">
                                            ❌ ยกเลิก
                                        </button>
                                    </div>
                                @else
                                    <span class="text-white/40 text-sm">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-white/60">
                                <i class="fas fa-check-circle text-4xl mb-4 block text-green-400"></i>
                                <p>ไม่มีรายงานใหม่</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div class="px-6 py-4 border-t border-white/10">
                {{ $reports->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

<script>
function resolveReport(reportId) {
    if (!confirm('ต้องการดำเนินการรายงานนี้?')) return;

    fetch(`/admin/forum/reports/${reportId}/resolve`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function dismissReport(reportId) {
    if (!confirm('ต้องการยกเลิกรายงานนี้?')) return;

    fetch(`/admin/forum/reports/${reportId}/dismiss`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}
</script>
@endsection

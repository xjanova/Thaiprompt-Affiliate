{{--
    หน้าแสดงรายละเอียดรายงาน (Admin)
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.admin-v3')

@section('title', 'รายละเอียดรายงาน #' . $report->id)

@section('content')
<div class="space-y-6" x-data="reportAdmin()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-white/60 mb-2">
                <a href="{{ route('admin.forum.reports.index') }}" class="hover:text-white transition">
                    จัดการรายงาน
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-white">รายงาน #{{ $report->id }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-white">🚨 รายละเอียดรายงาน</h1>
        </div>
        <a href="{{ route('admin.forum.reports.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition">
            <i class="fas fa-arrow-left"></i>
            <span>กลับ</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Report Details --}}
            <div class="glass-card rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-info-circle mr-2"></i>
                    ข้อมูลรายงาน
                </h2>

                <div class="space-y-4">
                    {{-- Status --}}
                    <div class="flex items-center gap-3">
                        <span class="text-white/60 w-24">สถานะ:</span>
                        @switch($report->status)
                            @case('pending')
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-sm">
                                    <i class="fas fa-clock"></i> รอดำเนินการ
                                </span>
                                @break
                            @case('reviewed')
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-sm">
                                    <i class="fas fa-eye"></i> กำลังตรวจสอบ
                                </span>
                                @break
                            @case('resolved')
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-sm">
                                    <i class="fas fa-check"></i> ดำเนินการแล้ว
                                </span>
                                @break
                            @case('dismissed')
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-500/20 text-gray-400 rounded-full text-sm">
                                    <i class="fas fa-times"></i> ยกเลิก
                                </span>
                                @break
                        @endswitch
                    </div>

                    {{-- Type --}}
                    <div class="flex items-center gap-3">
                        <span class="text-white/60 w-24">ประเภท:</span>
                        <span class="text-white">
                            @if($report->reportable_type === 'App\\Models\\ForumThread')
                                <i class="fas fa-file-alt mr-1"></i> กระทู้
                            @else
                                <i class="fas fa-comment mr-1"></i> ความคิดเห็น
                            @endif
                        </span>
                    </div>

                    {{-- Reason --}}
                    <div class="flex items-start gap-3">
                        <span class="text-white/60 w-24">เหตุผล:</span>
                        <span class="text-white">{{ $report->reason ?? '-' }}</span>
                    </div>

                    {{-- Description --}}
                    @if($report->description)
                    <div class="flex items-start gap-3">
                        <span class="text-white/60 w-24">รายละเอียด:</span>
                        <p class="text-white/80 flex-1">{{ $report->description }}</p>
                    </div>
                    @endif

                    {{-- Created At --}}
                    <div class="flex items-center gap-3">
                        <span class="text-white/60 w-24">รายงานเมื่อ:</span>
                        <span class="text-white">{{ $report->created_at->thaidate('j M Y H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- Reported Content --}}
            <div class="glass-card rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-file-alt mr-2"></i>
                    เนื้อหาที่ถูกรายงาน
                </h2>

                @if($report->reportable)
                <div class="p-4 bg-white/5 rounded-lg">
                    @if($report->reportable_type === 'App\\Models\\ForumThread')
                        {{-- Thread --}}
                        <div class="flex items-start gap-3 mb-3">
                            <img src="{{ $report->reportable->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($report->reportable->user->name ?? 'User') }}"
                                 alt=""
                                 class="w-10 h-10 rounded-full">
                            <div>
                                <div class="font-medium text-white">{{ $report->reportable->title }}</div>
                                <div class="text-sm text-white/60">
                                    โดย {{ $report->reportable->user->name ?? 'Unknown' }} •
                                    {{ $report->reportable->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        <div class="text-white/80 prose prose-invert max-w-none">
                            {!! Str::limit(strip_tags($report->reportable->content), 500) !!}
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('admin.forum.threads.show', $report->reportable) }}"
                               class="text-blue-400 hover:underline text-sm">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                ดูกระทู้เต็ม
                            </a>
                        </div>
                    @else
                        {{-- Post --}}
                        <div class="flex items-start gap-3 mb-3">
                            <img src="{{ $report->reportable->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($report->reportable->user->name ?? 'User') }}"
                                 alt=""
                                 class="w-10 h-10 rounded-full">
                            <div>
                                <div class="font-medium text-white">{{ $report->reportable->user->name ?? 'Unknown' }}</div>
                                <div class="text-sm text-white/60">
                                    {{ $report->reportable->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        <div class="text-white/80">
                            {!! Str::limit(strip_tags($report->reportable->content), 500) !!}
                        </div>
                        @if($report->reportable->thread)
                        <div class="mt-3">
                            <a href="{{ route('admin.forum.threads.show', $report->reportable->thread) }}"
                               class="text-blue-400 hover:underline text-sm">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                ดูกระทู้ที่เกี่ยวข้อง
                            </a>
                        </div>
                        @endif
                    @endif
                </div>
                @else
                <div class="p-4 bg-red-500/10 rounded-lg text-red-400">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    เนื้อหาถูกลบไปแล้ว
                </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Reporter Info --}}
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-sm font-medium text-white/60 mb-3">ผู้รายงาน</h3>
                @if($report->user)
                <div class="flex items-center gap-3">
                    <img src="{{ $report->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($report->user->name) }}"
                         alt="{{ $report->user->name }}"
                         class="w-12 h-12 rounded-full">
                    <div>
                        <div class="font-medium text-white">{{ $report->user->name }}</div>
                        <div class="text-sm text-white/60">{{ $report->user->email }}</div>
                    </div>
                </div>
                @else
                <div class="text-white/40">ผู้ใช้ถูกลบแล้ว</div>
                @endif
            </div>

            {{-- Actions --}}
            @if($report->status === 'pending' || $report->status === 'reviewed')
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-sm font-medium text-white/60 mb-3">ดำเนินการ</h3>

                <div class="space-y-3">
                    {{-- Admin Notes --}}
                    <div>
                        <label class="block text-sm text-white/80 mb-1">หมายเหตุ (ถ้ามี)</label>
                        <textarea x-model="adminNotes"
                                  rows="3"
                                  placeholder="เขียนหมายเหตุสำหรับการดำเนินการ..."
                                  class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 text-sm focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>

                    {{-- Resolve Button --}}
                    <button type="button"
                            @click="resolveReport"
                            :disabled="loading"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                        <i class="fas fa-check" :class="{ 'animate-spin': loading && action === 'resolve' }"></i>
                        <span>ดำเนินการแล้ว</span>
                    </button>

                    {{-- Dismiss Button --}}
                    <button type="button"
                            @click="dismissReport"
                            :disabled="loading"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                        <i class="fas fa-times" :class="{ 'animate-spin': loading && action === 'dismiss' }"></i>
                        <span>ยกเลิกรายงาน</span>
                    </button>
                </div>
            </div>
            @else
            {{-- Resolution Info --}}
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-sm font-medium text-white/60 mb-3">ผลการดำเนินการ</h3>

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-white/60">สถานะ:</span>
                        @if($report->status === 'resolved')
                        <span class="text-green-400">ดำเนินการแล้ว</span>
                        @else
                        <span class="text-gray-400">ยกเลิก</span>
                        @endif
                    </div>

                    @if($report->resolved_at)
                    <div class="flex items-center gap-2">
                        <span class="text-white/60">เมื่อ:</span>
                        <span class="text-white">{{ $report->resolved_at->thaidate('j M Y H:i') }}</span>
                    </div>
                    @endif

                    @if($report->admin_notes)
                    <div>
                        <span class="text-white/60 block mb-1">หมายเหตุ:</span>
                        <p class="text-white/80 p-2 bg-white/5 rounded">{{ $report->admin_notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

@push('scripts')
<script>
function reportAdmin() {
    return {
        adminNotes: '',
        loading: false,
        action: null,

        async resolveReport() {
            if (!confirm('ยืนยันว่าได้ดำเนินการกับรายงานนี้แล้ว?')) return;

            this.loading = true;
            this.action = 'resolve';

            try {
                const response = await fetch('{{ route("admin.forum.reports.resolve", $report) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        admin_notes: this.adminNotes
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
                alert('เกิดข้อผิดพลาด');
            }

            this.loading = false;
        },

        async dismissReport() {
            if (!confirm('ยืนยันการยกเลิกรายงานนี้?')) return;

            this.loading = true;
            this.action = 'dismiss';

            try {
                const response = await fetch('{{ route("admin.forum.reports.dismiss", $report) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        admin_notes: this.adminNotes
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
                alert('เกิดข้อผิดพลาด');
            }

            this.loading = false;
        }
    };
}
</script>
@endpush
@endsection

@extends('layouts.admin-v4')

@section('title', 'สถิติห้องเรียน'.$course->roleLabel())

@push('styles')
    @include('admin.classroom.partials.styles')
@endpush

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: สถิติคอร์ส (หลังบ้านห้องเรียน)
     ข้อมูลจาก Admin\ClassroomAdminController@stats:
       $course (+ lessons), $enrolled, $finishedAll, $perLesson (lesson_id → started/completed), $submitted, $passed,
       $passRate, $avgScore, $certified (paginate ใบรับรอง + user), $certifiedTotal, $needsRefresh
     ════════════════════════════════════════════════════════════ --}}
<div class="ac-scope">
    <div class="ac-row ac-between">
        <div style="flex:1; min-width:240px;">
            <div class="ac-eyebrow">หลังบ้าน · ห้องเรียน · คอร์ส{{ $course->roleLabel() }} · กติการุ่น {{ $course->rules_version }}</div>
            <h1 class="ac-h1">สถิติ: {{ $course->title }}</h1>
        </div>
        <div class="ac-row">
            <a href="{{ route('admin.classroom.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> ทุกคอร์ส</a>
            <a href="{{ route('admin.classroom.courses.edit', $course) }}" class="tp-btn tp-btn-sm"><i class="fas fa-pen"></i> คอร์ส &amp; บทเรียน</a>
        </div>
    </div>

    <div class="ac-kpis">
        <div class="tp-card"><div class="tp-muted" style="font-size:.8rem;">ผู้เรียนทั้งหมด</div><div class="ac-kpi-v">{{ number_format($enrolled) }}</div></div>
        <div class="tp-card"><div class="tp-muted" style="font-size:.8rem;">ดูครบทุกบท</div><div class="ac-kpi-v">{{ number_format($finishedAll) }}</div></div>
        <div class="tp-card"><div class="tp-muted" style="font-size:.8rem;">ส่งข้อสอบ</div><div class="ac-kpi-v">{{ number_format($submitted) }}</div><div class="tp-muted" style="font-size:.75rem;">คะแนนเฉลี่ย {{ $avgScore }}</div></div>
        <div class="tp-card"><div class="tp-muted" style="font-size:.8rem;">อัตราสอบผ่าน</div><div class="ac-kpi-v">{{ $passRate }}%</div><div class="tp-muted" style="font-size:.75rem;">ผ่าน {{ number_format($passed) }} ครั้ง</div></div>
        <div class="tp-card"><div class="tp-muted" style="font-size:.8rem;">ผ่านการอบรม</div><div class="ac-kpi-v">{{ number_format($certifiedTotal) }}</div><div class="tp-muted" style="font-size:.75rem;">ควรทบทวน {{ number_format($needsRefresh) }} คน</div></div>
    </div>

    <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
        <h2 class="ac-h2"><i class="fas fa-circle-play" style="color:var(--deep1);"></i> รายบทเรียน</h2>
        <div class="ac-scroll">
            <table class="ac-table">
                <thead><tr><th>#</th><th>บทเรียน</th><th>ความยาว</th><th>เริ่มดู</th><th>ดูจบ</th><th style="min-width:140px;">อัตราดูจบ</th></tr></thead>
                <tbody>
                    @forelse($course->lessons as $i => $lesson)
                        @php
                            $row = $perLesson->get($lesson->id);
                            $started = (int) ($row->started ?? 0);
                            $completed = (int) ($row->completed ?? 0);
                            $rate = $started > 0 ? round($completed * 100 / $started) : 0;
                        @endphp
                        <tr @if(! $lesson->is_active) style="opacity:.55;" @endif>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $lesson->title }} @unless($lesson->is_active) <span class="tp-pill ac-pill-off">ปิด</span> @endunless</td>
                            <td>{{ $lesson->durationLabel() }}</td>
                            <td>{{ number_format($started) }}</td>
                            <td>{{ number_format($completed) }}</td>
                            <td><div class="ac-bar"><span style="width: {{ $rate }}%;"></span></div><span class="tp-muted" style="font-size:.72rem;">{{ $rate }}%</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="tp-muted">ยังไม่มีบทเรียน</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
        <h2 class="ac-h2"><i class="fas fa-award" style="color:var(--deep1);"></i> ผู้ผ่านการอบรม{{ $course->roleLabel() }}</h2>
        <div class="ac-scroll">
            <table class="ac-table">
                <thead><tr><th>ผู้ใช้</th><th>ผ่านเมื่อ</th><th>รุ่นกติกา</th><th>คะแนนที่ได้</th><th>สถานะ</th></tr></thead>
                <tbody>
                    @forelse($certified as $cert)
                        @php $stale = (string) $cert->rules_version !== (string) $course->rules_version; @endphp
                        <tr>
                            <td>
                                <div style="font-weight:700;">{{ $cert->user?->name ?? 'ผู้ใช้ถูกลบ' }}</div>
                                <div class="tp-muted" style="font-size:.75rem;">#{{ $cert->user_id }} {{ $cert->user?->email }}</div>
                            </td>
                            <td>{{ $cert->certified_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                            <td><code>{{ $cert->rules_version }}</code></td>
                            <td>+{{ (int) $cert->points_awarded }}</td>
                            <td>
                                @if($stale)
                                    <span class="tp-pill ac-pill-warn">ควรทบทวน</span>
                                @else
                                    <span class="tp-pill ac-pill-navy">ผ่านรุ่นปัจจุบัน</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="tp-muted">ยังไม่มีผู้ผ่านการอบรม</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($certified->hasPages())
            <div>{{ $certified->links() }}</div>
        @endif
    </div>
</div>
@endsection

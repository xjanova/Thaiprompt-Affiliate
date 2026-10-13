@extends('layouts.admin-v4')

@section('title', 'ห้องเรียน')

@push('styles')
    @include('admin.classroom.partials.styles')
@endpush

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: ห้องเรียน (หลังบ้าน) — รายการคอร์ส 3 บทบาท
     ข้อมูลจาก Admin\ClassroomAdminController@index:
       $courses (ClassroomCourse + lessons_count, active_lessons_count, active_questions_count,
                 submitted_attempts_count, passed_attempts_count), $certCounts (role → จำนวนผู้ผ่าน)
     ════════════════════════════════════════════════════════════ --}}
@php
    $acIcons = ['seller' => 'fa-store', 'rider' => 'fa-motorcycle', 'buyer' => 'fa-basket-shopping'];
@endphp
<div class="ac-scope">
    <div class="ac-row ac-between">
        <div style="flex:1; min-width:240px;">
            <div class="ac-eyebrow">หลังบ้าน · ตลาด · การอบรม</div>
            <h1 class="ac-h1">🎓 ห้องเรียน</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:.85rem;">
                ดูวิดีโอครบ + สอบผ่าน → ป้าย "ผ่านการอบรม" ข้างป้ายยืนยันตัวตน และคะแนนความประพฤติ · ไม่บังคับ (ไม่ใช่ด่านเปิดร้าน/รับงาน/สั่งของ)
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card ac-flash">{{ session('success') }}</div>
    @endif

    @if($courses->isEmpty())
        <div class="tp-card" style="text-align:center; padding:32px;">
            <div style="font-size:2rem;">📚</div>
            <p class="tp-muted">ยังไม่มีคอร์ส — รัน <code>php artisan db:seed --class=ClassroomSeeder</code> เพื่อสร้างคอร์สตั้งต้น 3 บทบาท</p>
        </div>
    @else
        <div class="ac-grid">
            @foreach($courses as $course)
                @php
                    $passRate = $course->submitted_attempts_count > 0 ? round($course->passed_attempts_count * 100 / $course->submitted_attempts_count, 1) : null;
                    $short = $course->active_questions_count < $course->question_count;
                @endphp
                <div class="tp-card" style="display:flex; flex-direction:column; gap:12px;">
                    <div class="ac-row ac-between">
                        <div class="ac-row">
                            <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; background:#042C53; color:#FAC775;"><i class="fas {{ $acIcons[$course->role] ?? 'fa-graduation-cap' }}"></i></span>
                            <div>
                                <div class="tp-muted" style="font-size:.75rem;">คอร์ส{{ $course->roleLabel() }} · กติการุ่น {{ $course->rules_version }}</div>
                                <div style="font-weight:800; color:var(--ink); line-height:1.35;">{{ $course->title }}</div>
                            </div>
                        </div>
                        <span class="tp-pill {{ $course->is_active ? 'ac-pill-ok' : 'ac-pill-off' }}">{{ $course->is_active ? 'เปิดอยู่' : 'ปิดอยู่' }}</span>
                    </div>

                    <div class="ac-kpis" style="grid-template-columns:repeat(2, 1fr);">
                        <div class="tp-inset-sm" style="padding:10px 12px; border-radius:14px;">
                            <div class="tp-muted" style="font-size:.72rem;">บทเรียน (เปิด/ทั้งหมด)</div>
                            <div class="ac-kpi-v">{{ $course->active_lessons_count }}<span class="tp-muted" style="font-size:.8rem;">/{{ $course->lessons_count }}</span></div>
                        </div>
                        <div class="tp-inset-sm" style="padding:10px 12px; border-radius:14px;">
                            <div class="tp-muted" style="font-size:.72rem;">ข้อสอบที่เปิด (สุ่ม {{ $course->question_count }})</div>
                            <div class="ac-kpi-v" @if($short) style="color:#c05621;" @endif>{{ $course->active_questions_count }}</div>
                        </div>
                        <div class="tp-inset-sm" style="padding:10px 12px; border-radius:14px;">
                            <div class="tp-muted" style="font-size:.72rem;">ผ่านการอบรม</div>
                            <div class="ac-kpi-v">{{ number_format((int) ($certCounts[$course->role] ?? 0)) }}</div>
                        </div>
                        <div class="tp-inset-sm" style="padding:10px 12px; border-radius:14px;">
                            <div class="tp-muted" style="font-size:.72rem;">อัตราสอบผ่าน ({{ number_format($course->submitted_attempts_count) }} ครั้ง)</div>
                            <div class="ac-kpi-v">{{ $passRate === null ? '—' : $passRate.'%' }}</div>
                        </div>
                    </div>

                    @if($course->active_lessons_count === 0)
                        <div class="tp-muted" style="font-size:.78rem;">⚠️ ยังไม่มีบทเรียน — ผู้ใช้ยังสอบไม่ได้จนกว่าจะเพิ่มวิดีโออย่างน้อย 1 บท</div>
                    @endif
                    @if($short)
                        <div class="tp-muted" style="font-size:.78rem;">⚠️ ข้อสอบที่เปิดน้อยกว่าจำนวนที่สุ่ม — ระบบจะสุ่มเท่าที่มีและลดเกณฑ์ผ่านตามสัดส่วน</div>
                    @endif

                    <div class="ac-row" style="margin-top:auto;">
                        <a href="{{ route('admin.classroom.courses.edit', $course) }}" class="tp-btn tp-btn-primary tp-btn-sm"><i class="fas fa-pen"></i> คอร์ส &amp; บทเรียน</a>
                        <a href="{{ route('admin.classroom.questions.index', $course) }}" class="tp-btn tp-btn-sm"><i class="fas fa-list-check"></i> คลังข้อสอบ</a>
                        <a href="{{ route('admin.classroom.stats', $course) }}" class="tp-btn tp-btn-sm"><i class="fas fa-chart-simple"></i> สถิติ</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

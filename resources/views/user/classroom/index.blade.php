{{--
    ห้องเรียน (user.classroom.index) — คอร์สผู้ขาย / ไรเดอร์ / ผู้ซื้อ
    Controller: User\ClassroomController@index
    ตัวแปร: courses (ClassroomPresenter::courseList — เหมือน GET /api/v1/classroom/courses), pageTitle
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'ห้องเรียน')

@push('styles')
    @include('user.classroom.partials.styles')
@endpush

@php
    $crIcons = ['seller' => 'fa-store', 'rider' => 'fa-motorcycle', 'buyer' => 'fa-basket-shopping'];
    $crDuration = fn (int $s) => \App\Models\ClassroomLesson::formatDuration($s);
@endphp

@section('content')
<div class="cr-scope">
    {{-- หัวหน้า --}}
    <section class="tp-card cr-hero">
        <div class="cr-row" style="gap:16px;">
            <svg class="cr-seal-lg" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="10.7" fill="#042C53" stroke="#EF9F27" stroke-width="1.9"/>
                <path d="M12 6.4 19.2 9.7 12 13 4.8 9.7Z" fill="#FAC775"/>
                <path d="M8 11.5v2.9c0 1.3 1.8 2.3 4 2.3s4-1 4-2.3v-2.9L12 13.4Z" fill="#FAC775"/>
                <path d="M18.3 10.1v4.1" stroke="#FAC775" stroke-width="1.1" stroke-linecap="round"/>
                <circle cx="18.3" cy="14.8" r="0.95" fill="#FAC775"/>
            </svg>
            <div style="flex:1; min-width:220px;">
                <h1 class="cr-h1">ห้องเรียน Thai Prompt</h1>
                <p class="cr-muted cr-small" style="margin:6px 0 0; line-height:1.6;">
                    ดูวิดีโอให้ครบทุกบท แล้วทำแบบทดสอบให้ผ่าน รับป้าย <strong>"ผ่านการอบรม"</strong> แสดงข้างป้ายยืนยันตัวตน
                    พร้อมคะแนนความประพฤติ — ไม่บังคับ เปิดร้าน รับงาน หรือสั่งของได้ทันทีเหมือนเดิม
                </p>
            </div>
        </div>
    </section>

    @if(session('error'))
        <div class="cr-alert cr-tone-bad" role="alert">{{ session('error') }}</div>
    @endif

    @if(empty($courses))
        <section class="tp-card" style="text-align:center; padding:36px 20px;">
            <div style="font-size:40px;">📚</div>
            <h2 class="cr-h2" style="justify-content:center; margin-top:8px;">ยังไม่มีคอร์สเปิดสอน</h2>
            <p class="cr-muted cr-small">แอดมินกำลังเตรียมบทเรียน กลับมาดูใหม่เร็วๆ นี้</p>
        </section>
    @else
        <div class="cr-grid">
            @foreach($courses as $course)
                @php
                    $done = $course['lesson_count'] > 0 && $course['completed_lessons'] >= $course['lesson_count'];
                @endphp
                <a href="{{ route('user.classroom.course', $course['id']) }}" class="tp-card tp-card-hover cr-course">
                    <div class="cr-course-top">
                        <span class="cr-role-tile"><i class="fas {{ $crIcons[$course['role']] ?? 'fa-graduation-cap' }}"></i></span>
                        <div style="flex:1; min-width:0;">
                            <div class="cr-row" style="gap:6px;">
                                <span class="cr-pill cr-tone-navy">{{ $course['role_label'] }}</span>
                                @if($course['certified'])
                                    <x-training-badge :badge="['certified' => true, 'role' => $course['role'], 'label' => \App\Services\Classroom\TrainingBadge::label($course['role']), 'needs_refresh' => $course['needs_refresh']]" :size="22" label />
                                @endif
                            </div>
                            <h2 class="cr-h2" style="margin-top:8px; line-height:1.4;">{{ $course['title'] }}</h2>
                        </div>
                    </div>
                    @if($course['description'])
                        <p class="cr-muted cr-small" style="margin:0; line-height:1.6;">{{ \Illuminate\Support\Str::limit($course['description'], 160) }}</p>
                    @endif

                    <div class="cr-row cr-small cr-muted" style="gap:14px;">
                        <span><i class="fas fa-circle-play"></i> {{ number_format($course['lesson_count']) }} บท</span>
                        <span><i class="fas fa-clock"></i> {{ $crDuration($course['total_seconds']) }}</span>
                        <span><i class="fas fa-list-check"></i> สอบ {{ $course['question_count'] }} ข้อ (ผ่าน {{ $course['pass_score'] }})</span>
                        <span><i class="fas fa-star"></i> +{{ $course['points_reward'] }} คะแนน</span>
                    </div>

                    <div>
                        <div class="cr-row cr-between cr-small" style="margin-bottom:6px;">
                            <span class="cr-muted">เรียนแล้ว {{ $course['completed_lessons'] }}/{{ $course['lesson_count'] }} บท</span>
                            <span class="cr-num">{{ $course['progress_pct'] }}%</span>
                        </div>
                        <div class="cr-bar {{ $done ? 'ok' : '' }}" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $course['progress_pct'] }}">
                            <span style="width: {{ $course['progress_pct'] }}%;"></span>
                        </div>
                    </div>

                    <div class="cr-row cr-between">
                        @if($course['certified'] && $course['needs_refresh'])
                            <span class="cr-pill cr-tone-warn"><i class="fas fa-rotate"></i> กติกาอัปเดตแล้ว ควรทบทวน</span>
                        @elseif($course['certified'])
                            <span class="cr-pill cr-tone-ok"><i class="fas fa-circle-check"></i> ผ่านการอบรมแล้ว</span>
                        @elseif($course['lesson_count'] === 0)
                            <span class="cr-pill cr-tone-muted"><i class="fas fa-hourglass-half"></i> บทเรียนเร็วๆ นี้</span>
                        @elseif($done)
                            <span class="cr-pill cr-tone-ok"><i class="fas fa-unlock"></i> พร้อมสอบแล้ว</span>
                        @else
                            <span class="cr-pill"><i class="fas fa-book-open"></i> กำลังเรียน</span>
                        @endif
                        <span class="cr-link cr-small">เข้าเรียน <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection

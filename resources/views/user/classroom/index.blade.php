{{--
    ห้องเรียน (user.classroom.index) — คอร์สผู้ขาย / ไรเดอร์ / ผู้ซื้อ · ธีมโนวาเต็ม
    Controller: User\ClassroomController@index
    ตัวแปร: courses (ClassroomPresenter::courseList — เหมือน GET /api/v1/classroom/courses), pageTitle
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'ห้องเรียน')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/classroom-video.css') }}?v={{ filemtime(public_path('theme-nova/member/classroom-video.css')) }}">
@endpush

@php
    // ไอคอนตามบทบาทของคอร์ส (ชุดไอคอน nvm-i-*)
    $crIcons = ['seller' => 'store', 'rider' => 'scooter', 'buyer' => 'basket'];
    $crDuration = fn (int $s) => \App\Models\ClassroomLesson::formatDuration($s);
@endphp

@section('content')
<div class="nvm-stack nvm-cv">

<section class="nvm-ph" aria-labelledby="nvm-cv-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Thai Prompt Classroom</div>
            <h1 id="nvm-cv-h">ห้องเรียน Thai Prompt</h1>
            <p class="nvm-ph__lead">ดูวิดีโอให้ครบทุกบท แล้วทำแบบทดสอบให้ผ่าน รับป้าย "ผ่านการอบรม" แสดงข้างป้ายยืนยันตัวตน พร้อมคะแนนความประพฤติ</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ไม่บังคับ เปิดร้าน รับงาน หรือสั่งของได้ทันทีเหมือนเดิม</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ทำรายการไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

@if(empty($courses))
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/cat/books.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีคอร์สเปิดสอน</b>
            แอดมินกำลังเตรียมบทเรียน กลับมาดูใหม่เร็วๆ นี้
        </div>
    </section>
@else
    <div class="nvm-cv-grid">
        @foreach($courses as $course)
            @php
                $done = $course['lesson_count'] > 0 && $course['completed_lessons'] >= $course['lesson_count'];
            @endphp
            <a href="{{ route('user.classroom.course', $course['id']) }}" class="nvm-card nvm-cv-course">
                <div class="nvm-cv-course__top">
                    <span class="nvm-cv-course__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $crIcons[$course['role']] ?? 'award' }}"/></svg></span>
                    <div class="nvm-cv-course__txt">
                        <div class="nvm-cv-row">
                            <span class="nvm-chip nvm-chip--info">{{ $course['role_label'] }}</span>
                            @if($course['certified'])
                                <x-training-badge :badge="['certified' => true, 'role' => $course['role'], 'label' => \App\Services\Classroom\TrainingBadge::label($course['role']), 'needs_refresh' => $course['needs_refresh']]" :size="22" label />
                            @endif
                        </div>
                        <h2>{{ $course['title'] }}</h2>
                    </div>
                </div>
                @if($course['description'])
                    <p>{{ \Illuminate\Support\Str::limit($course['description'], 160) }}</p>
                @endif

                <ul class="nvm-cv-meta">
                    <li><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>{{ number_format($course['lesson_count']) }} บท</li>
                    <li><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>{{ $crDuration($course['total_seconds']) }}</li>
                    <li><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>สอบ {{ $course['question_count'] }} ข้อ (ผ่าน {{ $course['pass_score'] }})</li>
                    <li><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>+{{ $course['points_reward'] }} คะแนน</li>
                </ul>

                <div class="nvm-meter nvm-cv-bar {{ $done ? 'is-ok' : '' }}">
                    <div class="nvm-meter__row"><span>เรียนแล้ว {{ $course['completed_lessons'] }}/{{ $course['lesson_count'] }} บท</span><b class="nvm-num">{{ $course['progress_pct'] }}%</b></div>
                    <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $course['progress_pct'] }}" aria-label="ความคืบหน้าคอร์ส{{ $course['role_label'] }}"><i style="--w: {{ (int) $course['progress_pct'] }}%"></i></div>
                </div>

                <div class="nvm-cv-row nvm-cv-row--between">
                    @if($course['certified'] && $course['needs_refresh'])
                        <span class="nvm-chip nvm-chip--warn"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>กติกาอัปเดตแล้ว ควรทบทวน</span>
                    @elseif($course['certified'])
                        <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ผ่านการอบรมแล้ว</span>
                    @elseif($course['lesson_count'] === 0)
                        <span class="nvm-chip"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>บทเรียนเร็วๆ นี้</span>
                    @elseif($done)
                        <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>พร้อมสอบแล้ว</span>
                    @else
                        <span class="nvm-chip nvm-chip--gold">กำลังเรียน</span>
                    @endif
                    <span class="nvm-cv-go">เข้าเรียน<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></span>
                </div>
            </a>
        @endforeach
    </div>
@endif

</div>
@endsection

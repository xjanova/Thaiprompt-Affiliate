{{--
    คอร์ส (user.classroom.course) — บทเรียน + สถานะสอบ
    Controller: User\ClassroomController@course
    ตัวแปร: course (ClassroomPresenter::courseDetail — เหมือน GET /api/v1/classroom/courses/{id}), pageTitle
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'ห้องเรียน')

@push('styles')
    @include('user.classroom.partials.styles')
@endpush

@php
    $crDuration = fn (int $s) => \App\Models\ClassroomLesson::formatDuration($s);
    $lessonCount = (int) $course['lesson_count'];
    $retryAt = $course['retry_available_at'] ? \Illuminate\Support\Carbon::parse($course['retry_available_at']) : null;
    $retryLeft = $retryAt ? max(0, $retryAt->getTimestamp() - now()->getTimestamp()) : 0;
    $nextLesson = collect($course['lessons'])->first(fn ($l) => ! $l['completed']);
@endphp

@section('content')
<div class="cr-scope">
    <div class="cr-row">
        <a href="{{ route('user.classroom.index') }}" class="cr-btn-ghost"><i class="fas fa-arrow-left"></i> ห้องเรียน</a>
    </div>

    @if(session('error'))
        <div class="cr-alert cr-tone-bad" role="alert">{{ session('error') }}</div>
    @endif

    {{-- หัวคอร์ส --}}
    <section class="tp-card cr-hero">
        <div class="cr-stack">
            <div class="cr-row" style="gap:8px;">
                <span class="cr-pill cr-tone-navy">คอร์ส{{ $course['role_label'] }}</span>
                <span class="cr-pill cr-tone-muted">กติการุ่น {{ $course['rules_version'] }}</span>
                @if($course['training_badge'])
                    <x-training-badge :badge="$course['training_badge']" :size="22" label />
                @endif
            </div>
            <h1 class="cr-h1">{{ $course['title'] }}</h1>
            @if($course['description'])
                <p class="cr-muted" style="margin:0; line-height:1.7; font-size:14px;">{{ $course['description'] }}</p>
            @endif
            <div class="cr-row cr-small cr-muted" style="gap:16px;">
                <span><i class="fas fa-circle-play"></i> {{ $lessonCount }} บท · {{ $crDuration($course['total_seconds']) }}</span>
                <span><i class="fas fa-list-check"></i> สอบ {{ $course['question_count'] }} ข้อ ต้องถูก {{ $course['pass_score'] }} ข้อ</span>
                <span><i class="fas fa-star"></i> ผ่านครั้งแรกได้ +{{ $course['points_reward'] }} คะแนนความประพฤติ</span>
            </div>
            <div>
                <div class="cr-row cr-between cr-small" style="margin-bottom:6px;">
                    <span class="cr-muted">เรียนแล้ว {{ $course['completed_lessons'] }}/{{ $lessonCount }} บท</span>
                    <span class="cr-num">{{ $course['progress_pct'] }}%</span>
                </div>
                <div class="cr-bar {{ $course['quiz_unlocked'] ? 'ok' : '' }}"><span style="width: {{ $course['progress_pct'] }}%;"></span></div>
            </div>
        </div>
    </section>

    {{-- บทเรียน --}}
    <section class="tp-card cr-stack">
        <div class="cr-row cr-between">
            <h2 class="cr-h2"><i class="fas fa-circle-play" style="color:var(--deep1);"></i> บทเรียน</h2>
            @if($nextLesson)
                <a href="{{ route('user.classroom.lesson', $nextLesson['id']) }}" class="cr-link cr-small">เรียนต่อบทถัดไป <i class="fas fa-arrow-right"></i></a>
            @endif
        </div>
        <p class="cr-muted cr-small" style="margin:0;">ต้องดูแต่ละบทอย่างน้อย 90% ของความยาว (กดข้ามไปข้างหน้าระบบไม่นับเวลาให้)</p>

        @forelse($course['lessons'] as $i => $lesson)
            @php
                $pct = $lesson['required_seconds'] > 0 ? min(100, (int) floor($lesson['watched_seconds'] * 100 / $lesson['required_seconds'])) : 0;
            @endphp
            <a href="{{ route('user.classroom.lesson', $lesson['id']) }}" class="cr-lesson">
                <img class="cr-thumb" src="{{ $lesson['thumbnail_url'] }}" alt="" loading="lazy">
                <div style="flex:1; min-width:0;">
                    <div class="cr-small cr-muted">บทที่ {{ $i + 1 }} · {{ $crDuration($lesson['duration_seconds']) }}</div>
                    <div style="font-weight:700; color:var(--ink); line-height:1.45; margin:2px 0 6px;">{{ $lesson['title'] }}</div>
                    <div class="cr-bar {{ $lesson['completed'] ? 'ok' : '' }}" style="height:7px;"><span style="width: {{ $lesson['completed'] ? 100 : $pct }}%;"></span></div>
                </div>
                @if($lesson['completed'])
                    <span class="cr-check cr-tone-ok" aria-label="ดูครบแล้ว"><i class="fas fa-check"></i></span>
                @else
                    <span class="cr-check cr-tone-muted" aria-label="ยังดูไม่ครบ"><i class="fas fa-play"></i></span>
                @endif
            </a>
        @empty
            <div class="cr-alert cr-tone-muted">คอร์สนี้ยังไม่มีบทเรียน แอดมินกำลังเตรียมวิดีโอ กลับมาดูใหม่เร็วๆ นี้</div>
        @endforelse
    </section>

    {{-- แบบทดสอบ --}}
    <section class="tp-card cr-stack" x-data="{ left: {{ $retryLeft }}, tick() { if (this.left > 0) { this.left--; if (this.left === 0) window.location.reload(); } } }" x-init="setInterval(() => tick(), 1000)">
        <h2 class="cr-h2"><i class="fas fa-list-check" style="color:var(--deep1);"></i> แบบทดสอบท้ายคอร์ส</h2>

        @if($course['last_attempt'])
            <div class="cr-row cr-small">
                <span class="cr-muted">ผลสอบล่าสุด:</span>
                <span class="cr-num">{{ $course['last_attempt']['score'] }}/{{ $course['last_attempt']['total'] }}</span>
                @if($course['last_attempt']['passed'])
                    <span class="cr-pill cr-tone-ok"><i class="fas fa-circle-check"></i> ผ่าน</span>
                @else
                    <span class="cr-pill cr-tone-bad"><i class="fas fa-circle-xmark"></i> ยังไม่ผ่าน</span>
                @endif
            </div>
        @endif

        @if($course['open_attempt'])
            <div class="cr-alert cr-tone-warn">คุณมีชุดข้อสอบที่ทำค้างอยู่ ทำต่อให้เสร็จก่อนหมดเวลา</div>
            <div><a href="{{ route('user.classroom.attempt', $course['open_attempt']['attempt_id']) }}" class="cr-btn cr-tone-warn"><i class="fas fa-pen"></i> ทำข้อสอบต่อ</a></div>
        @elseif(! $course['quiz_unlocked'])
            <div class="cr-alert cr-tone-muted">
                <i class="fas fa-lock"></i>
                {{ $lessonCount === 0 ? 'คอร์สนี้ยังไม่มีบทเรียน จึงยังสอบไม่ได้' : 'ดูบทเรียนให้ครบทุกบทก่อน จึงจะเริ่มสอบได้ (เหลืออีก '.($lessonCount - $course['completed_lessons']).' บท)' }}
            </div>
            <div><button type="button" class="cr-btn cr-tone-navy" disabled><i class="fas fa-lock"></i> เริ่มสอบ</button></div>
        @else
            <div x-show="left > 0" x-cloak class="cr-alert cr-tone-warn">
                สอบไม่ผ่านรอบที่แล้ว สอบใหม่ได้ในอีก <strong class="cr-num" x-text="left"></strong> วินาที — ระหว่างนี้ลองทบทวนบทเรียนก่อน
            </div>
            <form method="POST" action="{{ route('user.classroom.quiz.start', $course['id']) }}" x-data="{ busy: false }" x-on:submit="busy = true">
                @csrf
                <p class="cr-muted cr-small" style="margin:0 0 12px;">สุ่ม {{ $course['question_count'] }} ข้อ มีเวลา 30 นาที ต้องตอบถูกอย่างน้อย {{ $course['pass_score'] }} ข้อ</p>
                <button type="submit" class="cr-btn cr-tone-navy" :disabled="busy || left > 0" :class="{ 'is-disabled': busy || left > 0 }">
                    <i class="fas fa-play"></i>
                    <span x-text="busy ? 'กำลังเตรียมข้อสอบ...' : '{{ $course['certified'] && ! $course['needs_refresh'] ? 'สอบทบทวนอีกครั้ง' : 'เริ่มสอบ' }}'">เริ่มสอบ</span>
                </button>
            </form>
        @endif
    </section>
</div>
@endsection

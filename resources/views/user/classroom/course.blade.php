{{--
    คอร์ส (user.classroom.course) — บทเรียน + สถานะสอบ · ธีมโนวาเต็ม
    Controller: User\ClassroomController@course
    ตัวแปร: course (ClassroomPresenter::courseDetail — เหมือน GET /api/v1/classroom/courses/{id}), pageTitle
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'ห้องเรียน')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/classroom-video.css') }}?v={{ filemtime(public_path('theme-nova/member/classroom-video.css')) }}">
@endpush

@php
    $crDuration = fn (int $s) => \App\Models\ClassroomLesson::formatDuration($s);
    $lessonCount = (int) $course['lesson_count'];
    $retryAt = $course['retry_available_at'] ? \Illuminate\Support\Carbon::parse($course['retry_available_at']) : null;
    $retryLeft = $retryAt ? max(0, $retryAt->getTimestamp() - now()->getTimestamp()) : 0;
    $nextLesson = collect($course['lessons'])->first(fn ($l) => ! $l['completed']);
    $startLabel = $course['certified'] && ! $course['needs_refresh'] ? 'สอบทบทวนอีกครั้ง' : 'เริ่มสอบ';
@endphp

@section('content')
<div class="nvm-stack nvm-cv">

<section class="nvm-ph" aria-labelledby="nvm-cv-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.classroom.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ห้องเรียน</a>
            <div class="nvm-eyebrow nvm-latin">Course</div>
            <h1 id="nvm-cv-h">{{ $course['title'] }}</h1>
            @if($course['description'])
                <p class="nvm-ph__lead">{{ $course['description'] }}</p>
            @endif
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--gold">คอร์ส{{ $course['role_label'] }}</span>
                <span class="nvm-pill nvm-pill--glass">กติการุ่น {{ $course['rules_version'] }}</span>
                @if($course['training_badge'])
                    <span class="nvm-pill nvm-pill--glass"><x-training-badge :badge="$course['training_badge']" :size="18" label /></span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/cat/books.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ทำรายการไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

<section class="nvm-card nvm-box" aria-label="สรุปคอร์ส">
    <ul class="nvm-cv-meta">
        <li><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>{{ $lessonCount }} บท · {{ $crDuration($course['total_seconds']) }}</li>
        <li><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>สอบ {{ $course['question_count'] }} ข้อ ต้องถูก {{ $course['pass_score'] }} ข้อ</li>
        <li><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ผ่านครั้งแรกได้ +{{ $course['points_reward'] }} คะแนนความประพฤติ</li>
    </ul>
    <div class="nvm-meter nvm-cv-bar {{ $course['quiz_unlocked'] ? 'is-ok' : '' }}">
        <div class="nvm-meter__row"><span>เรียนแล้ว {{ $course['completed_lessons'] }}/{{ $lessonCount }} บท</span><b class="nvm-num">{{ $course['progress_pct'] }}%</b></div>
        <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $course['progress_pct'] }}" aria-label="ความคืบหน้าคอร์ส"><i style="--w: {{ (int) $course['progress_pct'] }}%"></i></div>
    </div>
</section>

<div class="nvm-split">
    {{-- บทเรียน --}}
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cv-ls-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Lessons</small><h2 id="nvm-cv-ls-h">บทเรียน</h2></div>
            @if($nextLesson)
                <a href="{{ route('user.classroom.lesson', $nextLesson['id']) }}">เรียนต่อบทถัดไป<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            @endif
        </div>
        <p class="nvm-help">ต้องดูแต่ละบทอย่างน้อย 90% ของความยาว (กดข้ามไปข้างหน้าระบบไม่นับเวลาให้)</p>

        @if(count($course['lessons']))
            <ol class="nvm-cv-lessons nvm-gap-t">
                @foreach($course['lessons'] as $i => $lesson)
                    @php
                        $pct = $lesson['required_seconds'] > 0 ? min(100, (int) floor($lesson['watched_seconds'] * 100 / $lesson['required_seconds'])) : 0;
                        $barPct = $lesson['completed'] ? 100 : $pct;
                    @endphp
                    <li>
                        <a href="{{ route('user.classroom.lesson', $lesson['id']) }}" class="nvm-cv-lesson">
                            <img class="nvm-cv-lesson__thumb" src="{{ $lesson['thumbnail_url'] }}" alt="" width="104" height="59" loading="lazy">
                            <div class="nvm-cv-lesson__t">
                                <small>บทที่ {{ $i + 1 }} · {{ $crDuration($lesson['duration_seconds']) }}</small>
                                <b>{{ $lesson['title'] }}</b>
                                <div class="nvm-cv-bar nvm-cv-bar--thin {{ $lesson['completed'] ? 'is-ok' : '' }}">
                                    <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $barPct }}" aria-label="ดูบทที่ {{ $i + 1 }} แล้ว"><i style="--w: {{ $barPct }}%"></i></div>
                                </div>
                            </div>
                            @if($lesson['completed'])
                                <span class="nvm-cv-check is-ok" aria-label="ดูครบแล้ว"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></span>
                            @else
                                <span class="nvm-cv-check" aria-label="ยังดูไม่ครบ"><svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/cat/books.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>คอร์สนี้ยังไม่มีบทเรียน</b>
                แอดมินกำลังเตรียมวิดีโอ กลับมาดูใหม่เร็วๆ นี้
            </div>
        @endif
    </section>

    {{-- แบบทดสอบ --}}
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cv-qz-h"
             x-data="{ left: {{ $retryLeft }}, tick() { if (this.left > 0) { this.left--; if (this.left === 0) window.location.reload(); } } }"
             x-init="setInterval(() => tick(), 1000)">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Final quiz</small><h2 id="nvm-cv-qz-h">แบบทดสอบท้ายคอร์ส</h2></div></div>

        @if($course['last_attempt'])
            <dl class="nvm-kv">
                <dt>ผลสอบล่าสุด</dt>
                <dd>
                    <span class="nvm-num">{{ $course['last_attempt']['score'] }}/{{ $course['last_attempt']['total'] }}</span>
                    @if($course['last_attempt']['passed'])
                        <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ผ่าน</span>
                    @else
                        <span class="nvm-chip nvm-chip--bad"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยังไม่ผ่าน</span>
                    @endif
                </dd>
            </dl>
        @endif

        <div class="nvm-stack nvm-gap-t">
            @if($course['open_attempt'])
                <div class="nvm-status is-warn">
                    <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>
                    <span>คุณมีชุดข้อสอบที่ทำค้างอยู่<small>ทำต่อให้เสร็จก่อนหมดเวลา</small></span>
                </div>
                <div><a href="{{ route('user.classroom.attempt', $course['open_attempt']['attempt_id']) }}" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ทำข้อสอบต่อ</a></div>
            @elseif(! $course['quiz_unlocked'])
                <p class="nvm-note">
                    <svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>
                    <span>{{ $lessonCount === 0 ? 'คอร์สนี้ยังไม่มีบทเรียน จึงยังสอบไม่ได้' : 'ดูบทเรียนให้ครบทุกบทก่อน จึงจะเริ่มสอบได้ (เหลืออีก '.($lessonCount - $course['completed_lessons']).' บท)' }}</span>
                </p>
                <div><button type="button" class="nvm-btn nvm-btn--gold" disabled><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>เริ่มสอบ</button></div>
            @else
                <div x-show="left > 0" x-cloak class="nvm-status is-warn">
                    <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>
                    <span>สอบไม่ผ่านรอบที่แล้ว สอบใหม่ได้ในอีก <b class="nvm-num" x-text="left"></b> วินาที<small>ระหว่างนี้ลองทบทวนบทเรียนก่อน</small></span>
                </div>
                <form method="POST" action="{{ route('user.classroom.quiz.start', $course['id']) }}" class="nvm-cv-form" x-data="{ busy: false }" x-on:submit="busy = true">
                    @csrf
                    <p class="nvm-help">สุ่ม {{ $course['question_count'] }} ข้อ มีเวลา 30 นาที ต้องตอบถูกอย่างน้อย {{ $course['pass_score'] }} ข้อ</p>
                    <button type="submit" class="nvm-btn nvm-btn--gold nvm-gap-t" :disabled="busy || left > 0">
                        <svg class="nvm-ico"><use href="#nvm-i-send"/></svg>
                        <span x-text="busy ? 'กำลังเตรียมข้อสอบ...' : @js($startLabel)">{{ $startLabel }}</span>
                    </button>
                </form>
            @endif
        </div>
    </section>
</div>

</div>
@endsection

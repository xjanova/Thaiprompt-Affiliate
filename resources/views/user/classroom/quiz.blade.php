{{--
    ทำข้อสอบ (user.classroom.attempt) — ตัวเลือกสลับลำดับต่อผู้สอบ · ไม่มีเฉลยในหน้านี้ · ธีมโนวาเต็ม
    Controller: User\ClassroomController@attempt
    ตัวแปร: course (ClassroomCourse), quiz (ClassroomPresenter::attemptQuestions — เหมือน POST /api/v1/classroom/courses/{id}/quiz/start), pageTitle
    ส่งคำตอบ: answers[<question_id>] = ตำแหน่งตัวเลือก (เริ่ม 0) → POST user.classroom.attempt.submit
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'แบบทดสอบ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/classroom-video.css') }}?v={{ filemtime(public_path('theme-nova/member/classroom-video.css')) }}">
@endpush

@php
    $quizCfg = [
        'left' => (int) $quiz['time_left_seconds'],
        'total' => count($quiz['questions']),
        'courseUrl' => route('user.classroom.course', $quiz['course_id']),
    ];
    $startClock = sprintf('%02d:%02d', intdiv($quizCfg['left'], 60), $quizCfg['left'] % 60);
@endphp

@section('content')
<form method="POST" action="{{ route('user.classroom.attempt.submit', $quiz['attempt_id']) }}" class="nvm-stack nvm-cv"
      x-data="crQuiz({{ \Illuminate\Support\Js::from($quizCfg) }})" x-init="boot()" x-on:submit="guard($event)">
    @csrf

    <section class="nvm-card nvm-cv-quizbar nvm-cv-sticky" aria-label="สถานะการทำข้อสอบ">
        <div>
            <div class="nvm-help">แบบทดสอบ{{ $course?->roleLabel() ? 'คอร์ส'.$course->roleLabel() : '' }}</div>
            <b>ตอบแล้ว <span class="nvm-num" x-text="answered">0</span>/{{ count($quiz['questions']) }} · ต้องถูก {{ $quiz['pass_score'] }} ข้อ</b>
        </div>
        <span class="nvm-cv-clock" :class="{ 'is-low': left <= 300 }" aria-live="polite">
            <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg><span class="nvm-num" x-text="clock()">{{ $startClock }}</span>
        </span>
    </section>

    @foreach($quiz['questions'] as $n => $q)
        <fieldset class="nvm-card nvm-box nvm-cv-q">
            <legend class="nvm-cv-q__t"><span class="nvm-cv-q__n nvm-num">{{ $n + 1 }}.</span> {{ $q['question'] }}</legend>
            @foreach($q['choices'] as $ci => $choice)
                <label class="nvm-cv-choice" :class="{ 'is-picked': picked[{{ $q['id'] }}] === {{ $ci }} }">
                    <input type="radio" name="answers[{{ $q['id'] }}]" value="{{ $ci }}" x-on:change="pick({{ $q['id'] }}, {{ $ci }})" required>
                    <span>{{ $choice }}</span>
                </label>
            @endforeach
        </fieldset>
    @endforeach

    <section class="nvm-card nvm-box">
        <p x-show="answered < total" class="nvm-help">ยังไม่ได้ตอบอีก <b class="nvm-num" x-text="total - answered"></b> ข้อ — ข้อที่ไม่ตอบนับว่าผิด</p>
        <div class="nvm-cv-row nvm-cv-row--between nvm-cv-nav nvm-gap-t">
            <a href="{{ route('user.classroom.course', $quiz['course_id']) }}" class="nvm-btn nvm-btn--line nvm-cv-back"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กลับหน้าคอร์ส</a>
            <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy" formnovalidate>
                <svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังส่งคำตอบ...' : 'ส่งคำตอบ'">ส่งคำตอบ</span>
            </button>
        </div>
    </section>
</form>
@endsection

@push('scripts')
<script>
    // ── หน้าทำข้อสอบ: นับถอยหลัง + นับข้อที่ตอบ + กันกดส่งซ้ำ ──
    window.crQuiz = function (cfg) {
        return {
            left: cfg.left,
            total: cfg.total,
            picked: {},
            answered: 0,
            busy: false,

            boot() {
                setInterval(() => {
                    if (this.left > 0) this.left--;
                    if (this.left === 0 && !this.busy) {
                        // หมดเวลา = ชุดนี้ใช้ไม่ได้แล้ว (เซิร์ฟเวอร์ตอบ ATTEMPT_EXPIRED) → กลับหน้าคอร์ส
                        window.location.href = cfg.courseUrl;
                    }
                }, 1000);
            },

            pick(qid, index) {
                this.picked[qid] = index;
                this.answered = Object.keys(this.picked).length;
            },

            clock() {
                const m = Math.floor(this.left / 60), s = this.left % 60;
                return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            },

            guard(e) {
                if (this.busy) { e.preventDefault(); return; }
                if (this.answered < this.total && !window.confirm('ยังตอบไม่ครบ ' + (this.total - this.answered) + ' ข้อ (ข้อที่ไม่ตอบนับว่าผิด) ต้องการส่งเลยไหม?')) {
                    e.preventDefault();
                    return;
                }
                this.busy = true;
            },
        };
    };
</script>
@endpush

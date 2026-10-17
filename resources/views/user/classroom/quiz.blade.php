{{--
    ทำข้อสอบ (user.classroom.attempt) — ตัวเลือกสลับลำดับต่อผู้สอบ · ไม่มีเฉลยในหน้านี้
    Controller: User\ClassroomController@attempt
    ตัวแปร: course (ClassroomCourse), quiz (ClassroomPresenter::attemptQuestions — เหมือน POST /api/v1/classroom/courses/{id}/quiz/start), pageTitle
    ส่งคำตอบ: answers[<question_id>] = ตำแหน่งตัวเลือก (เริ่ม 0) → POST user.classroom.attempt.submit
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'แบบทดสอบ')

@push('styles')
    @include('user.classroom.partials.styles')
@endpush

@php
    $quizCfg = [
        'left' => (int) $quiz['time_left_seconds'],
        'total' => count($quiz['questions']),
        'courseUrl' => route('user.classroom.course', $quiz['course_id']),
    ];
@endphp

@section('content')
<form method="POST" action="{{ route('user.classroom.attempt.submit', $quiz['attempt_id']) }}" class="cr-scope"
      x-data="crQuiz({{ \Illuminate\Support\Js::from($quizCfg) }})" x-init="boot()" x-on:submit="guard($event)">
    @csrf

    <section class="tp-card cr-row cr-between cr-sticky" style="padding:14px 18px;">
        <div>
            <div class="cr-small cr-muted">แบบทดสอบ{{ $course?->roleLabel() ? 'คอร์ส'.$course->roleLabel() : '' }}</div>
            <div style="font-weight:800; color:var(--ink);">ตอบแล้ว <span class="cr-num" x-text="answered">0</span>/{{ count($quiz['questions']) }} · ต้องถูก {{ $quiz['pass_score'] }} ข้อ</div>
        </div>
        <span class="cr-pill" :class="left <= 300 ? 'cr-tone-bad' : 'cr-tone-navy'" aria-live="polite">
            <i class="fas fa-stopwatch"></i> <span class="cr-num" x-text="clock()">30:00</span>
        </span>
    </section>

    @foreach($quiz['questions'] as $n => $q)
        <fieldset class="tp-card cr-q" style="border:0; margin:0;">
            <legend class="cr-q-title" style="padding:0;">
                <span class="cr-num" style="color:var(--deep1);">{{ $n + 1 }}.</span> {{ $q['question'] }}
            </legend>
            @foreach($q['choices'] as $ci => $choice)
                <label class="cr-choice" :class="{ 'is-picked': picked[{{ $q['id'] }}] === {{ $ci }} }">
                    <input type="radio" name="answers[{{ $q['id'] }}]" value="{{ $ci }}" x-on:change="pick({{ $q['id'] }}, {{ $ci }})" required>
                    <span>{{ $choice }}</span>
                </label>
            @endforeach
        </fieldset>
    @endforeach

    <section class="tp-card cr-stack">
        <div x-show="answered < total" class="cr-muted cr-small">ยังไม่ได้ตอบอีก <span class="cr-num" x-text="total - answered"></span> ข้อ — ข้อที่ไม่ตอบนับว่าผิด</div>
        <div class="cr-row cr-between">
            <a href="{{ route('user.classroom.course', $quiz['course_id']) }}" class="cr-btn-ghost"><i class="fas fa-arrow-left"></i> กลับหน้าคอร์ส</a>
            <button type="submit" class="cr-btn cr-tone-navy" :disabled="busy" :class="{ 'is-disabled': busy }" formnovalidate>
                <i class="fas fa-paper-plane"></i> <span x-text="busy ? 'กำลังส่งคำตอบ...' : 'ส่งคำตอบ'">ส่งคำตอบ</span>
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

{{--
    บทเรียน (user.classroom.lesson) — เล่นวิดีโอ YouTube (IFrame API) + ส่ง heartbeat ทุก 10 วิ ระหว่างเล่น
    Controller: User\ClassroomController@lesson
    ตัวแปร: course (ClassroomCourse), lesson (ClassroomPresenter::lessonPayload), lessonNumber, lessonTotal,
            previousLesson|null, nextLesson|null (ClassroomLesson), quizUnlocked, pageTitle

    กันกดข้าม = ฝั่งเซิร์ฟเวอร์ (ClassroomProgressService) — หน้านี้แค่รายงานตำแหน่งตามจริง
    heartbeat: เริ่มเล่น / ทุก 10 วิระหว่างเล่น / หยุด / จบ · ปิดแท็บระหว่างเล่น = ส่งครั้งสุดท้ายด้วย sendBeacon
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'บทเรียน')

@push('styles')
    @include('user.classroom.partials.styles')
@endpush

@php
    $playerCfg = [
        'youtubeId' => $lesson['youtube_id'],
        'progressUrl' => route('user.classroom.lesson.progress', $lesson['id']),
        'csrf' => csrf_token(),
        'watched' => $lesson['watched_seconds'],
        'required' => $lesson['required_seconds'],
        'duration' => $lesson['duration_seconds'],
        'completed' => $lesson['completed'],
        'startAt' => $lesson['completed'] ? 0 : $lesson['last_position_seconds'],
        'quizUnlocked' => $quizUnlocked,
    ];
@endphp

@section('content')
<div class="cr-scope" x-data="crLessonPlayer({{ \Illuminate\Support\Js::from($playerCfg) }})" x-init="boot()">
    <div class="cr-row cr-between">
        <a href="{{ route('user.classroom.course', $course->id) }}" class="cr-btn-ghost"><i class="fas fa-arrow-left"></i> {{ \Illuminate\Support\Str::limit($course->title, 40) }}</a>
        @if($lessonNumber)
            <span class="cr-pill cr-tone-navy">บทที่ {{ $lessonNumber }}/{{ $lessonTotal }}</span>
        @endif
    </div>

    <div class="cr-player">
        <div id="cr-yt-player"></div>
    </div>

    <section class="tp-card cr-stack cr-sticky">
        <h1 class="cr-h2" style="font-size:18px; line-height:1.45;">{{ $lesson['title'] }}</h1>
        <div>
            <div class="cr-row cr-between cr-small" style="margin-bottom:6px;">
                <span class="cr-muted">
                    ดูแล้ว <span class="cr-num" x-text="fmt(watched)">{{ \App\Models\ClassroomLesson::formatDuration($lesson['watched_seconds']) }}</span>
                    จากที่ต้องดู <span class="cr-num">{{ \App\Models\ClassroomLesson::formatDuration($lesson['required_seconds']) }}</span>
                </span>
                <span class="cr-num" x-text="pct() + '%'"></span>
            </div>
            <div class="cr-bar" :class="{ ok: completed }"><span :style="'width:' + pct() + '%'"></span></div>
        </div>

        <div x-show="completed" x-cloak class="cr-alert cr-tone-ok"><i class="fas fa-circle-check"></i> ดูบทนี้ครบแล้ว</div>
        <div x-show="!completed" class="cr-muted cr-small">ระบบนับเวลาตามที่ดูจริง กดข้ามไปข้างหน้าจะไม่ได้เวลาเพิ่ม · ต้องเปิดหน้านี้ไว้ระหว่างดู</div>
        <div x-show="error" x-cloak class="cr-alert cr-tone-bad" x-text="error"></div>

        <div class="cr-row cr-between">
            @if($previousLesson)
                <a href="{{ route('user.classroom.lesson', $previousLesson->id) }}" class="cr-btn-ghost"><i class="fas fa-backward-step"></i> บทก่อนหน้า</a>
            @else
                <span></span>
            @endif

            @if($nextLesson)
                <a href="{{ route('user.classroom.lesson', $nextLesson->id) }}" class="cr-btn cr-tone-navy" :class="{ 'is-disabled': !completed }">
                    บทถัดไป <i class="fas fa-forward-step"></i>
                </a>
            @else
                <a href="{{ route('user.classroom.course', $course->id) }}" class="cr-btn" :class="quizUnlocked ? 'cr-tone-ok' : 'cr-tone-navy'">
                    <span x-text="quizUnlocked ? 'ไปทำแบบทดสอบ' : 'กลับหน้าคอร์ส'">กลับหน้าคอร์ส</span> <i class="fas fa-arrow-right"></i>
                </a>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    // ── ตัวเล่นบทเรียน: YouTube IFrame API + heartbeat ไปเซิร์ฟเวอร์ ──
    window.crLessonPlayer = function (cfg) {
        return {
            watched: cfg.watched,
            required: Math.max(1, cfg.required),
            completed: !!cfg.completed,
            quizUnlocked: !!cfg.quizUnlocked,
            error: '',
            player: null,
            timer: null,
            sending: false,

            boot() {
                const start = () => this.createPlayer();
                if (window.YT && window.YT.Player) {
                    start();
                    return;
                }
                const prev = window.onYouTubeIframeAPIReady;
                window.onYouTubeIframeAPIReady = function () {
                    if (typeof prev === 'function') prev();
                    start();
                };
                if (!document.getElementById('cr-yt-api')) {
                    const s = document.createElement('script');
                    s.id = 'cr-yt-api';
                    s.src = 'https://www.youtube.com/iframe_api';
                    document.head.appendChild(s);
                }
                // ปิดแท็บ/ย้ายหน้าระหว่างเล่น → ส่งตำแหน่งครั้งสุดท้าย
                window.addEventListener('pagehide', () => this.beacon());
            },

            createPlayer() {
                this.player = new YT.Player('cr-yt-player', {
                    videoId: cfg.youtubeId,
                    host: 'https://www.youtube-nocookie.com',
                    playerVars: { rel: 0, modestbranding: 1, playsinline: 1, start: Math.max(0, cfg.startAt || 0) },
                    events: {
                        onStateChange: (e) => this.onState(e.data),
                        onError: () => { this.error = 'เล่นวิดีโอไม่ได้ ลองรีเฟรชหน้า หรือแจ้งแอดมิน'; },
                    },
                });
            },

            onState(state) {
                if (state === YT.PlayerState.PLAYING) {
                    this.send(true);
                    this.stopTimer();
                    this.timer = setInterval(() => this.send(true), 10000);
                } else if (state === YT.PlayerState.PAUSED || state === YT.PlayerState.ENDED || state === YT.PlayerState.BUFFERING) {
                    this.stopTimer();
                    if (state !== YT.PlayerState.BUFFERING) this.send(false);
                }
            },

            stopTimer() {
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
            },

            position() {
                try { return Math.max(0, Math.floor(this.player.getCurrentTime() || 0)); } catch (e) { return 0; }
            },

            async send(playing) {
                if (!this.player || this.sending) return;
                this.sending = true;
                try {
                    const res = await fetch(cfg.progressUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ position_seconds: this.position(), playing: playing }),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok && json.success) {
                        this.watched = json.data.watched_seconds;
                        this.completed = json.data.completed;
                        this.quizUnlocked = json.data.course_quiz_unlocked;
                        this.error = '';
                    } else if (res.status !== 429) {
                        this.error = json.message || 'บันทึกเวลาดูไม่สำเร็จ';
                    }
                } catch (e) {
                    this.error = 'เชื่อมต่อไม่ได้ ระบบจะลองบันทึกใหม่อัตโนมัติ';
                } finally {
                    this.sending = false;
                }
            },

            beacon() {
                if (!this.player || !this.timer || !navigator.sendBeacon) return;
                const data = new FormData();
                data.append('_token', cfg.csrf);
                data.append('position_seconds', String(this.position()));
                data.append('playing', '0');
                navigator.sendBeacon(cfg.progressUrl, data);
            },

            pct() {
                return this.completed ? 100 : Math.min(100, Math.floor(this.watched * 100 / this.required));
            },

            fmt(s) {
                s = Math.max(0, s | 0);
                const m = Math.floor(s / 60), r = s % 60;
                return m + ':' + String(r).padStart(2, '0');
            },
        };
    };
</script>
@endpush

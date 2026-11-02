{{--
    บทเรียน (user.classroom.lesson) — เล่นวิดีโอ YouTube (IFrame API) + ส่ง heartbeat ทุก 10 วิ ระหว่างเล่น · ธีมโนวาเต็ม
    Controller: User\ClassroomController@lesson
    ตัวแปร: course (ClassroomCourse), lesson (ClassroomPresenter::lessonPayload), lessonNumber, lessonTotal,
            previousLesson|null, nextLesson|null (ClassroomLesson), quizUnlocked, pageTitle

    กันกดข้าม = ฝั่งเซิร์ฟเวอร์ (ClassroomProgressService) — หน้านี้แค่รายงานตำแหน่งตามจริง
    heartbeat: เริ่มเล่น / ทุก 10 วิระหว่างเล่น / หยุด / จบ · ปิดแท็บระหว่างเล่น = ส่งครั้งสุดท้ายด้วย sendBeacon
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'บทเรียน')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/classroom-video.css') }}?v={{ filemtime(public_path('theme-nova/member/classroom-video.css')) }}">
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
    $startPct = $lesson['completed'] ? 100 : min(100, (int) floor($lesson['watched_seconds'] * 100 / max(1, $lesson['required_seconds'])));
@endphp

@section('content')
<div class="nvm-stack nvm-cv" x-data="crLessonPlayer({{ \Illuminate\Support\Js::from($playerCfg) }})" x-init="boot()">
    <div class="nvm-cv-row nvm-cv-row--between">
        <a href="{{ route('user.classroom.course', $course->id) }}" class="nvm-btn nvm-btn--line nvm-btn--sm nvm-cv-back"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>{{ \Illuminate\Support\Str::limit($course->title, 40) }}</a>
        @if($lessonNumber)
            <span class="nvm-chip nvm-chip--gold">บทที่ {{ $lessonNumber }}/{{ $lessonTotal }}</span>
        @endif
    </div>

    <div class="nvm-cv-player">
        <div id="cr-yt-player"></div>
    </div>

    <section class="nvm-card nvm-box nvm-cv-sticky" aria-labelledby="nvm-cv-ls-h">
        <h1 id="nvm-cv-ls-h" class="nvm-cv-title">{{ $lesson['title'] }}</h1>

        <div class="nvm-meter nvm-cv-bar" :class="{ 'is-ok': completed }">
            <div class="nvm-meter__row">
                <span>
                    ดูแล้ว <b class="nvm-num" x-text="fmt(watched)">{{ \App\Models\ClassroomLesson::formatDuration($lesson['watched_seconds']) }}</b>
                    จากที่ต้องดู <b class="nvm-num">{{ \App\Models\ClassroomLesson::formatDuration($lesson['required_seconds']) }}</b>
                </span>
                <b class="nvm-num" x-text="pct() + '%'">{{ $startPct }}%</b>
            </div>
            <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="pct()" aria-label="เวลาดูบทนี้"><i style="--w: {{ $startPct }}%" :style="'--w: ' + pct() + '%'"></i></div>
        </div>

        <div class="nvm-stack nvm-gap-t">
            <div x-show="completed" x-cloak class="nvm-status">
                <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span>ดูบทนี้ครบแล้ว</span>
            </div>
            <p x-show="!completed" class="nvm-help">ระบบนับเวลาตามที่ดูจริง กดข้ามไปข้างหน้าจะไม่ได้เวลาเพิ่ม · ต้องเปิดหน้านี้ไว้ระหว่างดู</p>
            <p x-show="error" x-cloak class="nvm-err" role="alert" x-text="error"></p>

            <div class="nvm-cv-row nvm-cv-row--between nvm-cv-nav">
                @if($previousLesson)
                    <a href="{{ route('user.classroom.lesson', $previousLesson->id) }}" class="nvm-btn nvm-btn--line nvm-cv-back"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>บทก่อนหน้า</a>
                @else
                    <span></span>
                @endif

                @if($nextLesson)
                    <a href="{{ route('user.classroom.lesson', $nextLesson->id) }}" class="nvm-btn nvm-btn--gold" :class="{ 'is-disabled': !completed }">
                        บทถัดไป<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
                    </a>
                @else
                    <a href="{{ route('user.classroom.course', $course->id) }}" class="nvm-btn" :class="quizUnlocked ? 'nvm-btn--gold' : 'nvm-btn--line'">
                        <span x-text="quizUnlocked ? 'ไปทำแบบทดสอบ' : 'กลับหน้าคอร์ส'">กลับหน้าคอร์ส</span><svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
                    </a>
                @endif
            </div>
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

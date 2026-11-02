{{--
    ผลสอบ (user.classroom.result) — คะแนน + ป้ายผ่านการอบรม + เฉลยรายข้อ · ธีมโนวาเต็ม
    Controller: User\ClassroomController@result
    ตัวแปร: course (ClassroomCourse), result (ClassroomPresenter::attemptResult — เหมือน POST /api/v1/classroom/attempts/{id}/submit), pageTitle
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'ผลสอบ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/classroom-video.css') }}?v={{ filemtime(public_path('theme-nova/member/classroom-video.css')) }}">
@endpush

@php
    $cert = $result['certification'];
    $retryAt = $result['retry_available_at'] ? \Illuminate\Support\Carbon::parse($result['retry_available_at']) : null;
    $retryLeft = $retryAt ? max(0, $retryAt->getTimestamp() - now()->getTimestamp()) : 0;
    $scorePct = $result['total'] > 0 ? (int) round($result['score'] * 100 / $result['total']) : 0;
    $certBadge = $cert ? ['certified' => true, 'role' => $cert['role'], 'label' => $cert['label'], 'needs_refresh' => false] : null;
@endphp

@section('content')
<div class="nvm-stack nvm-cv">

<section class="nvm-ph" aria-labelledby="nvm-cv-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.classroom.course', $result['course_id']) }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กลับหน้าคอร์ส</a>
            <div class="nvm-eyebrow nvm-latin">Quiz result</div>
            <h1 id="nvm-cv-h">ผลสอบ{{ $course?->title ? ': '.$course->title : '' }}</h1>
            <div class="nvm-pills">
                @if($result['passed'])
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>สอบผ่าน</span>
                @else
                    <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยังไม่ผ่าน</span>
                @endif
                <span class="nvm-pill nvm-pill--glass nvm-num">{{ $result['score'] }}/{{ $result['total'] }} คะแนน</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset($result['passed'] ? 'images/nova/deco/garland.webp' : 'images/nova/cat/books.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- สรุปผล --}}
<section class="nvm-card nvm-box nvm-cv-result" aria-labelledby="nvm-cv-sum-h">
    @if($result['passed'])
        @if($certBadge)
            <x-training-badge :badge="$certBadge" :size="96" />
        @endif
        <h2 id="nvm-cv-sum-h">{{ ! empty($cert['newly_certified']) ? 'ยินดีด้วย! คุณผ่านการอบรมแล้ว' : 'สอบผ่านแล้ว' }}</h2>
        @if($certBadge)
            <x-training-badge :badge="$certBadge" :size="22" label />
            @if(! empty($cert['newly_certified']) && $cert['points_awarded'] > 0)
                <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ได้คะแนนความประพฤติ +{{ $cert['points_awarded'] }}</span>
            @elseif(! empty($cert['refreshed']))
                <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>ทบทวนกติการุ่นใหม่เรียบร้อย</span>
            @endif
            <p>ป้ายนี้จะแสดงข้างป้ายยืนยันตัวตนของคุณทั้งในแอปและบนเว็บ</p>
        @endif
    @else
        <img class="nvm-cv-result__art" src="{{ asset('images/nova/cat/books.webp') }}" alt="" width="96" height="96">
        <h2 id="nvm-cv-sum-h">ยังไม่ผ่าน ลองอีกครั้งนะ</h2>
        <p>ต้องตอบถูกอย่างน้อย {{ $result['pass_score'] }} จาก {{ $result['total'] }} ข้อ ทบทวนคำอธิบายด้านล่างแล้วสอบใหม่ได้</p>
    @endif

    <div class="nvm-cv-score nvm-num {{ $result['passed'] ? 'is-ok' : '' }}">{{ $result['score'] }}<small>/{{ $result['total'] }}</small></div>
    <div class="nvm-cv-bar {{ $result['passed'] ? 'is-ok' : '' }}">
        <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $scorePct }}" aria-label="คะแนนที่ได้"><i style="--w: {{ $scorePct }}%"></i></div>
    </div>

    @if(! $result['passed'])
        <div x-data="{ left: {{ $retryLeft }} }" x-init="setInterval(() => { if (left > 0) left-- }, 1000)" class="nvm-cv-result">
            <span x-show="left > 0" class="nvm-chip nvm-chip--warn"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>สอบใหม่ได้ในอีก <span class="nvm-num" x-text="left">{{ $retryLeft }}</span> วินาที</span>
            <form method="POST" action="{{ route('user.classroom.quiz.start', $result['course_id']) }}" class="nvm-cv-form" x-data="{ busy: false }" x-on:submit="busy = true">
                @csrf
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy || left > 0">
                    <svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>สอบใหม่
                </button>
            </form>
        </div>
    @endif
</section>

{{-- เฉลยรายข้อ --}}
<section class="nvm-stack" aria-labelledby="nvm-cv-ans-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Answers</small><h2 id="nvm-cv-ans-h">เฉลยและคำอธิบาย</h2></div></div>
    @foreach($result['results'] as $n => $row)
        <article class="nvm-card nvm-box">
            <div class="nvm-cv-row nvm-cv-row--between">
                <p class="nvm-cv-q__t"><span class="nvm-cv-q__n nvm-num">{{ $n + 1 }}.</span> {{ $row['question'] }}</p>
                @if($row['correct'])
                    <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ถูก</span>
                @elseif($row['chosen_index'] === null)
                    <span class="nvm-chip">ไม่ได้ตอบ</span>
                @else
                    <span class="nvm-chip nvm-chip--bad"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ผิด</span>
                @endif
            </div>
            @foreach($row['choices'] as $ci => $choice)
                @php
                    $isCorrect = $row['correct_index'] === $ci;
                    $isChosen = $row['chosen_index'] === $ci;
                @endphp
                <div class="nvm-cv-choice is-static {{ $isCorrect ? 'is-correct' : ($isChosen ? 'is-wrong' : '') }}">
                    @if($isCorrect)
                        <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                    @elseif($isChosen)
                        <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>
                    @else
                        <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
                    @endif
                    <span>{{ $choice }}@if($isChosen) <small class="nvm-cv-muted">(คำตอบของคุณ)</small>@endif</span>
                </div>
            @endforeach
            @if($row['explanation'])
                <p class="nvm-cv-explain"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>{{ $row['explanation'] }}</span></p>
            @endif
        </article>
    @endforeach
</section>

</div>
@endsection

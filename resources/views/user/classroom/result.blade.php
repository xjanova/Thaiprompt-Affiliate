{{--
    ผลสอบ (user.classroom.result) — คะแนน + ป้ายผ่านการอบรม + เฉลยรายข้อ
    Controller: User\ClassroomController@result
    ตัวแปร: course (ClassroomCourse), result (ClassroomPresenter::attemptResult — เหมือน POST /api/v1/classroom/attempts/{id}/submit), pageTitle
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'ผลสอบ')

@push('styles')
    @include('user.classroom.partials.styles')
@endpush

@php
    $cert = $result['certification'];
    $retryAt = $result['retry_available_at'] ? \Illuminate\Support\Carbon::parse($result['retry_available_at']) : null;
    $retryLeft = $retryAt ? max(0, $retryAt->getTimestamp() - now()->getTimestamp()) : 0;
    $scorePct = $result['total'] > 0 ? (int) round($result['score'] * 100 / $result['total']) : 0;
@endphp

@section('content')
<div class="cr-scope">
    <div class="cr-row">
        <a href="{{ route('user.classroom.course', $result['course_id']) }}" class="cr-btn-ghost"><i class="fas fa-arrow-left"></i> กลับหน้าคอร์ส</a>
    </div>

    {{-- สรุปผล --}}
    <section class="tp-card cr-hero" style="text-align:center;">
        <div class="cr-stack" style="align-items:center;">
            @if($result['passed'])
                @if($cert)
                    <x-training-badge :badge="['certified' => true, 'role' => $cert['role'], 'label' => $cert['label'], 'needs_refresh' => false]" :size="96" />
                @endif
                <h1 class="cr-h1">{{ ! empty($cert['newly_certified']) ? 'ยินดีด้วย! คุณผ่านการอบรมแล้ว' : 'สอบผ่านแล้ว' }}</h1>
                @if($cert)
                    <x-training-badge :badge="['certified' => true, 'role' => $cert['role'], 'label' => $cert['label'], 'needs_refresh' => false]" :size="22" label />
                    @if(! empty($cert['newly_certified']) && $cert['points_awarded'] > 0)
                        <span class="cr-pill cr-tone-ok"><i class="fas fa-star"></i> ได้คะแนนความประพฤติ +{{ $cert['points_awarded'] }}</span>
                    @elseif(! empty($cert['refreshed']))
                        <span class="cr-pill cr-tone-ok"><i class="fas fa-rotate"></i> ทบทวนกติการุ่นใหม่เรียบร้อย</span>
                    @endif
                    <p class="cr-muted cr-small" style="margin:0;">ป้ายนี้จะแสดงข้างป้ายยืนยันตัวตนของคุณทั้งในแอปและบนเว็บ</p>
                @endif
            @else
                <div style="font-size:52px; line-height:1;">📘</div>
                <h1 class="cr-h1">ยังไม่ผ่าน ลองอีกครั้งนะ</h1>
                <p class="cr-muted cr-small" style="margin:0;">ต้องตอบถูกอย่างน้อย {{ $result['pass_score'] }} จาก {{ $result['total'] }} ข้อ ทบทวนคำอธิบายด้านล่างแล้วสอบใหม่ได้</p>
            @endif

            <div class="cr-num" style="font-size:clamp(34px, 9vw, 48px); color:{{ $result['passed'] ? 'var(--cr-ok)' : 'var(--cr-bad)' }};">
                {{ $result['score'] }}<span class="cr-muted" style="font-size:.5em;">/{{ $result['total'] }}</span>
            </div>
            <div style="width:min(360px, 100%);">
                <div class="cr-bar {{ $result['passed'] ? 'ok' : '' }}"><span style="width: {{ $scorePct }}%;"></span></div>
            </div>

            @if(! $result['passed'])
                <div x-data="{ left: {{ $retryLeft }} }" x-init="setInterval(() => { if (left > 0) left-- }, 1000)" class="cr-stack" style="align-items:center;">
                    <span x-show="left > 0" class="cr-pill cr-tone-warn"><i class="fas fa-hourglass-half"></i> สอบใหม่ได้ในอีก <span class="cr-num" x-text="left">{{ $retryLeft }}</span> วินาที</span>
                    <form method="POST" action="{{ route('user.classroom.quiz.start', $result['course_id']) }}" x-data="{ busy: false }" x-on:submit="busy = true">
                        @csrf
                        <button type="submit" class="cr-btn cr-tone-navy" :disabled="busy || left > 0" :class="{ 'is-disabled': busy || left > 0 }">
                            <i class="fas fa-rotate-right"></i> สอบใหม่
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    {{-- เฉลยรายข้อ --}}
    <section class="cr-stack">
        <h2 class="cr-h2"><i class="fas fa-list-check" style="color:var(--deep1);"></i> เฉลยและคำอธิบาย</h2>
        @foreach($result['results'] as $n => $row)
            <article class="tp-card cr-q">
                <div class="cr-row cr-between" style="align-items:flex-start;">
                    <p class="cr-q-title" style="flex:1; min-width:0;"><span class="cr-num" style="color:var(--deep1);">{{ $n + 1 }}.</span> {{ $row['question'] }}</p>
                    @if($row['correct'])
                        <span class="cr-pill cr-tone-ok"><i class="fas fa-check"></i> ถูก</span>
                    @elseif($row['chosen_index'] === null)
                        <span class="cr-pill cr-tone-muted"><i class="fas fa-minus"></i> ไม่ได้ตอบ</span>
                    @else
                        <span class="cr-pill cr-tone-bad"><i class="fas fa-xmark"></i> ผิด</span>
                    @endif
                </div>
                @foreach($row['choices'] as $ci => $choice)
                    @php
                        $isCorrect = $row['correct_index'] === $ci;
                        $isChosen = $row['chosen_index'] === $ci;
                    @endphp
                    <div class="cr-choice {{ $isCorrect ? 'is-correct' : ($isChosen ? 'is-wrong' : '') }}" style="cursor:default;">
                        <i class="fas {{ $isCorrect ? 'fa-circle-check' : ($isChosen ? 'fa-circle-xmark' : 'fa-circle') }}" style="margin-top:3px; color:{{ $isCorrect ? 'var(--cr-ok)' : ($isChosen ? 'var(--cr-bad)' : 'var(--ink2)') }};"></i>
                        <span>{{ $choice }}@if($isChosen) <span class="cr-muted cr-small">(คำตอบของคุณ)</span>@endif</span>
                    </div>
                @endforeach
                @if($row['explanation'])
                    <p class="cr-muted cr-small" style="margin:12px 0 0; line-height:1.6;"><i class="fas fa-lightbulb" style="color:var(--accent1);"></i> {{ $row['explanation'] }}</p>
                @endif
            </article>
        @endforeach
    </section>
</div>
@endsection

{{--
    งานไรเดอร์ (user.rider.jobs) — แท็บ งานรอรับ / งานปัจจุบัน / ประวัติ · ธีมโนวาเต็ม
    Controller: User\RiderController@jobs (?tab=available|current|history&status=completed|cancelled|failed)
    ตัวแปร: rider, tab, statusFilter, availableJobs, availableReason, currentJob, currentJobData, history (paginator),
            historyItems (JobSummary[]), canAcceptJobs, blockReason, pageTitle
    (2026-10-13) อ่านอย่างเดียว — รับงาน/ทำงานส่งในแอป Thai Prompt APP เท่านั้น
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'งานไรเดอร์')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/rider.css') }}?v={{ filemtime(public_path('theme-nova/member/rider.css')) }}">
@endpush

@php
    $tabs = [
        'available' => ['label' => 'งานรอรับ', 'icon' => 'pin', 'count' => count($availableJobs)],
        'current' => ['label' => 'งานปัจจุบัน', 'icon' => 'scooter', 'count' => $currentJobData ? 1 : 0],
        'history' => ['label' => 'ประวัติ', 'icon' => 'history', 'count' => null],
    ];
    $historyFilters = [
        '' => 'ทั้งหมด',
        'completed' => 'สำเร็จ',
        'cancelled' => 'ยกเลิก',
        'failed' => 'ส่งไม่สำเร็จ',
    ];
    $availPill = match ($rider->availability) {
        'online' => 'nvm-pill--ok',
        'busy' => 'nvm-pill--gold',
        default => 'nvm-pill--glass',
    };
@endphp

@section('content')
<div class="nvm-stack">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'jobs'])

    {{-- ── รับงานได้ในแอปเท่านั้น (2026-10-13) ─────────────────── --}}
    @include('user.rider.partials.app-only-banner', ['appOnlyNote' => 'หน้านี้ดูงานใกล้คุณ งานที่กำลังส่ง และประวัติงานได้ — กดรับงานและอัปเดตสถานะงานในแอป'])

    <section class="nvm-ph" aria-labelledby="nvm-rd-jobs-h">
        <div class="nvm-ph__in">
            <div class="nvm-ph__txt">
                <div class="nvm-eyebrow nvm-latin">Rider jobs</div>
                <h1 id="nvm-rd-jobs-h">งานไรเดอร์</h1>
                <p class="nvm-ph__lead">ดูงานใกล้คุณ ติดตามงานที่กำลังส่ง และดูประวัติงานทั้งหมด</p>
                <div class="nvm-pills">
                    <span class="nvm-pill {{ $availPill }}"><svg class="nvm-ico"><use href="#nvm-i-scooter"/></svg>{{ $rider->availability_text }}</span>
                </div>
            </div>
            <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scooter.webp') }}" alt="" width="200" height="200">
        </div>
    </section>

    {{-- ── แท็บ ─────────────────────────────────────────────── --}}
    <nav class="nvm-tabs" aria-label="ประเภทงาน">
        @foreach($tabs as $key => $t)
            <a href="{{ route('user.rider.jobs', ['tab' => $key]) }}" class="{{ $tab === $key ? 'is-on' : '' }}" @if($tab === $key) aria-current="page" @endif>
                <svg class="nvm-ico"><use href="#nvm-i-{{ $t['icon'] }}"/></svg>{{ $t['label'] }}
                @if($t['count'])<span class="nvm-chip nvm-chip--gold nvm-num">{{ $t['count'] }}</span>@endif
            </a>
        @endforeach
    </nav>

    @if($tab === 'available')
        {{-- ── งานรอรับ ────────────────────────────────────────── --}}
        @if($rider->status === 'approved' && $rider->availability !== 'online' && !$currentJobData)
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>เปิดรับงานในแอป Thai Prompt APP แล้วงานใกล้คุณจะแสดงที่นี่</p>
        @endif
        @include('user.rider.partials.available-jobs')

    @elseif($tab === 'current')
        {{-- ── งานปัจจุบัน ─────────────────────────────────────── --}}
        @if($currentJobData)
            @include('user.rider.partials.current-job', ['jobData' => $currentJobData])
        @else
            <div class="nvm-card nvm-empty">
                <img src="{{ asset('images/nova/deco/scooter.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีงานที่กำลังทำ</b>
                รับงานใกล้คุณได้ในแอป Thai Prompt APP
                <div><a href="{{ route('user.rider.jobs', ['tab' => 'available']) }}" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>ดูงานรอรับ</a></div>
            </div>
        @endif

    @else
        {{-- ── ประวัติงาน ──────────────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-hist-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">History</small><h2 id="nvm-rd-hist-h">ประวัติงาน</h2></div>
                <nav class="nvm-tabs" aria-label="กรองสถานะ">
                    @foreach($historyFilters as $value => $label)
                        <a href="{{ route('user.rider.jobs', array_filter(['tab' => 'history', 'status' => $value ?: null])) }}"
                           class="{{ ($statusFilter ?? '') === $value ? 'is-on' : '' }}" @if(($statusFilter ?? '') === $value) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            @if(count($historyItems) === 0)
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="86" height="86" loading="lazy">
                    <b>ยังไม่มีประวัติงาน{{ $statusFilter ? 'ในสถานะนี้' : '' }}</b>
                    งานที่ส่งสำเร็จ ยกเลิก หรือส่งไม่สำเร็จจะแสดงที่นี่
                </div>
            @else
                <div class="nvm-txs nvm-tks">
                    @foreach($historyItems as $row)
                        @include('user.rider.partials.job-row', ['row' => $row])
                    @endforeach
                </div>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ทั้งหมด {{ number_format($history->total()) }} งาน</p>
            @endif

            @if($history->hasPages())
                <div class="nvm-pager nvm-gap-t">{{ $history->links() }}</div>
            @endif
        </section>
    @endif
</div>
@endsection

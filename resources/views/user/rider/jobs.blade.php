{{--
    งานไรเดอร์ (user.rider.jobs) — แท็บ งานรอรับ / งานปัจจุบัน / ประวัติ
    Controller: User\RiderController@jobs (?tab=available|current|history&status=completed|cancelled|failed)
    ตัวแปร: rider, tab, statusFilter, availableJobs, availableReason, currentJob, currentJobData, history (paginator),
            historyItems (JobSummary[]), canAcceptJobs, blockReason, pageTitle
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'งานไรเดอร์')

@push('styles')
    @include('user.rider.partials.styles')
@endpush

@php
    $tabs = [
        'available' => ['label' => 'งานรอรับ', 'icon' => 'fa-bolt', 'count' => count($availableJobs)],
        'current' => ['label' => 'งานปัจจุบัน', 'icon' => 'fa-person-biking', 'count' => $currentJobData ? 1 : 0],
        'history' => ['label' => 'ประวัติ', 'icon' => 'fa-clock-rotate-left', 'count' => null],
    ];
    $historyFilters = [
        '' => 'ทั้งหมด',
        'completed' => 'สำเร็จ',
        'cancelled' => 'ยกเลิก',
        'failed' => 'ส่งไม่สำเร็จ',
    ];
@endphp

@section('content')
<div class="rd-scope" x-data="rdLocator({{ \Illuminate\Support\Js::from(route('user.rider.location')) }})" x-on:rd-refresh-location.window="refresh()">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'jobs'])

    <section class="tp-card rd-hero">
        <div class="rd-hero-in">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:17px; font-size:22px;"><i class="fas fa-route"></i></span>
            <div style="flex:1 1 220px; min-width:0;">
                <h1 class="rd-h1">งานไรเดอร์</h1>
                <div class="rd-muted rd-small">รับงานใกล้คุณ ติดตามงานที่กำลังส่ง และดูประวัติงานทั้งหมด</div>
            </div>
            <span class="rd-pill {{ $rider->availability === 'online' ? 'rd-tone-ok' : ($rider->availability === 'busy' ? 'rd-tone-info' : 'rd-tone-muted') }}">
                <span class="rd-dot {{ $rider->availability === 'online' ? 'live' : '' }}"></span> {{ $rider->availability_text }}
            </span>
        </div>
    </section>

    {{-- ── แท็บ ─────────────────────────────────────────────── --}}
    <nav class="rd-nav" aria-label="ประเภทงาน">
        @foreach($tabs as $key => $t)
            <a href="{{ route('user.rider.jobs', ['tab' => $key]) }}" class="{{ $tab === $key ? 'on' : '' }}" @if($tab === $key) aria-current="page" @endif>
                <i class="fas {{ $t['icon'] }}"></i> {{ $t['label'] }}
                @if($t['count'])
                    <span class="rd-pill {{ $tab === $key ? '' : 'rd-tone-gold' }}" style="padding:3px 8px; {{ $tab === $key ? 'background:color-mix(in srgb, var(--rd-on) 25%, transparent); color:var(--rd-on);' : '' }}">{{ $t['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    @if($tab === 'available')
        {{-- ── งานรอรับ ────────────────────────────────────────── --}}
        @if($rider->status === 'approved' && $rider->availability !== 'online' && !$currentJobData)
            <div class="rd-alert rd-tone-info">
                <i class="fas fa-circle-info"></i>
                <div>เปิดรับงานได้ที่<a href="{{ route('user.rider.dashboard') }}" class="rd-link"> แดชบอร์ด</a> แล้วงานใกล้คุณจะแสดงที่นี่</div>
            </div>
        @endif
        @include('user.rider.partials.available-jobs')

    @elseif($tab === 'current')
        {{-- ── งานปัจจุบัน ─────────────────────────────────────── --}}
        @if($currentJobData)
            @include('user.rider.partials.current-job', ['jobData' => $currentJobData])
        @else
            <div class="tp-card rd-empty">
                <div class="ic"><i class="fas fa-motorcycle"></i></div>
                <div style="font-weight:800; font-size:16px;">ยังไม่มีงานที่กำลังทำ</div>
                <p class="rd-muted" style="font-size:13px; margin:6px auto 14px; max-width:380px;">ไปที่แท็บงานรอรับเพื่อเลือกงานใกล้คุณ</p>
                <a href="{{ route('user.rider.jobs', ['tab' => 'available']) }}" class="rd-btn3d rd-tone-gold sm"><i class="fas fa-bolt"></i> ดูงานรอรับ</a>
            </div>
        @endif

    @else
        {{-- ── ประวัติงาน ──────────────────────────────────────── --}}
        <div class="rd-row" role="group" aria-label="กรองสถานะ">
            @foreach($historyFilters as $value => $label)
                <a href="{{ route('user.rider.jobs', array_filter(['tab' => 'history', 'status' => $value ?: null])) }}"
                   class="rd-chip {{ ($statusFilter ?? '') === $value ? 'on' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <section class="tp-card">
            @if(count($historyItems) === 0)
                <div class="rd-empty">
                    <div class="ic"><i class="fas fa-box-open"></i></div>
                    <div style="font-weight:800; font-size:16px;">ยังไม่มีประวัติงาน{{ $statusFilter ? 'ในสถานะนี้' : '' }}</div>
                    <p class="rd-muted" style="font-size:13px; margin:6px 0 0;">งานที่ส่งสำเร็จ ยกเลิก หรือส่งไม่สำเร็จจะแสดงที่นี่</p>
                </div>
            @else
                <div class="rd-list">
                    @foreach($historyItems as $row)
                        @include('user.rider.partials.job-row', ['row' => $row])
                    @endforeach
                </div>
            @endif
        </section>

        @if($history->hasPages())
            <div class="rd-row" style="justify-content:space-between;">
                @if($history->previousPageUrl())
                    <a href="{{ $history->previousPageUrl() }}" class="tp-btn"><i class="fas fa-chevron-left"></i> ก่อนหน้า</a>
                @else
                    <span></span>
                @endif
                <span class="rd-muted rd-small">หน้า {{ $history->currentPage() }} / {{ $history->lastPage() }} · ทั้งหมด {{ number_format($history->total()) }} งาน</span>
                @if($history->nextPageUrl())
                    <a href="{{ $history->nextPageUrl() }}" class="tp-btn">ถัดไป <i class="fas fa-chevron-right"></i></a>
                @else
                    <span></span>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
    @include('user.rider.partials.scripts')
@endpush
